# CmdBusFactory

Factory class for creating `CmdBus` instances with proper dependency injection via Laminas ServiceManager.

## Overview

`CmdBusFactory` is a Laminas ServiceManager factory that creates and configures `CmdBus` instances. It handles dependency resolution and ensures the command bus is properly initialized with its required middleware pipeline.

## Class Definition

```php
final class CmdBusFactory
{
    public function __invoke(ContainerInterface $container): CmdBus;
}
```

## Implementation

```php
public function __invoke(ContainerInterface $container): CmdBus
{
    if (!$container->has(MiddlewarePipelineInterface::class)) {
        throw ServiceNotFoundException::fromService(MiddlewarePipelineInterface::class);
    }

    /** @var MiddlewarePipelineInterface&MiddlewarePipe $middlewarePipeline */
    $middlewarePipeline = $container->get(MiddlewarePipelineInterface::class);

    return new CmdBus($middlewarePipeline);
}
```

## Dependencies

The factory requires these services to be available in the container:

- `MiddlewarePipelineInterface` - The middleware pipeline implementation

## Usage Examples

### Automatic Registration

The factory is automatically registered by the `ConfigProvider`:

```php
// In ConfigProvider::getDependencies()
'factories' => [
    CmdBus::class => Container\CmdBusFactory::class,
],
'aliases' => [
    CmdBusInterface::class => CmdBus::class,
],
```

### Manual Registration

```php
// In any configuration file
return [
    'dependencies' => [
        'factories' => [
            Webware\CommandBus\CmdBus::class => Webware\CommandBus\Container\CmdBusFactory::class,
        ],
        'aliases' => [
            Webware\CommandBus\CmdBusInterface::class => Webware\CommandBus\CmdBus::class,
        ],
    ],
];
```

### Service Retrieval

```php
// In your application code
$commandBus = $container->get(CmdBusInterface::class);

// Or directly
$commandBus = $container->get(CmdBus::class);
```

### Factory in Action

```php
use Psr\Container\ContainerInterface;
use Webware\CommandBus\Container\CmdBusFactory;

// Container setup (typically handled by Mezzio/Laminas)
$container = new ServiceManager();
$factory = new CmdBusFactory();

// Factory creates the command bus
$commandBus = $factory($container);

// Now you can use the command bus
$result = $commandBus->handle($command);
```

## Integration Examples

### Mezzio Request Handler

```php
class ExampleRequestHandler
{
    public function __construct(
        private CmdBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $commandData = $request->getParsedBody();

        $command = new CreateUserCommand(
            email: $commandData['email'],
            username: $commandData['username']
        );

        try {
            $result = $this->commandBus->handle($command);
            return new JsonResponse(['user_id' => $result->getId()]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
```

### RequestHandler Factory

```php
class ExampleRequestHandlerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ExampleRequestHandler {
        return new ExampleRequestHandler(
            $container->get(CmdBusInterface::class) // Uses CmdBusFactory internally
        );
    }
}
```

### Service Layer Integration

```php
class UserService
{
    public function __construct(
        private CmdBusInterface $commandBus
    ) {}

    public function createUser(array $userData): User
    {
        $command = new CreateUserCommand(
            email: $userData['email'],
            username: $userData['username'],
            displayName: $userData['display_name'] ?? null
        );

        return $this->commandBus->handle($command);
    }

    public function updateUser(int $userId, array $userData): User
    {
        $command = new UpdateUserCommand(
            userId: $userId,
            email: $userData['email'],
            displayName: $userData['display_name']
        );

        return $this->commandBus->handle($command);
    }
}
```

## Error Handling

### Service Not Found

The factory throws `ServiceNotFoundException` if required dependencies are missing:

```php
try {
    $commandBus = $factory($container);
} catch (ServiceNotFoundException $e) {
    // Handle missing MiddlewarePipelineInterface service
    echo "Required service not found: " . $e->getServiceName();
}
```

### Required Configuration

The factory expects the middleware pipeline to be properly configured:

```php
// config/autoload/cmd-bus.global.php
return [
    'Webware\CommandBus\ConfigProvider' => [
        'command-map' => [
            // Map command names to their handlers
            CreateUserCommand::class => CreateUserHandler::class,
            UpdateUserCommand::class => UpdateUserHandler::class,
        ],
        'middleware_pipeline' => [
            ['middleware' => ValidationMiddleware::class, 'priority' => 1000],
            ['middleware' => LoggingMiddleware::class, 'priority' => 900],
            ['middleware' => CommandHandlerMiddleware::class, 'priority' => 1], // This is configured for you
        ],
    ],
    'dependencies' => [
        'factories' => [
            ValidationMiddleware::class => ValidationMiddlewareFactory::class,
            LoggingMiddleware::class => LoggingMiddlewareFactory::class,
            CommandHandlerMiddleware::class => CommandHandlerMiddlewareFactory::class,
        ],
    ],
];
```

## Best Practices

### 1. Use Interface Injection

Always inject the interface rather than the concrete class:

```php
// ✅ Good - depends on interface
public function __construct(private CmdBusInterface $commandBus) {}

// ❌ Bad - depends on implementation
public function __construct(private CmdBus $commandBus) {}
```

### 2. Configure All Dependencies

Ensure all middleware and handlers have proper factories:

```php
'dependencies' => [
    'factories' => [
        CmdBus::class => CmdBusFactory::class,
        MiddlewarePipe::class => MiddlewarePipeFactory::class,
        CommandHandlerMiddleware::class => CommandHandlerMiddlewareFactory::class,
        // Your custom services...
        MyCustomHandler::class => MyCustomHandlerFactory::class,
    ],
],
```

## Related Components

- [CmdBus](../cmdbus.md) - The service this factory creates
- [MiddlewarePipeFactory](middleware-pipe-factory.md) - Factory for the required middleware pipeline
- [ConfigProvider](../config-provider.md) - Registers this factory
- [ServiceNotFoundException](../exception/service-not-found-exception.md) - Exception thrown for missing dependencies
