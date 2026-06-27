<?php

declare(strict_types=1);

namespace Webware\CommandBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;
use PHPUnit\Framework\Attributes\Test;

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

    #[Test]
    public function invokeReturnsCorrectStructure(): void
    {
        $config = ($this->configProvider)();

        static::assertArrayHasKey('dependencies', $config);
        static::assertArrayHasKey(CommandBusInterface::class, $config);

        $dependencies = $config['dependencies'];
        static::assertArrayHasKey('aliases', $dependencies);
        static::assertArrayHasKey('factories', $dependencies);

        $cmdBusConfig = $config[CommandBusInterface::class];
        static::assertArrayHasKey(ConfigProvider::COMMAND_MAP_KEY, $cmdBusConfig);
        static::assertArrayHasKey(ConfigProvider::MIDDLEWARE_PIPELINE_KEY, $cmdBusConfig);
    }

    #[Test]
    public function getDependenciesReturnsCorrectAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedAliases, $dependencies['aliases']);
    }

    #[Test]
    public function getDependenciesReturnsCorrectFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedFactories, $dependencies['factories']);
    }

    #[Test]
    public function getDependenciesReturnsCorrectInvokables(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedInvokables, $dependencies['invokables']);
    }

    #[Test]
    public function getDependenciesStructure(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertCount(3, $dependencies);
        static::assertArrayHasKey('aliases', $dependencies);
        static::assertArrayHasKey('factories', $dependencies);
        static::assertArrayHasKey('invokables', $dependencies);
    }

    #[Test]
    public function getCommandMapReturnsEmptyArray(): void
    {
        $commandMap = $this->configProvider->getCommandMap();

        static::assertEmpty($commandMap);
    }

    #[Test]
    public function getMiddlewareReturnsDefaultConfiguration(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        static::assertSame($this->expectedMiddleware, $middleware);
    }

    #[Test]
    public function getMiddlewareStructure(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        static::assertCount(1, $middleware);

        static::assertSame(CommandHandlerMiddleware::class, $middleware[0]['middleware']);
    }

    #[Test]
    public function constants(): void
    {
        static::assertSame('command_map', ConfigProvider::COMMAND_MAP_KEY);
        static::assertSame('middleware_pipeline', ConfigProvider::MIDDLEWARE_PIPELINE_KEY);
        static::assertSame(1, ConfigProvider::DEFAULT_PRIORITY);
    }

    #[Test]
    public function configProviderCanBeInvokedMultipleTimes(): void
    {
        $config1 = ($this->configProvider)();
        $config2 = ($this->configProvider)();

        static::assertSame($config1, $config2);
    }
}
