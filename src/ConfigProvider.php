<?php

declare(strict_types=1);

namespace PhpCmd;

/**
 * @phpstan-type CommandMap array<class-string, class-string>
 *
 * @phpstan-type MiddlewareSpec array{
 *     middleware: class-string,
 *     priority: int
 * }
 *
 * @phpstan-type MiddlewarePipe MiddlewareSpec[]
 */
final class ConfigProvider
{
    public final const CONFIG_KEY              = 'php-cmd-bus';
    public final const COMMAND_MAP_KEY         = 'command-map';
    public final const DEFAULT_PRIORITY        = 1;
    public final const MIDDLEWARE_PIPELINE_KEY = 'middleware_pipeline';

    /**
     * @return array{
     *     dependencies: array{
     *         aliases: array<class-string, class-string>,
     *         factories: array<class-string, class-string>,
     *     PhpCmd\ConfigProvider:
     *     }
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies'    => $this->getDependencies(),
            self::CONFIG_KEY  => [
                self::COMMAND_MAP_KEY         => $this->getCommandMap(),
                self::MIDDLEWARE_PIPELINE_KEY => $this->getMiddleware(),
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                CmdBusInterface::class             => CmdBus::class,
                MiddlewarePipelineInterface::class => MiddlewarePipe::class,
            ],
            'factories' => [
                CmdBus::class                              => Container\CmdBusFactory::class,
                MiddlewarePipe::class                      => Container\MiddlewarePipeFactory::class,
                Middleware\CommandHandlerMiddleware::class => Container\CommandHandlerMiddlewareFactory::class,
            ],
        ];
    }

    public function getCommandMap(): array
    {
        return [
            // Command FQCN => CommandHandler FQCN
        ];
    }

    public function getMiddleware(): array
    {
        return [
            [
                'middleware' => Middleware\CommandHandlerMiddleware::class,
                'priority'   => self::DEFAULT_PRIORITY,
            ]
        ];
    }
}
