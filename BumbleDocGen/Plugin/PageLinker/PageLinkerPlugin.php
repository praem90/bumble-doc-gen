<?php

declare(strict_types=1);

namespace BumbleDocGen\Plugin\PageLinker;

use BumbleDocGen\Parser\Entity\ClassEntity;
use BumbleDocGen\Plugin\TemplatePluginInterface;
use BumbleDocGen\Render\Context\Context;
use BumbleDocGen\Render\Twig\Function\GetDocumentedClassUrl;
use Psr\Log\LoggerInterface;

final class PageLinkerPlugin implements TemplatePluginInterface
{
    private array $keyUsageCount = [];

    public function __construct(
        private string $linkRegEx = '/(`)([^<>\n]+?)(`_)/m',
        private int $groupRegExNumber = 2,
        private string $outputTemplate = "`%title% <%url%>`_"
    ) {
    }

    private function getAllPageLinks(Context $context): array
    {
        static $pageLinks = null;
        if (is_null($pageLinks)) {
            $pageLinks = [];
            $templatesDir = $context->getConfiguration()->getTemplatesDir();
            $breadcrumbsHelper = $context->getBreadcrumbsHelper();

            $addLinkKey = function (string $key, array $breadcrumb) use (&$pageLinks) {
                $this->keyUsageCount[$key] ??= 0;
                ++$this->keyUsageCount[$key];
                $pageLinks[$key] = $breadcrumb;
            };

            /**@var \SplFileInfo[] $allFiles */
            $allFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $templatesDir, \FilesystemIterator::SKIP_DOTS
                )
            );
            foreach ($allFiles as $file) {
                $filePatch = str_replace($templatesDir, '', $file->getRealPath());
                if (!str_ends_with($filePatch, '.twig')) {
                    continue;
                }
                foreach ($breadcrumbsHelper->getBreadcrumbs($filePatch) as $breadcrumb) {
                    $addLinkKey($breadcrumb['url'], $breadcrumb);
                    $addLinkKey($breadcrumb['title'], $breadcrumb);
                    $linkKey = $breadcrumbsHelper->getTemplateLinkKey($filePatch);
                    if ($linkKey) {
                        $addLinkKey($linkKey, $breadcrumb);
                    }
                }
            }
        }
        return $pageLinks;
    }

    private function getEntityUrlData(string $linkString, Context $context): ?array
    {
        static $pageLinks = null;
        if (is_null($pageLinks)) {
            $pageLinks = [];
            foreach ($context->getClassEntityCollection() as $classEntity) {
                /**@var ClassEntity $classEntity */
                $pageLinks[$classEntity->getShortName()] = $classEntity;
                $this->keyUsageCount[$classEntity->getShortName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getShortName()];

                $pageLinks[$classEntity->getFileName()] = $classEntity;
                $this->keyUsageCount[$classEntity->getFileName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getFileName()];

                $pageLinks[$classEntity->getName()] = $classEntity;
                $this->keyUsageCount[$classEntity->getName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getName()];
            }
        }

        $classData = explode('::', $linkString);
        $className = $classData[0];

        if (isset($pageLinks[$className])) {
            $this->checkKey($className, $context->getConfiguration()->getLogger());

            $cursor = '';
            if (isset($classData[1])) {
                if (str_ends_with($classData[1], '()')) {
                    $cursor = 'm' . str_replace('()', '', $classData[1]);
                } elseif (str_starts_with($classData[1], '$')) {
                    $cursor = 'p' . str_replace('$', '', $classData[1]);
                }
            }

            $getDocumentedClassUrl = new GetDocumentedClassUrl($context);
            $url = $getDocumentedClassUrl($pageLinks[$className]->getName(), $cursor);

            return [
                'url' => $url,
                'title' => $linkString,
            ];
        }
        return null;
    }

    private function checkKey(string $key, LoggerInterface $logger): void
    {
        if (($this->keyUsageCount[$key] ?? 0) > 1) {
            $logger->warning(
                "PageLinkerPlugin: Key `{$key}` refers to multiple templates ({$this->keyUsageCount[$key]}). Use a unique link key to avoid mistakes"
            );
        }
    }

    public function handleRenderedTemplateContent(string $content, Context $context): string
    {
        $logger = $context->getConfiguration()->getLogger();

        $pageLinks = $this->getAllPageLinks($context);
        return preg_replace_callback($this->linkRegEx, function (array $matches) use ($pageLinks, $logger, $context) {
            $linkString = $matches[$this->groupRegExNumber];
            if (array_key_exists($linkString, $pageLinks)) {
                $breadcrumb = $pageLinks[$linkString];
                $this->checkKey($linkString, $logger);
                return "`{$breadcrumb['title']} <{$breadcrumb['url']}>`_";
            } else {
                $entityUrlData = $this->getEntityUrlData($linkString, $context);
                if ($entityUrlData) {
                    return str_replace(
                        ['%title%', '%url%'],
                        [$entityUrlData['title'], $entityUrlData['url']],
                        $this->outputTemplate
                    );
                }
            }

            $logger->warning("PageLinkerPlugin: Key `{$linkString}` not found to get document link.");
            return $linkString;
        }, $content);
    }
}
