# CommandHandlerFactory

Factory class responsible for resolving and creating command handlers based on command-to-handler mappings.

## Overview

`CommandHandlerFactory` is a key component that bridges commands with their corresponding handlers. It uses the dependency injection container to resolve handler instances based on configured command mappings, ensuring proper handler instantiation and dependency resolution.

## Class Definition

```php
final class CommandHandlerFactory
{
    public function __construct(private readonly ContainerInterface $container);
    public function __invoke(CommandInterface $command): CommandHandlerInterface;
    public function factory(CommandInterface $command): CommandHandlerInterface;
}
```

## Constructor

### __construct(ContainerInterface $container)

Creates a new factory instance with access to the dependency injection container.

**Parameters:**

- `$container` - PSR-11 container for resolving handler instances

## Methods

### __invoke(CommandInterface $command): CommandHandlerInterface

Magic method that allows the factory to be called as a function.

**Parameters:**

- `$command` - The command instance that needs a handler

**Returns:**

- `CommandHandlerInterface` - The resolved handler instance

### factory(CommandInterface $command): CommandHandlerInterface

Resolves and returns the appropriate handler for the given command.

**Parameters:**

- `$command` - The command instance that needs a handler

**Returns:**

- `CommandHandlerInterface` - The resolved handler instance

**Throws:**

- `InvalidConfigurationException` - When no handler mapping exists
- `ServiceNotFoundException` - When the handler service cannot be resolved

## Usage Examples

### Basic Usage

```php
use Webware\CommandBus\CommandHandlerFactory;
use Psr\Container\ContainerInterface;

// Setup (usually handled by the container)
$factory = new CommandHandlerFactory($container);

// Resolve handler for a command
$command = new CreateUserCommand('user@example.com', 'username');
$handler = $factory($command); // Using __invoke
// or
$handler = $factory->factory($command); // Using factory method

// Execute the command
$result = $handler->handle($command);
```

### Custom Factory Usage

```php
class CustomCommandProcessor
{
    public function __construct(
        private CommandHandlerFactory $handlerFactory,
        private LoggerInterface $logger
    ) {}

    public function processCommand(CommandInterface $command): mixed
    {
        $this->logger->info('Processing command', [
            'command' => $command::class
        ]);

        try {
            $handler = $this->handlerFactory->factory($command);

            $this->logger->debug('Handler resolved', [
                'handler' => $handler::class
            ]);

            $result = $handler->handle($command);

            $this->logger->info('Command processed successfully');
            return $result;

        } catch (InvalidConfigurationException $e) {
            $this->logger->error('No handler found for command', [
                'command' => $command::class,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

## Configuration

### Command-to-Handler Mapping

The factory relies on command-to-handler mappings configured in your application:

```php
// config/autoload/commandbus.global.php
return [
    Webware\CommandBus\CommandBusInterface::class => [
        'command-map' => [
            App\Command\CreateUserCommand::class => App\Handler\CreateUserHandler::class,
            App\Command\UpdateUserCommand::class => App\Handler\UpdateUserHandler::class,
            App\Command\DeleteUserCommand::class => App\Handler\DeleteUserHandler::class,
        ],
    ],
];
```

### Handler Service Registration

Handlers must be registered in the dependency injection container:

```php
// In a autoloaded config file or ConfigProvider
return [
    'dependencies' => [
        'factories' => [
            App\Handler\CreateUserHandler::class => App\Factory\CreateUserHandlerFactory::class,
            App\Handler\UpdateUserHandler::class => App\Factory\UpdateUserHandlerFactory::class,
            App\Handler\DeleteUserHandler::class => App\Factory\DeleteUserHandlerFactory::class,
        ],
    ],
];
```

### Factory Registration

The CommandHandlerFactory itself must be registered:

```php
// Automatically registered by ConfigProvider
'factories' => [
    CommandHandlerFactory::class => Container\CommandHandlerFactoryFactory::class,
],
```

## Error Handling

### Missing Command Mapping

```php
try {
    $handler = $factory->factory($command);
} catch (InvalidConfigurationException $e) {
    // Command not mapped to any handler
    $this->logger->error('Unmapped command', [
        'command' => $command::class,
        'available_mappings' => array_keys($this->getCommandMap())
    ]);

    throw new CommandException(
        sprintf('No handler configured for command %s', $command::class),
        0,
        $e
    );
}
```

### Handler Service Not Found

```php
try {
    $handler = $factory->factory($command);
} catch (ServiceNotFoundException $e) {
    // Handler class not registered in container
    $this->logger->error('Handler service not found', [
        'command' => $command::class,
        'handler_class' => $this->getHandlerClass($command),
        'error' => $e->getMessage()
    ]);

    throw new CommandException(
        sprintf('Handler service not available for command %s', $command::class),
        0,
        $e
    );
}
```

## Advanced Usage

### Decorating Handler Factory

```php
class DecoratingCommandHandlerFactory extends CommandHandlerFactory
{
    public function __construct(
        ContainerInterface $container,
        private array $decorators = []
    ) {
        parent::__construct($container);
    }

    public function factory(CommandInterface $command): CommandHandlerInterface
    {
        $handler = parent::factory($command);

        // Apply decorators
        foreach ($this->decorators as $decoratorClass) {
            if ($this->container->has($decoratorClass)) {
                $decorator = $this->container->get($decoratorClass);
                if ($decorator instanceof HandlerDecoratorInterface) {
                    $handler = $decorator->decorate($handler);
                }
            }
        }

        return $handler;
    }
}
```

### Handler Discovery

```php
class HandlerDiscovery
{
    public function __construct(
        private CommandHandlerFactory $factory,
        private ContainerInterface $container
    ) {}

    public function discoverAvailableHandlers(): array
    {
        $availableHandlers = [];
        $commandMap = $this->getCommandMap();

        foreach ($commandMap as $commandClass => $handlerClass) {
            try {
                if ($this->container->has($handlerClass)) {
                    $availableHandlers[$commandClass] = $handlerClass;
                }
            } catch (\Throwable $e) {
                // Handler not available
            }
        }

        return $availableHandlers;
    }

    public function validateHandlerConfiguration(): array
    {
        $issues = [];
        $commandMap = $this->getCommandMap();

        foreach ($commandMap as $commandClass => $handlerClass) {
            // Check if command class exists
            if (!class_exists($commandClass)) {
                $issues[] = "Command class does not exist: {$commandClass}";
                continue;
            }

            // Check if handler class exists
            if (!class_exists($handlerClass)) {
                $issues[] = "Handler class does not exist: {$handlerClass}";
                continue;
            }

            // Check if handler is registered in container
            if (!$this->container->has($handlerClass)) {
                $issues[] = "Handler not registered in container: {$handlerClass}";
                continue;
            }

            // Try to create a mock command and resolve handler
            try {
                $mockCommand = $this->createMockCommand($commandClass);
                $this->factory->factory($mockCommand);
            } catch (\Throwable $e) {
                $issues[] = "Handler resolution failed for {$commandClass}: {$e->getMessage()}";
            }
        }

        return $issues;
    }
}
```

## Testing

### Unit Testing

```php
class CommandHandlerFactoryTest extends TestCase
{
    private CommandHandlerFactory $factory;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new CommandHandlerFactory($this->container);
    }

    public function testFactoryResolvesHandler(): void
    {
        $command = new CreateUserCommand('test@example.com', 'testuser');
        $handler = $this->createMock(CommandHandlerInterface::class);

        $this->container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn([
                ConfigProvider::class => [
                    'command-map' => [
                        CreateUserCommand::class => CreateUserHandler::class
                    ]
                ]
            ]);

        $this->container->expects($this->once())
            ->method('has')
            ->with(CreateUserHandler::class)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with(CreateUserHandler::class)
            ->willReturn($handler);

        $result = $this->factory->factory($command);

        $this->assertSame($handler, $result);
    }

    public function testFactoryThrowsExceptionForUnmappedCommand(): void
    {
        $command = new UnmappedCommand();

        $this->container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn([
                ConfigProvider::class => [
                    'command-map' => []
                ]
            ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->factory->factory($command);
    }
}
```

### Integration Testing

```php
class CommandHandlerFactoryIntegrationTest extends TestCase
{
    private ContainerInterface $container;
    private CommandHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createConfiguredContainer();
        $this->factory = $this->container->get(CommandHandlerFactory::class);
    }

    public function testFactoryResolvesRealHandler(): void
    {
        $command = new CreateUserCommand('test@example.com', 'testuser');
        $handler = $this->factory->factory($command);

        $this->assertInstanceOf(CreateUserHandler::class, $handler);
        $this->assertInstanceOf(CommandHandlerInterface::class, $handler);
    }

    public function testHandlerCanExecuteCommand(): void
    {
        $command = new CreateUserCommand('test@example.com', 'testuser');
        $handler = $this->factory->factory($command);

        $result = $handler->handle($command);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->getEmail());
    }
}
```

## Best Practices

### 1. Use Specific Handler Classes

```php
// ✅ Good - specific handler for each command
'command-map' => [
    CreateUserCommand::class => CreateUserHandler::class,
    UpdateUserCommand::class => UpdateUserHandler::class,
]

// ❌ Bad - generic handler for multiple commands
'command-map' => [
    CreateUserCommand::class => GenericUserHandler::class,
    UpdateUserCommand::class => GenericUserHandler::class,
]
```

### 2. Register All Handler Dependencies

```php
// ✅ Good - all dependencies registered
'factories' => [
    CreateUserHandler::class => CreateUserHandlerFactory::class,
    UserRepositoryInterface::class => UserRepositoryFactory::class,
    EventDispatcherInterface::class => EventDispatcherFactory::class,
]
```

### 3. Validate Configuration

```php
// ✅ Good - validate configuration at startup
class ApplicationBootstrap
{
    public function bootstrap(): void
    {
        $discovery = new HandlerDiscovery($this->factory, $this->container);
        $issues = $discovery->validateHandlerConfiguration();

        if (!empty($issues)) {
            throw new InvalidConfigurationException(
                'Handler configuration issues: ' . implode(', ', $issues)
            );
        }
    }
}
```

### 4. Handle Errors Gracefully

```php
// ✅ Good - comprehensive error handling
try {
    $handler = $this->factory->factory($command);
    return $handler->handle($command);
} catch (InvalidConfigurationException $e) {
    $this->logger->error('Command not mapped', ['command' => $command::class]);
    throw new CommandException('Command cannot be processed', 0, $e);
} catch (ServiceNotFoundException $e) {
    $this->logger->error('Handler not available', ['command' => $command::class]);
    throw new CommandException('Command handler unavailable', 0, $e);
}
```

## Related Components

- [CommandHandlerFactoryFactory](container/command-handler-factory-factory.md) - Factory for creating CommandHandlerFactory instances
- [CommandHandlerInterface](command-handler-interface.md) - Interface implemented by resolved handlers
- [CommandInterface](command-interface.md) - Interface implemented by commands
- [InvalidConfigurationException](exception/invalid-configuration-exception.md) - Exception thrown for configuration errors
- [ServiceNotFoundException](exception/service-not-found-exception.md) - Exception thrown for missing services
