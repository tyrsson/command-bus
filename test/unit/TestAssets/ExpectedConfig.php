<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\TestAssets;

use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\CommandHandlerResolver;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\Container;
use PhpCmd\CmdBus\MiddlewarePipe;
use PhpCmd\CmdBus\MiddlewarePipelineInterface;
use PhpCmd\CmdBus\Handler\EmptyPipelineHandler;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;

final class ExpectedConfig
{
    public static function getExpectedDependencies(): array
    {
        return [
            'aliases'    => self::getExpectedAliases(),
            'factories'  => self::getExpectedFactories(),
            'invokables' => self::getExpectedInvokables(),
        ];
    }

    public static function getExpectedFactories(): array
    {
        return [
            CmdBus::class                   => Container\CmdBusFactory::class,
            CommandHandlerResolver::class   => Container\CommandHandlerResolverFactory::class,
            MiddlewarePipe::class           => Container\MiddlewarePipeFactory::class,
            CommandHandlerMiddleware::class => Container\CommandHandlerMiddlewareFactory::class,
        ];
    }

    public static function getExpectedAliases(): array
    {
        return [
            CmdBusInterface::class                 => CmdBus::class,
            MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
            CommandHandlerResolverInterface::class => CommandHandlerResolver::class,
        ];
    }

    public static function getExpectedInvokables(): array
    {
        return [
            EmptyPipelineHandler::class => EmptyPipelineHandler::class,
        ];
    }

    public static function getExpectedMiddleware(): array
    {
        return [
            [
                'middleware' => CommandHandlerMiddleware::class,
                'priority'   => 1,
            ],
        ];
    }
}
