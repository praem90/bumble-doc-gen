<?php

declare(strict_types=1);

namespace BumbleDocGen\LanguageHandler\Php;

use BumbleDocGen\Core\Cache\LocalCache\Exception\InvalidCallContextException;
use BumbleDocGen\Core\Cache\LocalCache\Exception\ObjectNotFoundException;
use BumbleDocGen\Core\Cache\LocalCache\LocalObjectCache;
use BumbleDocGen\Core\Configuration\ConfigurationParameterBag;
use BumbleDocGen\Core\Configuration\Exception\InvalidConfigurationParameterException;
use BumbleDocGen\Core\Parser\FilterCondition\ConditionInterface;
use BumbleDocGen\Core\Render\EntityDocRender\EntityDocRenderInterface;
use BumbleDocGen\Core\Render\EntityDocRender\EntityDocRendersCollection;
use BumbleDocGen\Core\Render\Twig\Filter\CustomFilterInterface;
use BumbleDocGen\Core\Render\Twig\Filter\CustomFiltersCollection;
use BumbleDocGen\Core\Render\Twig\Function\CustomFunctionInterface;
use BumbleDocGen\Core\Render\Twig\Function\CustomFunctionsCollection;

final class PhpHandlerSettings
{
    public const SETTINGS_PREFIX = 'language_handlers.php.settings';
    public const DEFAULT_SETTINGS_FILE = __DIR__ . '/phpHandlerDefaultSettings.yaml';

    public function __construct(
        private ConfigurationParameterBag $parameterBag,
        private LocalObjectCache          $localObjectCache
    )
    {
        $parameterBag->addValueFromFileIfNotExists(
            self::SETTINGS_PREFIX,
            self::DEFAULT_SETTINGS_FILE,
        );
    }

    private function getSettingsKey(string $key): string
    {
        return self::SETTINGS_PREFIX . ".{$key}";
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getClassEntityFilter(): ConditionInterface
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        /** @var ConditionInterface $classEntityFilter */
        $classEntityFilter = $this->parameterBag->validateAndGetClassValue(
            $this->getSettingsKey('class_filter'),
            ConditionInterface::class
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $classEntityFilter);
        return $classEntityFilter;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getClassConstantEntityFilter(): ConditionInterface
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        /** @var ConditionInterface $constantEntityFilter */
        $constantEntityFilter = $this->parameterBag->validateAndGetClassValue(
            $this->getSettingsKey('class_constant_filter'),
            ConditionInterface::class
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $constantEntityFilter);
        return $constantEntityFilter;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getMethodEntityFilter(): ConditionInterface
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        /** @var ConditionInterface $methodEntityFilter */
        $methodEntityFilter = $this->parameterBag->validateAndGetClassValue(
            $this->getSettingsKey('method_filter'),
            ConditionInterface::class
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $methodEntityFilter);
        return $methodEntityFilter;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getPropertyEntityFilter(): ConditionInterface
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        /** @var ConditionInterface $propertyEntityFilter */
        $propertyEntityFilter = $this->parameterBag->validateAndGetClassValue(
            $this->getSettingsKey('property_filter'),
            ConditionInterface::class
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $propertyEntityFilter);
        return $propertyEntityFilter;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getEntityDocRendersCollection(): EntityDocRendersCollection
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        $entityDocRendersCollection = new EntityDocRendersCollection();
        $entityDocRenders = $this->parameterBag->validateAndGetClassListValue(
            $this->getSettingsKey('doc_renders'),
            EntityDocRenderInterface::class
        );
        foreach ($entityDocRenders as $entityDocRender) {
            $entityDocRendersCollection->add($entityDocRender);
        }
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $entityDocRendersCollection);
        return $entityDocRendersCollection;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getFileSourceBaseUrl(): ?string
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        $fileSourceBaseUrl = $this->parameterBag->validateAndGetStringValue(
            $this->getSettingsKey('file_source_base_url')
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $fileSourceBaseUrl);
        return $fileSourceBaseUrl;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function asyncSourceLoadingEnabled(): bool
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        $asyncSourceLoadingEnabled = $this->parameterBag->validateAndGetBooleanValue(
            $this->getSettingsKey('async_source_loading_enabled')
        );
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $asyncSourceLoadingEnabled);
        return $asyncSourceLoadingEnabled;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getCustomTwigFunctions(): CustomFunctionsCollection
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        $customFunctions = $this->parameterBag->validateAndGetClassListValue(
            $this->getSettingsKey('custom_twig_functions'),
            CustomFunctionInterface::class
        );
        $customFunctionsCollection = new CustomFunctionsCollection();
        foreach ($customFunctions as $customFunction) {
            $customFunctionsCollection->add($customFunction);
        }
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $customFunctionsCollection);
        return $customFunctionsCollection;
    }

    /**
     * @throws InvalidConfigurationParameterException
     */
    public function getCustomTwigFilters(): CustomFiltersCollection
    {
        try {
            return $this->localObjectCache->getCurrentMethodCachedResult('');
        } catch (ObjectNotFoundException|InvalidCallContextException) {
        }
        $customFilters = $this->parameterBag->validateAndGetClassListValue(
            $this->getSettingsKey('custom_twig_filters'),
            CustomFilterInterface::class

        );
        $customFiltersCollection = new CustomFiltersCollection();
        foreach ($customFilters as $customFilter) {
            $customFiltersCollection->add($customFilter);
        }
        $this->localObjectCache->cacheCurrentMethodResultSilently('', $customFiltersCollection);
        return $customFiltersCollection;
    }
}
