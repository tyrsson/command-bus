<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest;

use Laminas\ServiceManager\Factory\InvokableFactory;
use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\CommandHandlerResolver;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\ConfigProvider;
use PhpCmd\CmdBus\Container\CmdBusFactory;
use PhpCmd\CmdBus\Container\CommandHandlerMiddlewareFactory;
use PhpCmd\CmdBus\Container\CommandHandlerResolverFactory;
use PhpCmd\CmdBus\Container\MiddlewarePipeFactory;
use PhpCmd\CmdBus\Handler\EmptyPipelineHandler;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use PhpCmd\CmdBus\Middleware\PostCommandHandlerMiddleware;
use PhpCmd\CmdBus\Middleware\PreCommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewarePipe;
use PhpCmd\CmdBus\MiddlewarePipelineInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configProvider = new ConfigProvider();
    }

    public function testInvokeReturnsCorrectStructure(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(ConfigProvider::class, $config);

        $dependencies = $config['dependencies'];
        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);

        $cmdBusConfig = $config[ConfigProvider::class];
        $this->assertArrayHasKey(ConfigProvider::COMMAND_MAP_KEY, $cmdBusConfig);
        $this->assertArrayHasKey(ConfigProvider::MIDDLEWARE_PIPELINE_KEY, $cmdBusConfig);
    }

    public function testGetDependenciesReturnsCorrectAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $expectedAliases = [
            CmdBusInterface::class                 => CmdBus::class,
            MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
            CommandHandlerResolverInterface::class => CommandHandlerResolver::class,
        ];

        $this->assertSame($expectedAliases, $dependencies['aliases']);
    }

    public function testGetDependenciesReturnsCorrectFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $expectedFactories = [
            CmdBus::class                       => CmdBusFactory::class,
            CommandHandlerResolver::class       => CommandHandlerResolverFactory::class,
            EmptyPipelineHandler::class         => InvokableFactory::class,
            MiddlewarePipe::class               => MiddlewarePipeFactory::class,
            CommandHandlerMiddleware::class     => CommandHandlerMiddlewareFactory::class,
            PostCommandHandlerMiddleware::class => InvokableFactory::class,
            PreCommandHandlerMiddleware::class  => InvokableFactory::class,
        ];

        $this->assertSame($expectedFactories, $dependencies['factories']);
    }

    public function testGetDependenciesStructure(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertCount(2, $dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
    }

    public function testGetCommandMapReturnsEmptyArray(): void
    {
        $commandMap = $this->configProvider->getCommandMap();

        $this->assertEmpty($commandMap);
    }

    public function testGetMiddlewareReturnsDefaultConfiguration(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        $expectedMiddleware = [
            [
                'middleware' => PreCommandHandlerMiddleware::class,
                'priority'   => ConfigProvider::DEFAULT_PRIORITY,
            ],
            [
                'middleware' => CommandHandlerMiddleware::class,
                'priority'   => ConfigProvider::DEFAULT_PRIORITY,
            ],
            [
                'middleware' => PostCommandHandlerMiddleware::class,
                'priority'   => ConfigProvider::DEFAULT_PRIORITY,
            ],
        ];

        $this->assertSame($expectedMiddleware, $middleware);
    }

    public function testGetMiddlewareStructure(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        $this->assertCount(3, $middleware);

        foreach ($middleware as $middlewareSpec) {
            $this->assertArrayHasKey('middleware', $middlewareSpec);
            $this->assertArrayHasKey('priority', $middlewareSpec);
            $this->assertSame(1, $middlewareSpec['priority']);
        }

        $this->assertSame(PreCommandHandlerMiddleware::class, $middleware[0]['middleware']);
        $this->assertSame(CommandHandlerMiddleware::class, $middleware[1]['middleware']);
        $this->assertSame(PostCommandHandlerMiddleware::class, $middleware[2]['middleware']);
    }

    public function testConstants(): void
    {
        $this->assertSame('command-map', ConfigProvider::COMMAND_MAP_KEY);
        $this->assertSame('middleware_pipeline', ConfigProvider::MIDDLEWARE_PIPELINE_KEY);
        $this->assertSame(1, ConfigProvider::DEFAULT_PRIORITY);
    }

    public function testConfigProviderCanBeInvokedMultipleTimes(): void
    {
        $config1 = ($this->configProvider)();
        $config2 = ($this->configProvider)();

        $this->assertSame($config1, $config2);
    }

    public function testAllMethodsReturnConsistentTypes(): void
    {
        // Test that all methods return the expected types consistently
        $dependencies1 = $this->configProvider->getDependencies();
        $dependencies2 = $this->configProvider->getDependencies();
        $this->assertSame($dependencies1, $dependencies2);

        $commandMap1 = $this->configProvider->getCommandMap();
        $commandMap2 = $this->configProvider->getCommandMap();
        $this->assertSame($commandMap1, $commandMap2);

        $middleware1 = $this->configProvider->getMiddleware();
        $middleware2 = $this->configProvider->getMiddleware();
        $this->assertSame($middleware1, $middleware2);
    }
}
