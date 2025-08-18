# CommandHandlerInterface

The contract for classes that handle command execution in the cmd-bus system.

## Overview

`CommandHandlerInterface` defines the standard interface for command handlers. These handlers contain the business logic for processing specific commands and are the final destination in the command processing pipeline.

## Class Definition

```php
interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
```

## Methods

### handle(CommandInterface $command): mixed

Processes a command and returns the result.

**Parameters:**

- `$command` - The command to process

**Returns:**

- `mixed` - The result of command processing (can be any type)

## Purpose

Command handlers serve as:

1. **Business Logic Container** - Encapsulate the core business operations
2. **Single Responsibility** - Each handler typically handles one command type
3. **Testable Units** - Can be tested independently of the command bus
4. **Service Integration** - Bridge between commands and domain services

## Best Practices

### 1. Single Command Type

Each handler should typically handle only one command type:

```php
// ✅ Good - handles one command type
final class CreateUserHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): User
    {
        assert($command instanceof CreateUserCommand);
        // ...
    }
}
```

## Related Components

- [CommandInterface](command-interface.md) - Commands that handlers process
- [CommandHandlerFactory](command-handler-factory.md) - Factory for resolving handlers
- [CommandHandlerMiddleware](middleware/command-handler-middleware.md) - Middleware that executes handlers
- [CmdBusInterface](cmdbus-interface.md) - Interface that extends this one
