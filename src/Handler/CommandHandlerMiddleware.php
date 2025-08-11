<?php

declare(strict_types=1);

namespace PhpCmd\Handler;

use PhpCmd\CommandInterface;
use PhpCmd\ConfigProvider;
use PhpCmd\MiddlewareInterface;
use Psr\Container\ContainerInterface;

final class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function process(CommandInterface $command): mixed
    {
        $map = $this->container->get(ConfigProvider::CONFIG_KEY)[ConfigProvider::COMMAND_MAP_KEY] ?? [];
        if (! $this->container->has($map[$command::class])) {
            throw new \RuntimeException('Command handler not found.');
        }
        return $this->container->get($map[$command::class])->handle($command);
    }
}
