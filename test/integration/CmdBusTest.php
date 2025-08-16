<?php

declare(strict_types=1);

namespace PhpCmdIntegrationTest;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpCmd\CmdBus;
use PhpCmd\CmdBusInterface;
use PhpCmd\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(CmdBus::class)]
#[CoversMethod(CmdBus::class, 'handle')]
/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-import-type FactoriesConfiguration from ServiceManager
 * @psalm-import-type CmdBusConfig from ConfigProvider
 * @psalm-import-type CmdBusCommandMap from ConfigProvider
 */
final class CmdBusTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;

    protected function setUp(): void
    {
        parent::setUp();
        /** @psalm-var array{dependencies: ServiceManagerConfiguration} $config*/
        $config                     = (new ConfigProvider())();
        $dependencies               = $config['dependencies'];
        $dependencies['factories'] += [
            TestAssets\CommandHandler::class => InvokableFactory::class,
            TestAssets\Command::class        => InvokableFactory::class,
        ];
        /** @psalm-var CmdBusCommandMap */
        $config[ConfigProvider::class][ConfigProvider::COMMAND_MAP_KEY] = [
            TestAssets\Command::class => TestAssets\CommandHandler::class,
        ];
        $dependencies['services']['config']                             = $config;

        $this->container = new ServiceManager($dependencies);
    }

    public function testHandle(): void
    {
        /** @var CmdBusInterface $cmdBus */
        $cmdBus  = $this->container->get(CmdBusInterface::class);
        $command = new TestAssets\Command();
        $result  = $cmdBus->handle($command);
        $this->assertEquals($command->name, $result);
    }
}
