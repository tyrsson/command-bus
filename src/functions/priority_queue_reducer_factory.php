<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use SplPriorityQueue;

use function is_int;

use const PHP_INT_MAX;

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
 */
function priority_queue_reducer_factory(): callable
{
    $serial = PHP_INT_MAX;

    return static function (SplPriorityQueue $queue, array $item) use (&$serial): SplPriorityQueue {
        // @mago-expect lint:no-isset
        $priority = isset($item['priority']) && is_int($item['priority'])
            ? $item['priority']
            : 1;
        $queue->insert($item, [$priority, $serial]);
        $serial -= 1;

        return $queue;
    };
}
