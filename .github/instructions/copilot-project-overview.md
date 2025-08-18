# Getting Started with cmd-bus

A comprehensive guide to using the cmd-bus Command Bus library in your Mezzio applications.

## Installation

```bash
composer require php-cmd/cmd-bus
```

## Quick Setup

### 1. Register the ConfigProvider

```php
// config/config.php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    // Other providers...
    PhpCmd\CmdBus\ConfigProvider::class,

    // Your local config
    'glob:config/autoload/{{,*.}global,{,*.}local}.php',
]);

return $aggregator->getMergedConfig();
```

### 2. Configure Commands and Handlers

```php
// config/autoload/cmd-bus.global.php
return [
    PhpCmd\CmdBus\ConfigProvider::class => [
        'command-map' => [
            App\User\Command\CreateUserCommand::class => App\User\CommandHandler\CreateUserHandler::class,
            App\User\Command\UpdateUserCommand::class => App\User\CommandHandler\UpdateUserHandler::class,
        ],
        'middleware_pipeline' => [
            // This is setup by default
            ['middleware' => PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware::class, 'priority' => 1],
        ],
    ],
];
```

### 3. Create Your First Command

```php
// src/User/Command/CreateUserCommand.php
namespace App\User\Command;

use App\User\Repository\UserRepositoryInterface;
use PhpCmd\CmdBus\CommandInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateUserCommand implements CommandInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function execute(): mixed
    {
       $data = $this->request->getParsedBody();
       return $this->userRepository->save($data);
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
```

### 4. Create Your Handler

```php
// src/User/CommandHandler/CreateUserHandler.php
namespace App\User\CommandHandler;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use App\User\Command\CreateUserCommand;
use App\Entity\User;

final class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(CommandInterface $command): User
    {
        return $command->execute();
    }
}
```

### 5. Register Handler Factory

```php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            App\User\Handler\CreateUserHandler::class => App\User\Container\CreateUserHandlerFactory::class,
        ],
    ],
];
```

### 6. Use in RequestHandler

```php
// src/User/RequestHandler/CreateUserHandler.php
namespace App\User\RequestHandler;

use PhpCmd\CmdBus\CmdBusInterface;
use Laminas\Diactoros\Response\JsonResponse;

class CreateUserHandler
{
    public function __construct(
        private CmdBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = new CreateUserCommand();
        $command->setRequest($request);

        try {
            $user = $this->commandBus->handle($command);

            return new JsonResponse([
                'success' => true,
                'user_id' => $user->getId(),
                'message' => 'User created successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## Core Concepts

### Commands

Commands represent an intent to perform an action:

- Implement `CommandInterface`

### Handlers

Handlers execute commands:

- Implement `CommandHandlerInterface`
- Handle pre/post command logic
- Return meaningful results

### Middleware

Middleware provides cross-cutting concerns:

- Implement `MiddlewareInterface`
- Process commands before handlers

### Pipeline

The middleware pipeline executes middleware in priority order:

- Higher priority executes first
- Each middleware can act on a given command
- Final middleware is typically `CommandHandlerMiddleware`

## Advanced Usage

### Custom Middleware

```php
namespace App\Middleware;

use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\CommandHandlerInterface;

final class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        $this->auditLogger->logCommandStart($command);

        try {
            $result = $handler->handle($command);
            $this->auditLogger->logCommandSuccess($command, $result);
            return $result;
        } catch (\Throwable $e) {
            $this->auditLogger->logCommandFailure($command, $e);
            throw $e;
        }
    }
}
```

### Event Integration

```php
// deps

final class EventDispatchingCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function handle(CommandInterface $command): User
    {
        $this->eventDispatcher->dispatch(new PreExecuteCommandEvent($command));

        $command = new CreateUserCommand();
        $command->setRequest($request);

        $result = $command->execute();

        $this->eventDispatcher->dispatch(new PostExecuteCommandEvent($command, $result));

        return $result;
    }
}
```

## Best Practices

### 1. Command Design

- Keep commands simple and focused
- Use strong typing
- Validate in constructor
- Make immutable with `readonly`

### 2. Handler Organization

- One handler per command (Usually)
- Keep handlers focused on single responsibility
- Use dependency injection for services
- Return meaningful results

### 3. Middleware Order

- Cross-cutting concerns (logging, caching)
- Command execution last (default)

### 4. Error Handling

- Use specific exceptions for business errors
- Let domain exceptions bubble up
- Wrap infrastructure errors appropriately
- Log errors at appropriate levels

## Testing

### Testing Commands

```php
class CreateUserCommandTest extends TestCase
{
    public function testCommandCreation(): void
    {
        $command = new CreateUserCommand(
            email: 'test@example.com',
            username: 'testuser'
        );

        $this->assertEquals('test@example.com', $command->email);
        $this->assertEquals('testuser', $command->username);
    }
}
```

### Testing Handlers

```php
class CreateUserHandlerTest extends TestCase
{
    public function testHandlerCreatesUser(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $handler = new CreateUserHandler($userRepository);

        $command = new CreateUserCommand(
            email: 'test@example.com',
            username: 'testuser'
        );

        $userRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $result = $handler->handle($command);

        $this->assertInstanceOf(User::class, $result);
    }
}
```

### Integration Testing

```php
class CommandBusIntegrationTest extends TestCase
{
    public function testCommandExecution(): void
    {
        $container = $this->createConfiguredContainer();
        $commandBus = $container->get(CmdBusInterface::class);

        $command = new CreateUserCommand(
            email: 'test@example.com',
            username: 'testuser'
        );

        $result = $commandBus->handle($command);

        $this->assertInstanceOf(User::class, $result);
    }
}
```

## Troubleshooting

### Common Issues

1. **Handler Not Found**
   - Check command is registered in command-map
   - Verify handler class exists and is properly namespaced
   - Ensure handler factory is configured

2. **Middleware Not Executing**
   - Check middleware is registered in pipeline
   - Verify priority is set correctly
   - Ensure middleware factory is configured

3. **Service Not Found**
   - Check all dependencies are registered
   - Verify factory classes exist
   - Review container configuration
