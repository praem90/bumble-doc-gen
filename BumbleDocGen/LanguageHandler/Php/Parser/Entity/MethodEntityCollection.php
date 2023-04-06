<?php

declare(strict_types=1);

namespace BumbleDocGen\LanguageHandler\Php\Parser\Entity;

use BumbleDocGen\Core\Configuration\Exception\InvalidConfigurationParameterException;
use BumbleDocGen\Core\Parser\Entity\BaseEntityCollection;
use BumbleDocGen\LanguageHandler\Php\Parser\Entity\Cache\CacheablePhpEntityFactory;
use BumbleDocGen\LanguageHandler\Php\Parser\Entity\Exception\ReflectionException;
use DI\DependencyException;
use DI\NotFoundException;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use Psr\Log\LoggerInterface;

/**
 * @implements \IteratorAggregate<int, MethodEntity>
 */
final class MethodEntityCollection extends BaseEntityCollection
{
    public function __construct(
        private ClassEntity               $classEntity,
        private CacheablePhpEntityFactory $cacheablePhpEntityFactory,
        private LoggerInterface           $logger
    )
    {
    }

    /**
     * @throws ReflectionException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidConfigurationParameterException
     */
    public static function createByClassEntity(
        ClassEntity               $classEntity,
        CacheablePhpEntityFactory $cacheablePhpEntityFactory,
        LoggerInterface           $logger
    ): MethodEntityCollection
    {
        $methodEntityCollection = new MethodEntityCollection($classEntity, $cacheablePhpEntityFactory, $logger);
        $configuration = $classEntity->getConfiguration();

        $methodEntityFilter = $classEntity->getPhpHandlerSettings()->getMethodEntityFilter();
        foreach ($classEntity->getMethodsData() as $name => $methodData) {
            $methodEntity = $cacheablePhpEntityFactory->createMethodEntity(
                $classEntity,
                $name,
                $methodData['declaringClass'],
                $methodData['implementingClass']
            );
            if ($methodEntityFilter->canAddToCollection($methodEntity)) {
                $methodEntityCollection->add($methodEntity);
            }
        }

        $logger = $configuration->getLogger();
        $docBlock = $classEntity->getDocBlock();
        $methodsBlocks = $docBlock->getTagsByName('method');
        if ($methodsBlocks) {
            foreach ($methodsBlocks as $methodsBlock) {
                try {
                    /**@var Method $methodsBlock */
                    $methodEntity = DynamicMethodEntity::createByAnnotationMethod($classEntity, $methodsBlock);
                    $methodEntityCollection->add($methodEntity);
                } catch (\Exception $e) {
                    $logger->error($e->getMessage());
                }
            }
        }

        return $methodEntityCollection;
    }

    public function add(MethodEntityInterface $methodEntity, bool $reload = false): MethodEntityCollection
    {
        $objectId = $methodEntity->getObjectId();
        if (!isset($this->entities[$objectId]) || $reload) {
            $this->entities[$objectId] = $methodEntity;
        }
        return $this;
    }

    public function get(string $objectId): ?MethodEntity
    {
        return $this->entities[$objectId] ?? null;
    }

    /**
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws DependencyException
     * @throws InvalidConfigurationParameterException
     */
    public function unsafeGet(string $key): ?MethodEntity
    {
        $methodEntity = $this->get($key);
        if (!$methodEntity) {
            $methodData = $this->classEntity->getMethodsData()[$key] ?? null;
            if (is_array($methodData)) {
                return $this->cacheablePhpEntityFactory->createMethodEntity(
                    $this->classEntity,
                    $key,
                    $methodData['declaringClass'],
                    $methodData['implementingClass']
                );
            }
        }
        return $methodEntity;
    }

    public function getInitializations(): MethodEntityCollection
    {
        $methodEntityCollection = clone $this;
        foreach ($this as $objectId => $methodEntity) {
            try {
                /**@var MethodEntity $methodEntity */
                if (!$methodEntity->isInitialization()) {
                    $methodEntityCollection->remove($objectId);
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }
        return $methodEntityCollection;
    }

    public function getAllExceptInitializations(): MethodEntityCollection
    {
        $methodEntityCollection = clone $this;
        foreach ($this as $objectId => $methodEntity) {
            try {
                /**@var MethodEntity $methodEntity */
                if ($methodEntity->isInitialization()) {
                    $methodEntityCollection->remove($objectId);
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }
        return $methodEntityCollection;
    }
}
