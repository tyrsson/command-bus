# CommandHandlerMiddleware

The final middleware in the pipeline that resolves and executes the appropriate command handler.

## Overview

`CommandHandlerMiddleware` is the terminal middleware that bridges the middleware pipeline with actual command handlers. It uses the `CommandHandlerFactory` to resolve the appropriate handler for each command and executes it.

## Class Definition

```php
final class CommandHandlerMiddleware implements MiddlewareInterface, CommandHandlerInterface
{
    public function __construct(private readonly CommandHandlerFactory $factory);
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed;
    public function handle(CommandInterface $command): mixed;
}
```

## Implementation

The middleware implements both `MiddlewareInterface` and `CommandHandlerInterface`:

- **As Middleware** - Resolves handlers and processes commands
- **As Handler** - Can be used directly as a command handler

## Usage Examples

### Command-Handler Mapping

```php
// config/autoload/cmd-bus.global.php
return [
    // other config
    Webware\CommandBus\ConfigProvider::class => [
        'command-map' => [
            App\Command\CreateUserCommand::class => App\Handler\CreateUserHandler::class,
            App\Command\UpdateUserCommand::class => App\Handler\UpdateUserHandler::class,
            App\Command\DeleteUserCommand::class => App\Handler\DeleteUserHandler::class,
        ],
    ],
    // other config
];
```

## Advanced Usage

### Custom Handler Resolution

```php
class CustomCommandHandlerMiddleware extends CommandHandlerMiddleware
{
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Add custom logic before handler resolution
        $this->validateCommand($command);

        // Resolve and execute handler
        $result = parent::process($command, $handler);

        // Add custom logic after execution
        $this->logResult($command, $result);

        return $result;
    }

    private function validateCommand(CommandInterface $command): void
    {
        if ($command instanceof ValidatableCommand && !$command->isValid()) {
            throw new ValidationException('Command validation failed');
        }
    }

    private function logResult(CommandInterface $command, mixed $result): void
    {
        $this->logger->info('Command executed successfully', [
            'command' => $command::class,
            'result_type' => is_object($result) ? $result::class : gettype($result)
        ]);
    }
}
```

### 3. Use Single Handler Per Command

Each command should map to exactly one handler:

```php
// ✅ Good - one-to-one mapping
'command-map' => [
    CreateUserCommand::class => CreateUserHandler::class,
    UpdateUserCommand::class => UpdateUserHandler::class,
]
```

## Related Components

- [CommandHandlerFactory](../command-handler-factory.md) - Factory used to resolve handlers
- [MiddlewareInterface](../middleware-interface.md) - Interface this class implements
- [CommandHandlerInterface](../command-handler-interface.md) - Interface for the handlers this middleware executes
- [CommandHandlerMiddlewareFactory](command-handler-middleware-factory.md) - Factory for creating instances
