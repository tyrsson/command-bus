# CommandInterface

The contract that all commands must implement to be processed by the command bus.

## Overview

`CommandInterface` marker interface for Commands.

## Class Definition

```php
interface CommandInterface {}
```

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
}
```

## Related Components

- [CommandHandlerInterface](command-handler-interface.md) - Handlers that process commands
- [CommandBusInterface](CommandBus-interface.md) - Bus that dispatches commands
- [MiddlewareInterface](middleware-interface.md) - Middleware that can intercept commands
