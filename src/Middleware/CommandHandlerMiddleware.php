<?php

declare(strict_types=1);

namespace PhpCmd\Middleware;

use PhpCmd\CommandHandlerInterface;
use PhpCmd\CommandInterface;
use PhpCmd\ConfigProvider;
use PhpCmd\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @phpstan-import-type CmdBusConfig from ConfigProvider
 * @phpstan-import-type CommandMap from ConfigProvider
 */
final class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function process(CommandInterface $command, CommandHandlerInterface $next): mixed
    {
        /** @phpstan-var array<CmdBusConfig> */
        $config = $this->container->get('config');
        /** @phpstan-var CmdBusConfig $config */
        $config = $config[ConfigProvider::class] ?? [];
        /** @phpstan-var CommandMap $map */
        $map = $config[ConfigProvider::COMMAND_MAP_KEY] ?? [];
        /** @phpstan-var class-string $handlerClass */
        $handlerClass = $map[$command::class];
        if (! $this->container->has($handlerClass)) {
            throw new RuntimeException('Command handler not found.');
        }
        /** @var CommandHandlerInterface $handler */
        $handler = $this->container->get($handlerClass);
        // todo: decide how to handle the result
        $handler->handle($command);
        return $next->handle($command);
    }
}
