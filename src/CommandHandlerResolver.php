<?php

declare(strict_types=1);

namespace Webware\CommandBus;

use Override;
use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Exception\InvalidConfigurationException;

use function array_key_exists;

/**
 * @phpstan-import-type CmdBusConfig from ConfigProvider
 * @phpstan-import-type CommandMap from ConfigProvider
 */
final class CommandHandlerResolver implements CommandHandlerResolverInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function __invoke(CommandInterface $command): CommandHandlerInterface
    {
        return $this->resolve($command);
    }

    #[Override]
    public function resolve(CommandInterface $command): CommandHandlerInterface
    {
        /** @phpstan-var array<CmdBusConfig> */
        $config = $this->container->get('config');
        /** @phpstan-var CmdBusConfig $config */
        $config = $config[ConfigProvider::class] ?? [];
        /** @phpstan-var CommandMap $map */
        $map = $config[ConfigProvider::COMMAND_MAP_KEY] ?? [];
        if (! array_key_exists($command::class, $map)) {
            throw InvalidConfigurationException::fromUnMappedCommand($command::class);
        }
        /** @phpstan-var class-string $handlerClass */
        $handlerClass = $map[$command::class];
        if (! $this->container->has($handlerClass)) {
            throw InvalidConfigurationException::fromHandlerNotFound($handlerClass);
        }
        $handler = $this->container->get($handlerClass);
        if (! $handler instanceof CommandHandlerInterface) {
            throw InvalidConfigurationException::fromInvalidHandler($handlerClass, $handler);
        }
        return $handler;
    }
}
