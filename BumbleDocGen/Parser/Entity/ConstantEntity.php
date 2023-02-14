<?php

declare(strict_types=1);

namespace BumbleDocGen\Parser\Entity;

use BumbleDocGen\Parser\Entity\Cache\CacheableEntityWrapperFactory;
use BumbleDocGen\Parser\ParserHelper;
use phpDocumentor\Reflection\DocBlock;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * Class constant entity
 */
class ConstantEntity extends BaseEntity
{
    private ?ReflectionClassConstant $reflectionClassConstant = null;

    private function __construct(
        protected ClassEntity $classEntity,
        protected string      $constantName,
        protected string      $declaringClassName,
        protected string      $implementingClassName,
    )
    {
        parent::__construct($classEntity->getConfiguration(), $classEntity->getReflector());
    }

    public static function create(
        ClassEntity $classEntity,
        string      $constantName,
        string      $declaringClassName,
        string      $implementingClassName,
        bool        $reloadCache = false
    ): ConstantEntity
    {
        static $classEntities = [];
        $objectId = "{$classEntity->getName()}:{$constantName}";
        if (!isset($classEntities[$objectId]) || $reloadCache) {
            $classEntities[$objectId] = new static(
                $classEntity, $constantName, $declaringClassName, $implementingClassName
            );
        }
        return $classEntities[$objectId];
    }

    #[Cache\CacheableMethod] public function getDocBlock(): DocBlock
    {
        $classEntity = CacheableEntityWrapperFactory::createClassEntityByReflection(
            $this->configuration,
            $this->reflector,
            $this->getDocCommentReflectionRecursive()->getImplementingClass()
        );
        return ParserHelper::getDocBlock($classEntity, $this->getDocCommentRecursive());
    }

    public function getClassEntity(): ClassEntity
    {
        return $this->classEntity;
    }

    public function getReflection(): ReflectionClassConstant
    {
        if (!$this->reflectionClassConstant) {
            $this->reflectionClassConstant = $this->classEntity->getReflection()->getReflectionConstant($this->constantName);
        }
        return $this->reflectionClassConstant;
    }

    public function getImplementingReflectionClass(): ReflectionClass
    {
        return $this->getReflection()->getDeclaringClass();
    }

    public function getImplementingClassName(): string
    {
        return $this->implementingClassName;
    }

    public function getImplementingClass(ClassEntityCollection $classEntityPool): ?ClassEntity
    {
        $implementingClassName = $this->getImplementingClassName();
        if (!$implementingClassName) {
            return null;
        }
        return $classEntityPool->getLoadedOrCreateNew($implementingClassName);
    }

    protected function getDocCommentReflectionRecursive(): ReflectionClassConstant
    {
        return $this->getReflection();
    }

    #[Cache\CacheableMethod] protected function getDocCommentRecursive(): string
    {
        static $docCommentsCache = [];
        $objectId = $this->getObjectId();
        if (!isset($docCommentsCache[$objectId])) {
            $docCommentsCache[$objectId] = $this->getReflection()->getDocComment() ?: ' ';
        }
        return $docCommentsCache[$objectId];
    }

    public function getName(): string
    {
        return $this->constantName;
    }

    #[Cache\CacheableMethod] public function getFileName(): ?string
    {
        $fullFileName = $this->getReflection()->getDeclaringClass()->getFileName();
        if (!$fullFileName) {
            return null;
        }
        return str_replace(
            $this->configuration->getProjectRoot(),
            '',
            $fullFileName
        );
    }

    #[Cache\CacheableMethod] public function getDescription(): string
    {
        $docBlock = $this->getDocBlock();
        return $docBlock->getSummary();
    }

    #[Cache\CacheableMethod] public function isPublic(): bool
    {
        return $this->getReflection()->isPublic();
    }

    #[Cache\CacheableMethod] public function isProtected(): bool
    {
        return $this->getReflection()->isProtected();
    }

    #[Cache\CacheableMethod] public function isPrivate(): bool
    {
        return $this->getReflection()->isPrivate();
    }

    #[Cache\CacheableMethod] public function getStartLine(): int
    {
        return $this->getReflection()->getStartLine();
    }

    #[Cache\CacheableMethod] public function getEndLine(): int
    {
        return $this->getReflection()->getEndLine();
    }
}
