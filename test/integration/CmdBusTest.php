<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_merge;

#[CoversClass(CmdBus::class)]
#[CoversMethod(CmdBus::class, 'handle')]
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
        /** @var CmdBusInterface $cmdBus */
        $cmdBus  = $this->container->get(CmdBusInterface::class);
        $command = new TestAssets\Command();
        $result  = $cmdBus->handle($command);

        $this->assertInstanceOf(CommandResult::class, $result);
        $this->assertSame($command, $result->getCommand());
        $this->assertSame(CommandStatus::Success, $result->getStatus());
        $this->assertEquals('Command-One', $result->getResult());
    }
}
