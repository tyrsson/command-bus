<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\TestAssets;

use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\CommandHandlerResolver;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\Container;
use PhpCmd\CmdBus\Handler\EmptyPipelineHandler;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewarePipe;
use PhpCmd\CmdBus\MiddlewarePipelineInterface;

final class ExpectedConfig
{
    /**
     * @return array{
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>,
     *     invokables: array<class-string, class-string>
     * }
     */
    public static function getExpectedDependencies(): array
    {
        return [
            'aliases'    => self::getExpectedAliases(),
            'factories'  => self::getExpectedFactories(),
            'invokables' => self::getExpectedInvokables(),
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedFactories(): array
    {
        return [
            CmdBus::class                   => Container\CmdBusFactory::class,
            CommandHandlerResolver::class   => Container\CommandHandlerResolverFactory::class,
            MiddlewarePipe::class           => Container\MiddlewarePipeFactory::class,
            CommandHandlerMiddleware::class => Container\CommandHandlerMiddlewareFactory::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedAliases(): array
    {
        return [
            CmdBusInterface::class                 => CmdBus::class,
            MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
            CommandHandlerResolverInterface::class => CommandHandlerResolver::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedInvokables(): array
    {
        return [
            EmptyPipelineHandler::class => EmptyPipelineHandler::class,
        ];
    }

    /**
     * @return array<array{middleware: class-string, priority: int}>
     */
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
