<?php

declare(strict_types=1);

namespace Webware\CommandBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, 'getCommandMap')]
#[CoversMethod(ConfigProvider::class, 'getMiddleware')]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    /** @var array<class-string, class-string> */
    private array $expectedAliases;

    /** @var array<class-string, class-string> */
    private array $expectedFactories;

    /** @var array<class-string, class-string> */
    private array $expectedInvokables;

    /** @var array<array{middleware: class-string, priority: int}> */
    private array $expectedMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configProvider     = new ConfigProvider();
        $this->expectedAliases    = TestAssets\ExpectedConfig::getExpectedAliases();
        $this->expectedFactories  = TestAssets\ExpectedConfig::getExpectedFactories();
        $this->expectedInvokables = TestAssets\ExpectedConfig::getExpectedInvokables();
        $this->expectedMiddleware = TestAssets\ExpectedConfig::getExpectedMiddleware();
    }

    public function testInvokeReturnsCorrectStructure(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(CommandBusInterface::class, $config);

        $dependencies = $config['dependencies'];
        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);

        $cmdBusConfig = $config[CommandBusInterface::class];
        $this->assertArrayHasKey(ConfigProvider::COMMAND_MAP_KEY, $cmdBusConfig);
        $this->assertArrayHasKey(ConfigProvider::MIDDLEWARE_PIPELINE_KEY, $cmdBusConfig);
    }

    public function testGetDependenciesReturnsCorrectAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertSame($this->expectedAliases, $dependencies['aliases']);
    }

    public function testGetDependenciesReturnsCorrectFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertSame($this->expectedFactories, $dependencies['factories']);
    }

    public function testGetDependenciesReturnsCorrectInvokables(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertSame($this->expectedInvokables, $dependencies['invokables']);
    }

    public function testGetDependenciesStructure(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertCount(3, $dependencies);
        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey('invokables', $dependencies);
    }

    public function testGetCommandMapReturnsEmptyArray(): void
    {
        $commandMap = $this->configProvider->getCommandMap();

        $this->assertEmpty($commandMap);
    }

    public function testGetMiddlewareReturnsDefaultConfiguration(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        $this->assertSame($this->expectedMiddleware, $middleware);
    }

    public function testGetMiddlewareStructure(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        $this->assertCount(1, $middleware);

        $this->assertSame(CommandHandlerMiddleware::class, $middleware[0]['middleware']);
    }

    public function testConstants(): void
    {
        $this->assertSame('command_map', ConfigProvider::COMMAND_MAP_KEY);
        $this->assertSame('middleware_pipeline', ConfigProvider::MIDDLEWARE_PIPELINE_KEY);
        $this->assertSame(1, ConfigProvider::DEFAULT_PRIORITY);
    }

    public function testConfigProviderCanBeInvokedMultipleTimes(): void
    {
        $config1 = ($this->configProvider)();
        $config2 = ($this->configProvider)();

        $this->assertSame($config1, $config2);
    }
}
