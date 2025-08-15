<?php

declare(strict_types=1);

namespace PhpCmdIntegrationTest;

use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PhpCmd\CmdBus;
use PhpCmd\CmdBusInterface;
use PhpCmd\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PSpell\Config;
use Psr\Container\ContainerInterface;

#[CoversClass(CmdBus::class)]
#[CoversMethod(CmdBus::class, 'handle')]
final class CmdBusTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $config = (new ConfigProvider())();
        $dependencies = $config['dependencies'];
        $dependencies['factories'] += [
            TestAssets\CommandHandler::class => InvokableFactory::class,
            TestAssets\Command::class        => InvokableFactory::class,
        ];
        $config[ConfigProvider::class][ConfigProvider::COMMAND_MAP_KEY] = [
            TestAssets\Command::class => TestAssets\CommandHandler::class,
        ];
        $dependencies['services']['config'] = $config;

        $this->container = new ServiceManager($dependencies);
    }

    public function testHandle(): void
    {
        $cmdBus = $this->container->get(CmdBusInterface::class);
        $command = new TestAssets\Command();
        $result = $cmdBus->handle($command);
        $this->assertEquals($command->name, $result);
    }
}
