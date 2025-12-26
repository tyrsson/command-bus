<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\TestAssets;

use Webware\CommandBus\CommandBus;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\CommandHandlerResolver;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\Container;
use Webware\CommandBus\Handler\EmptyPipelineHandler;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

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
            CommandBus::class               => Container\CommandBusFactory::class,
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
            CommandBusInterface::class             => CommandBus::class,
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
