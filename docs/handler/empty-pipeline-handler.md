# EmptyPipelineHandler

A fallback handler that throws an exception when no command handler is found for a command.

## Overview

`EmptyPipelineHandler` serves as the default handler when the middleware pipeline is exhausted without finding an appropriate command handler. It ensures that unhandled commands result in a clear error message rather than silent failures.

## Class Definition

```php
final class EmptyPipelineHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
```

## Implementation

```php
public function handle(CommandInterface $command): mixed
{
    throw CommandException::commandNotHandled($command::class);
}
```

## Purpose

The `EmptyPipelineHandler` serves as:

1. **Safety Net** - Catches commands that don't have registered handlers
2. **Error Clarity** - Provides clear error messages for missing handlers
3. **Pipeline Termination** - Ensures the pipeline always has a final handler

## Usage Examples

### Automatic Usage

The handler is automatically used by `MiddlewarePipe` when no other handler is specified:

```php
use PhpCmd\CmdBus\MiddlewarePipe;

$pipeline = new MiddlewarePipe();
$pipeline->pipe(new LoggingMiddleware($logger));
$pipeline->pipe(new ValidationMiddleware($validator));

// If CommandHandlerMiddleware is not added, EmptyPipelineHandler is used
$command = new CreateUserCommand('user@example.com', 'username');

try {
    $result = $pipeline->handle($command);
} catch (CommandException $e) {
    // "No command handler found for command class "App\Command\CreateUserCommand"."
    echo $e->getMessage();
}
```

### Logging Integration

```php
class LoggingEmptyPipelineHandler implements CommandHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        $this->logger->warning('Command handler not found', [
            'command' => $command::class,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);

        throw CommandException::commandNotHandled($command::class);
    }
}
```

## Related Components

- [CommandException](../exception/command-exception.md) - Exception thrown by this handler
- [MiddlewarePipe](../middleware-pipe.md) - Uses this handler as fallback
- [CommandHandlerMiddleware](../middleware/command-handler-middleware.md) - Proper command execution middleware
- [CommandHandlerInterface](../command-handler-interface.md) - Interface this class implements
