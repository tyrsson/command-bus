<?php

declare(strict_types=1);

namespace PhpCmd;

/**
 * @phpstan-type CommandMap array<class-string, class-string>
 * @phpstan-type MiddlewareSpec array{
 *     middleware: class-string,
 *     priority: int
 * }
 * @phpstan-type MiddlewarePipeSpec array<MiddlewareSpec>
 * @phpstan-type CmdBusConfig array{
 *     command-map: CommandMap,
 *     middleware_pipeline: MiddlewarePipeSpec
 * }
 * @phpstan-type AbstractFactoriesConfiguration = array<
 *      array-key,
 *      class-string<\Laminas\ServiceManager\Factory\AbstractFactoryInterface>|\Laminas\ServiceManager\Factory\AbstractFactoryInterface
 * >
 * @phpstan-type DelegatorCallable = callable(\Psr\Container\ContainerInterface,string,callable():mixed,array<mixed>|null):mixed
 * @phpstan-type DelegatorsConfiguration = array<
 *      string,
 *      array<
 *          array-key,
 *          class-string<\Laminas\ServiceManager\Factory\DelegatorFactoryInterface>
 *          |class-string<object&DelegatorCallable>
 *          |\Laminas\ServiceManager\Factory\DelegatorFactoryInterface
 *          |DelegatorCallable
 *      >
 * >
 * @phpstan-type FactoryCallable = callable(\Psr\Container\ContainerInterface,string,array<mixed>|null):mixed
 * @phpstan-type FactoriesConfiguration = array<
 *      string,
 *      class-string<\Laminas\ServiceManager\Factory\FactoryInterface>|class-string<object&FactoryCallable>|\Laminas\ServiceManager\Factory\FactoryInterface|FactoryCallable|class-string
 * >
 * @phpstan-type InitializerCallable = callable(\Psr\Container\ContainerInterface,mixed):void
 * @phpstan-type InitializersConfiguration = array<
 *      array-key,
 *      class-string<\Laminas\ServiceManager\Initializer\InitializerInterface>|class-string<object&InitializerCallable>|\Laminas\ServiceManager\Initializer\InitializerInterface|InitializerCallable
 * >
 * @phpstan-type LazyServicesConfiguration = array{
 *      class_map?:array<string,class-string>,
 *      proxies_namespace?:non-empty-string,
 *      proxies_target_dir?:non-empty-string,
 *      write_proxy_files?:bool
 * }
 * @phpstan-type ServiceManagerConfiguration = array{
 *     abstract_factories?: AbstractFactoriesConfiguration,
 *     aliases?: array<string,string>,
 *     delegators?: DelegatorsConfiguration,
 *     factories?: FactoriesConfiguration,
 *     initializers?: InitializersConfiguration,
 *     invokables?: array<string,class-string>,
 *     lazy_services?: LazyServicesConfiguration,
 *     services?: array<string,mixed>,
 *     shared?:array<string,bool>,
 *     shared_by_default?: bool,
 *     ...
 * }
 */
final class ConfigProvider
{
    public const COMMAND_MAP_KEY         = 'command-map';
    public const DEFAULT_PRIORITY        = 1;
    public const MIDDLEWARE_PIPELINE_KEY = 'middleware_pipeline';

    /**
     * @phpstan-return array{
     *     dependencies: ServiceManagerConfiguration,
     *     PhpCmd\ConfigProvider: CmdBusConfig,
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            static::class  => [
                self::COMMAND_MAP_KEY         => $this->getCommandMap(),
                self::MIDDLEWARE_PIPELINE_KEY => $this->getMiddleware(),
            ],
        ];
    }

    /**
     * @phpstan-return array{
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>
     * }
     */
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

    /**
     * @phpstan-return CommandMap
     */
    public function getCommandMap(): array
    {
        return [
            // Command FQCN => CommandHandler FQCN
        ];
    }

    /**
     * @phpstan-return MiddlewarePipeSpec
     */
    public function getMiddleware(): array
    {
        return [
            [
                'middleware' => Middleware\CommandHandlerMiddleware::class,
                'priority'   => self::DEFAULT_PRIORITY,
            ],
        ];
    }
}
