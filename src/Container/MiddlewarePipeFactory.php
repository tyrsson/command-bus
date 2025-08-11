<?php

declare(strict_types=1);

namespace PhpCmd\Container;

use PhpCmd\ConfigProvider;
use PhpCmd\Exception;
use PhpCmd\MiddlewarePipe;
use PhpCmd\MiddlewarePipelineInterface;
use Psr\Container\ContainerInterface;
use SplPriorityQueue;

use function array_key_exists;
use function array_map;
use function array_reduce;
use function is_array;
use function is_int;

use const PHP_INT_MAX;

final class MiddlewarePipeFactory
{
    public function __invoke(ContainerInterface $container): MiddlewarePipelineInterface
    {
        if (! $container->has('config')) {
            throw Exception\ServiceNotFoundException::fromService('config');
        }

        $config = $container->get('config')[ConfigProvider::CONFIG_KEY] ?? [];

        if ($config === []) {
            throw Exception\InvalidConfigurationException::fromMissingKey(
                sprintf(
                    'Configuration for key: %s was not found in the config service.',
                    '$config[' . ConfigProvider::CONFIG_KEY . ']'
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

    private static function pipeMiddleware(
        ContainerInterface $container,
        MiddlewarePipelineInterface $middlewarePipe,
        array $config
    ) {
        $middleware = $config[ConfigProvider::MIDDLEWARE_PIPELINE_KEY] ?? [];
        if ($middleware === []) {
            return $middlewarePipe;
        }

        /**
         * Create a priority queue from the specifications
         *
         * @psalm-var SplPriorityQueue<int, MiddlewareSpec> $queue
         */
        $queue = array_reduce(
            array_map(self::createCollectionMapper(), $middleware),
            self::createPriorityQueueReducer(),
            new SplPriorityQueue()
        );

        foreach ($queue as $spec) {
            if ($container->has($spec['middleware'])) {
                $middlewarePipe->pipe($container->get($spec['middleware']));
            }
        }
    }

    /**
     * Create the collection mapping function.
     *
     * Returns a callable with the following signature:
     *
     * <code>
     * function (array|string $item) : array
     * </code>
     *
     * If the 'middleware' value is missing, or not viable as middleware, it
     * raises an exception, to ensure the pipeline is built correctly.
     *
     * @return callable(MiddlewareSpec): MiddlewareSpec
     * @throws InvalidArgumentException
     */
    private static function createCollectionMapper(): callable
    {
        return static function ($item): array {
            if (! is_array($item) || ! array_key_exists('middleware', $item)) {
                throw Exception\InvalidConfigurationException::fromInvalidType(
                    '$config[' . ConfigProvider::CONFIG_KEY . '][' . ConfigProvider::MIDDLEWARE_PIPELINE_KEY . ']',
                    $item
                );
            }
            return $item;
        };
    }

    /**
     * Create reducer function that will reduce an array to a priority queue.
     *
     * Creates and returns a function with the signature:
     *
     * <code>
     * function (SplQueue $queue, array $item) : SplQueue
     * </code>
     *
     * The function is useful to reduce an array of pipeline middleware to a
     * priority queue.
     *
     * @return callable(SplPriorityQueue, MiddlewareSpec): SplPriorityQueue
     */
    private static function createPriorityQueueReducer(): callable
    {
        // insure that items with the same priority are enqueued in the order
        // in which they are inserted.
        $serial = PHP_INT_MAX;
        return static function (SplPriorityQueue $queue, array $item) use (&$serial): SplPriorityQueue {
            $priority = isset($item['priority']) && is_int($item['priority'])
                ? $item['priority']
                : 1;
            $queue->insert($item, [$priority, $serial]);
            $serial -= 1;
            return $queue;
        };
    }
}
