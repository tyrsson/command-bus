# CommandInterface

The contract that all commands must implement to be processed by the command bus.

## Overview

`CommandInterface` defines the basic structure for commands in the cmd-bus system. All commands must implement this interface to be compatible with the command bus pipeline.

## Class Definition

```php
interface CommandInterface
{
    public function execute(): mixed;
}
```

## Methods

### execute(): mixed

Defines the execution contract for commands.

**Returns:**

- `mixed` - The result of command execution

## Purpose

Commands serve as:

1. **Data Transfer Objects** - Carry data needed for a specific operation
2. **Intent Declaration** - Clearly express what operation should be performed

## Usage Examples

### Basic Command Implementation

```php
use Webware\CommandBus\CommandInterface;

final readonly class CreateUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $username,
        public ?string $displayName = null,
        public array $roles = ['user']
    ) {}

    public function execute(): mixed
    {
        // run the command
    }
}
```

### Command with Validation

### Complex Command with Business Rules

## Related Components

- [CommandHandlerInterface](command-handler-interface.md) - Handlers that process commands
- [CommandBusInterface](CommandBus-interface.md) - Bus that dispatches commands
- [MiddlewareInterface](middleware-interface.md) - Middleware that can intercept commands
