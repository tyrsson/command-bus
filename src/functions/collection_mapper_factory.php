<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Exception;

use function array_key_exists;

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
 */
function collectionMapperFactory(string $key): callable
{
    return static function (array $item) use ($key): array {
        if (! array_key_exists($key, $item)) {
            throw Exception\InvalidConfigurationException::fromInvalidType('$config[' . ConfigProvider::class . '][' . ConfigProvider::MIDDLEWARE_PIPELINE_KEY . ']', $item);
        }

        return $item;
    };
}
