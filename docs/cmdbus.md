# CmdBus

The main command bus implementation that orchestrates command processing through a middleware pipeline.

## Overview

`CmdBus` is the primary entry point for command processing. It accepts commands and delegates their execution to a configured middleware pipeline. This class provides a clean interface for command dispatch while allowing flexible middleware composition.

## Class Definition

```php
final class CmdBus implements CmdBusInterface
{
    public function __construct(
        private MiddlewarePipelineInterface&MiddlewarePipe $pipeline
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
```

## Constructor Parameters

- `$pipeline` - A middleware pipeline that implements both `MiddlewarePipelineInterface` and is an instance of `MiddlewarePipe`

## Methods

### handle(CommandInterface $command): mixed

Processes a command through the configured middleware pipeline.

**Parameters:**

- `$command` - The command to process

**Returns:**

- `mixed` - The result from the command processing pipeline

## Usage Examples

### Basic Usage in Mezzio

```php
use App\Command\CreateUserCommand;
use PhpCmd\CmdBus\CmdBusInterface;

class UserHandler
{
    public function __construct(
        private CmdBusInterface $commandBus
    ) {}

    public function createAction(): ResponseInterface
    {
        $command = new CreateUserCommand(
            email: 'user@example.com',
            username: 'newuser'
        );

        $result = $this->commandBus->handle($command);

        return new JsonResponse(['user_id' => $result->getId()]);
    }
}
```

## Related Components

- [CmdBusInterface](cmdbus-interface.md) - The interface this class implements
- [MiddlewarePipe](middleware-pipe.md) - The pipeline component used internally
- [CmdBusFactory](container/cmdbus-factory.md) - Factory for creating instances
- [CommandInterface](command-interface.md) - Interface all commands must implement
