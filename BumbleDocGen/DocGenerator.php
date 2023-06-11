<?php

declare(strict_types=1);

namespace BumbleDocGen;

use BumbleDocGen\Core\Configuration\Exception\InvalidConfigurationParameterException;
use BumbleDocGen\Core\Parser\Entity\RootEntityCollectionsGroup;
use BumbleDocGen\Core\Parser\ProjectParser;
use BumbleDocGen\Core\Renderer\Renderer;
use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use function BumbleDocGen\Core\bites_int_to_string;

/**
 * Class for generating documentation.
 */
final class DocGenerator
{
    public const VERSION = '1.0.0';

    public function __construct(
        private ProjectParser              $parser,
        private Renderer                   $render,
        private RootEntityCollectionsGroup $rootEntityCollectionsGroup,
        private Logger                     $logger
    )
    {
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidConfigurationParameterException
     */
    public function parseAndGetRootEntityCollectionsGroup(): RootEntityCollectionsGroup
    {
        $this->parser->parse();
        return $this->rootEntityCollectionsGroup;
    }

    /**
     * Generates documentation using configuration
     *
     * @throws InvalidArgumentException
     */
    public function generate(): void
    {
        $start = microtime(true);
        $memory = memory_get_usage();

        try {
            $this->parser->parse();
            $this->render->run();
        } catch (\Exception $e) {
            $this->logger->critical("{$e->getFile()}:{$e->getLine()} {$e->getMessage()} \n\n{{$e->getTraceAsString()}}");
        }

        $time = microtime(true) - $start;
        $this->logger->notice("Time of execution: {$time} sec.");
        $memory = memory_get_usage() - $memory;
        $this->logger->notice('Memory:' . bites_int_to_string($memory));
    }
}
