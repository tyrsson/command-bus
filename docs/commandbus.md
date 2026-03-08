# CommandBus

The main command bus implementation that orchestrates command processing through a middleware pipeline.

## Overview

`CommandBus` is the primary entry point for command processing. It accepts commands and delegates their execution to a configured middleware pipeline. This class provides a clean interface for command dispatch while allowing flexible middleware composition.

## Class Definition

```php
final class CommandBus implements CommandBusInterface
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
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\Command\CommandResult;

final readonly class CreateUserRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = new CreateUserCommand(
            email: 'user@example.com',
            username: 'newuser'
        );

        /** @var CommandResult $commandResult */
        $commandResult = $this->commandBus->handle($command);

        return match ($result->getStatus()) {
            CommandResult::Success => new JsonResponse(['user_id' => $result->getResult()]),
            CommandResult::Failure => new JsonResponse(['message' => 'Command Failure']),
            default => throw new \YourException('Some custom message'),
        };
    }
}
```

## Related Components

- [CommandBusInterface](CommandBus-interface.md) - The interface this class implements
- [MiddlewarePipe](middleware-pipe.md) - The pipeline component used internally
- [CommandBusFactory](container/CommandBus-factory.md) - Factory for creating instances
- [CommandInterface](command-interface.md) - Interface all commands must implement
