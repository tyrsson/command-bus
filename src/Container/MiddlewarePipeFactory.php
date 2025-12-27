<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use SplPriorityQueue;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Exception;
use Webware\CommandBus\MiddlewareInterface;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

use function array_map;
use function array_reduce;
use function sprintf;

/**
 * @phpstan-import-type CommandBusConfig from ConfigProvider
 * @phpstan-import-type MiddlewarePipeSpec from ConfigProvider
 * @phpstan-import-type MiddlewareSpec from ConfigProvider
 * @phpstan-import-type CommandMap from ConfigProvider
 */
final class MiddlewarePipeFactory
{
    public function __invoke(ContainerInterface $container): MiddlewarePipelineInterface
    {
        if (! $container->has('config')) {
            throw Exception\ServiceNotFoundException::fromService('config');
        }

        /** @phpstan-var array<CommandBusConfig> $config */
        $config = $container->get('config');
        /** @phpstan-var CommandBusConfig $config */
        $config = $config[CommandBusInterface::class] ?? [];

        if ($config === []) {
            throw Exception\InvalidConfigurationException::fromMissingKey(
                sprintf(
                    'Configuration for key: %s was not found in the config service.',
                    '$config[' . CommandBusInterface::class . ']'
                )
            );
        }

        $middlewarePipe = new MiddlewarePipe();

        $config[ConfigProvider::MIDDLEWARE_PIPELINE_KEY] ??= [];

        if ($config[ConfigProvider::MIDDLEWARE_PIPELINE_KEY] !== []) {
            self::pipeMiddleware($container, $middlewarePipe, $config);
        }

        return $middlewarePipe;
    }

    /**
     * Pipe middleware into the CommandBus middleware pipeline.
     *
     * @phpstan-param CommandBusConfig $config
     */
    private static function pipeMiddleware(
        ContainerInterface $container,
        MiddlewarePipelineInterface $middlewarePipe,
        array $config
    ): MiddlewarePipelineInterface {
        /** @phpstan-var MiddlewarePipeSpec $middleware */
        $middleware = $config[ConfigProvider::MIDDLEWARE_PIPELINE_KEY] ?? [];
        if ($middleware === []) {
            return $middlewarePipe;
        }

        /**
         * Create a priority queue from the specifications
         *
         * @phpstan-var SplPriorityQueue<int, MiddlewareSpec> $queue
         */
        $queue = array_reduce(
            array_map(collectionMapperFactory('middleware'), $middleware),
            priorityQueueReducerFactory(),
            new SplPriorityQueue()
        );

        foreach ($queue as $spec) {
            if ($container->has($spec['middleware'])) {
                /** @var MiddlewareInterface $middleware */
                $middleware = $container->get($spec['middleware']);
                $middlewarePipe->pipe($middleware);
            }
        }

        return $middlewarePipe;
    }
}
