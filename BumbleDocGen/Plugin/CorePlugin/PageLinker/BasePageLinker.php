<?php

declare(strict_types=1);

namespace BumbleDocGen\Plugin\CorePlugin\PageLinker;

use BumbleDocGen\Parser\Entity\ClassEntity;
use BumbleDocGen\Plugin\Event\Render\BeforeCreatingDocFile;
use BumbleDocGen\Plugin\PluginInterface;
use BumbleDocGen\Render\Context\Context;
use BumbleDocGen\Render\EntityDocRender\EntityDocRenderHelper;
use Psr\Log\LoggerInterface;

abstract class BasePageLinker implements PluginInterface
{
    public const CLASS_ENTITY_SHORT_LINK_OPTION = 'short_form';
    public const CLASS_ENTITY_FULL_LINK_OPTION = 'full_form';
    public const CLASS_ENTITY_ONLY_CURSOR_LINK_OPTION = 'only_cursor';

    private array $keyUsageCount = [];

    /**
     * Template to search for empty links
     *
     * @example /(`)([^<>\n]+?)(`_)/m
     */
    abstract function getLinkRegEx(): string;

    /**
     * Group number of the regular expression that contains the text that will be used to search for the link
     */
    abstract function getGroupRegExNumber(): int;

    /**
     * Template of the result of processing an empty link by a plugin.
     * Keys %title% and %url% will be replaced with real data
     *
     * @example `%title% <%url%>`_
     */
    abstract function getOutputTemplate(): string;

    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCreatingDocFile::class => 'beforeCreatingDocFile',
        ];
    }

    final public function beforeCreatingDocFile(BeforeCreatingDocFile $event): void
    {
        $context = $event->getContext();
        $pageLinks = $this->getAllPageLinks($context);

        $content = preg_replace_callback(
            $this->getLinkRegEx(),
            function (array $matches) use ($pageLinks, $context) {
                $linkString = $matches[$this->getGroupRegExNumber()];
                if (array_key_exists($linkString, $pageLinks)) {
                    $breadcrumb = $pageLinks[$linkString];
                    $this->checkKey($linkString, $this->logger);
                    return $this->getFilledOutputTemplate($breadcrumb['title'], $breadcrumb['url']);
                } else {
                    $entityUrlData = $this->getEntityUrlData($linkString, $context);
                    if ($entityUrlData) {
                        return $this->getFilledOutputTemplate($entityUrlData['title'], $entityUrlData['url']);
                    }
                }

                $this->logger->warning("PageLinkerPlugin: Key `{$linkString}` not found to get document link.");
                return $linkString;
            },
            $event->getContent()
        );

        $event->setContent($content);
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
                $this->keyUsageCount[$classEntity->getShortName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getShortName()];

                $this->keyUsageCount[$classEntity->getFileName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getFileName()];

                $this->keyUsageCount[$classEntity->getName()] ??= 0;
                ++$this->keyUsageCount[$classEntity->getName()];
            }
        }

        $entityUrlData = EntityDocRenderHelper::getEntityUrlDataByLink($linkString, $context);
        if ($entityUrlData['url']) {
            $linkString = explode('|', $linkString)[0];
            $className = explode('::', $linkString)[0];

            if (isset($pageLinks[$className])) {
                $this->checkKey($className, $context->getConfiguration()->getLogger());
            }
            return $entityUrlData;
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

    protected function getFilledOutputTemplate(string $title, string $url): string
    {
        return str_replace(
            ['%title%', '%url%'],
            [$title, $url],
            $this->getOutputTemplate()
        );
    }
}
