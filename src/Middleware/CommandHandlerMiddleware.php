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
 * @psalm-import-type CmdBusConfig from ConfigProvider
 * @psalm-import-type CmdBusCommandMap from ConfigProvider
 */
final class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function process(CommandInterface $command): mixed
    {
        /** @psalm-var array<CmdBusConfig> */
        $config = $this->container->get('config');
        /** @psalm-var CmdBusConfig $config */
        $config = $config[ConfigProvider::class] ?? [];
        /** @psalm-var CmdBusCommandMap $map */
        $map = $config[ConfigProvider::COMMAND_MAP_KEY] ?? [];
        /** @psalm-var class-string $handlerClass */
        $handlerClass = $map[$command::class];
        if (! $this->container->has($handlerClass)) {
            throw new RuntimeException('Command handler not found.');
        }
        /** @var CommandHandlerInterface $handler */
        $handler = $this->container->get($handlerClass);
        return $handler->handle($command);
    }
}
