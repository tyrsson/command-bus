<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result1);
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    #[Test]
    public function factoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        static::assertSame(
            '__invoke',
            new ReflectionClass($this->factory)
                ->getMethod('__invoke')
                ->getName(),
        );
    }

    #[Test]
    public function invokeReturnsMiddlewarePipelineInterface(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
        static::assertInstanceOf(MiddlewarePipe::class, $result);
    }

    #[Test]
    public function invokeSkipsMiddlewareNotAvailableInContainer(): void
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
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => true,
                'TestMiddleware1' => true,
                'TestMiddleware2' => false,
                default           => false,
            });

        $this->container
            ->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    #[Test]
    public function invokeThrowsExceptionWhenCommandBusInterfaceKeyMissing(): void
    {
        $config = [];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Configuration for key: $config[Webware\CommandBus\CommandBusInterface] was not found in the config service.', // phpcs:ignore
        );

        ($this->factory)($container);
    }

    #[Test]
    public function invokeThrowsExceptionWhenConfigServiceNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service not found: config was not found in the container');

        ($this->factory)($container);
    }

    #[Test]
    public function invokeThrowsExceptionWhenMiddlewareConfigMissingMiddlewareKey(): void
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

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid type for config key "$config[Webware\CommandBus\ConfigProvider][middleware_pipeline]": array',
        );

        ($this->factory)($container);
    }

    #[Test]
    public function invokeWithEmptyMiddlewarePipelineConfig(): void
    {
        $config = [
            CommandBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    #[Test]
    public function invokeWithMiddlewareDefaultPriority(): void
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
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => true,
                'TestMiddleware1' => true,
                default           => false,
            });

        $this->container
            ->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    #[Test]
    public function invokeWithMiddlewarePipelineConfiguration(): void
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
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => true,
                'TestMiddleware1' => true,
                'TestMiddleware2' => true,
                default           => false,
            });

        $this->container
            ->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                'TestMiddleware2' => $this->middleware2,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    #[Test]
    public function invokeWithNonIntegerPriorityDefaultsToOne(): void
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
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => true,
                'TestMiddleware1' => true,
                default           => false,
            });

        $this->container
            ->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory     = new MiddlewarePipeFactory();
        $this->container   = $this->createStub(ContainerInterface::class);
        $this->middleware1 = $this->createStub(MiddlewareInterface::class);
        $this->middleware2 = $this->createStub(MiddlewareInterface::class);
    }
}
