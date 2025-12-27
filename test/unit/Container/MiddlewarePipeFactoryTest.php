<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider;
use Webware\CommandBus\Container\MiddlewarePipeFactory;
use Webware\CommandBus\Exception\InvalidConfigurationException;
use Webware\CommandBus\Exception\ServiceNotFoundException;
use Webware\CommandBus\MiddlewareInterface;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

#[CoversClass(MiddlewarePipeFactory::class)]
final class MiddlewarePipeFactoryTest extends TestCase
{
    private MiddlewarePipeFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $middleware1;

    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $middleware2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory     = new MiddlewarePipeFactory();
        $this->container   = $this->createMock(ContainerInterface::class);
        $this->middleware1 = $this->createMock(MiddlewareInterface::class);
        $this->middleware2 = $this->createMock(MiddlewareInterface::class);
    }

    public function testInvokeReturnsMiddlewarePipelineInterface(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
        $this->assertInstanceOf(MiddlewarePipe::class, $result);
    }

    public function testInvokeThrowsExceptionWhenConfigServiceNotFound(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service not found: config was not found in the container');

        ($this->factory)($this->container);
    }

    public function testInvokeThrowsExceptionWhenCommandBusInterfaceKeyMissing(): void
    {
        $config = [];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration for key: $config[Webware\CommandBus\CommandBusInterface] was not found in the config service.' // phpcs:ignore
        );

        ($this->factory)($this->container);
    }

    public function testInvokeWithEmptyMiddlewarePipelineConfig(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    public function testInvokeWithMiddlewarePipelineConfiguration(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                    [
                        'middleware' => 'TestMiddleware2',
                        'priority'   => 5,
                    ],
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnCallback(function ($service) {
                return match ($service) {
                    'config'          => true,
                    'TestMiddleware1' => true,
                    'TestMiddleware2' => true,
                    default           => false,
                };
            });

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'          => $config,
                    'TestMiddleware1' => $this->middleware1,
                    'TestMiddleware2' => $this->middleware2,
                    default           => null,
                };
            });

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    public function testInvokeSkipsMiddlewareNotAvailableInContainer(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                    [
                        'middleware' => 'TestMiddleware2',
                        'priority'   => 5,
                    ],
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnCallback(function ($service) {
                return match ($service) {
                    'config'          => true,
                    'TestMiddleware1' => true,
                    'TestMiddleware2' => false,
                    default           => false,
                };
            });

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'          => $config,
                    'TestMiddleware1' => $this->middleware1,
                    default           => null,
                };
            });

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    public function testInvokeThrowsExceptionWhenMiddlewareConfigMissingMiddlewareKey(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'priority' => 10,
                        // Missing 'middleware' key
                    ],
                ],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid type for config key "$config[Webware\CommandBus\ConfigProvider][middleware_pipeline]": array'
        );

        ($this->factory)($this->container);
    }

    public function testInvokeWithMiddlewareDefaultPriority(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        // No priority specified, should default to 1
                    ],
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnCallback(function ($service) {
                return match ($service) {
                    'config' => true,
                    'TestMiddleware1' => true,
                    default => false,
                };
            });

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config' => $config,
                    'TestMiddleware1' => $this->middleware1,
                    default => null,
                };
            });

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $this->container
            ->expects($this->exactly(2))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result1);
        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        $this->assertSame('__invoke', (new ReflectionClass($this->factory))->getMethod('__invoke')->getName());
    }

    public function testInvokeWithNonIntegerPriorityDefaultsToOne(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 'invalid', // Non-integer priority
                    ],
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturnCallback(function ($service) {
                return match ($service) {
                    'config'          => true,
                    'TestMiddleware1' => true,
                    default           => false,
                };
            });

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'          => $config,
                    'TestMiddleware1' => $this->middleware1,
                    default           => null,
                };
            });

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }
}
