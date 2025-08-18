# CommandException

Base exception for command-related errors in the cmd-bus system.

## Overview

`CommandException` is the primary exception thrown when command processing fails. It provides specific factory methods for common command-related error scenarios and serves as the base exception type for command bus operations.

## Class Definition

```php
final class CommandException extends RuntimeException
{
    public static function create(string $commandClass): self;
    public static function fromCommandClass(string $commandClass): self;
    public static function commandNotHandled(string $commandClass): self;
}
```

## Factory Methods

### create(string $commandClass): self

Creates a basic command exception for a command class.

**Parameters:**

- `$commandClass` - The fully qualified class name of the command

**Returns:**

- `CommandException` instance

### fromCommandClass(string $commandClass): self

Creates an exception with a formatted message indicating no handler was found.

**Parameters:**

- `$commandClass` - The fully qualified class name of the command

**Returns:**

- `CommandException` with message: "No command handler found for command class "{class}"."

### commandNotHandled(string $commandClass): self

Creates an exception specifically for unhandled commands (used by EmptyPipelineHandler).

**Parameters:**

- `$commandClass` - The fully qualified class name of the command

**Returns:**

- `CommandException` with message: "No command handler found for command class "{class}"."

## Usage Examples

### Basic Exception Handling

```php
use PhpCmd\CmdBus\Exception\CommandException;

class CreateUserHandler
{
    public function createUser(ServerRequestInterface $request): ResponseInterface
    {
        $command = new CreateUserCommand(
            email: $request->getParsedBody()['email'],
            username: $request->getParsedBody()['username']
        );

        try {
            $user = $this->commandBus->handle($command);
            return new JsonResponse(['user_id' => $user->getId()]);
        } catch (CommandException $e) {
            return new JsonResponse([
                'error' => 'Command processing failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

### Domain-Specific Extensions

```php
// User-specific command exception
class UserCommandException extends CommandException
{
    public static function userNotFound(int $userId): self
    {
        return new self(sprintf('User with ID %d not found', $userId));
    }

    public static function emailAlreadyTaken(string $email): self
    {
        return new self(sprintf('Email "%s" is already taken', $email));
    }

    public static function insufficientPermissions(string $action): self
    {
        return new self(sprintf('Insufficient permissions to %s', $action));
    }
}
```

## Related Components

- [EmptyPipelineHandler](../handler/empty-pipeline-handler.md) - Throws this exception for unhandled commands
- [CommandHandlerFactory](../command-handler-factory.md) - May throw this exception when handlers aren't found
- [InvalidConfigurationException](invalid-configuration-exception.md) - Related configuration exception
- [ServiceNotFoundException](service-not-found-exception.md) - Related service resolution exception
