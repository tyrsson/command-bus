<?php

declare(strict_types=1);

namespace Webware\CommandBusIntegrationTest;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBus;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider;

use function array_merge;

#[CoversClass(CommandBus::class)]
#[CoversMethod(CommandBus::class, 'handle')]
/**
 * @phpstan-import-type ServiceManagerConfiguration from ConfigProvider
 * @phpstan-import-type CmdBusConfig from ConfigProvider
 * @phpstan-import-type CommandMap from ConfigProvider
 */
final class CmdBusTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;

    protected function setUp(): void
    {
        parent::setUp();
        $config                    = (new ConfigProvider())();
        $dependencies              = $config['dependencies'];
        $dependencies['factories'] = ($dependencies['factories'] ?? []) + [
            TestAssets\CommandHandler::class       => InvokableFactory::class,
            TestAssets\Command::class              => InvokableFactory::class,
            TestAssets\TestMiddlewareFirst::class  => InvokableFactory::class,
            TestAssets\TestMiddlewareSecond::class => InvokableFactory::class,
        ];
        $config[ConfigProvider::class][ConfigProvider::COMMAND_MAP_KEY] = [
            TestAssets\Command::class => TestAssets\CommandHandler::class,
        ];
        $middleware     = $config[ConfigProvider::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY];
        $testMiddleware = [
            [
                'middleware' => TestAssets\TestMiddlewareFirst::class,
                'priority'   => 100,
            ],
            [
                'middleware' => TestAssets\TestMiddlewareSecond::class,
                'priority'   => -1,
            ],
        ];
        $config[ConfigProvider::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY] = array_merge(
            $middleware,
            $testMiddleware
        );
        $dependencies['services']['config']                                     = $config;

        // @phpstan-ignore-next-line
        $this->container = new ServiceManager($dependencies);
    }

    public function testHandle(): void
    {
        /** @var CommandBusInterface $cmdBus */
        $cmdBus  = $this->container->get(CommandBusInterface::class);
        $command = new TestAssets\Command();
        $result  = $cmdBus->handle($command);

        $this->assertInstanceOf(CommandResult::class, $result);
        $this->assertSame($command, $result->getCommand());
        $this->assertSame(CommandStatus::Success, $result->getStatus());
        $this->assertEquals('Command-One', $result->getResult());
    }
}
