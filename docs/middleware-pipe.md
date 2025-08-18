# MiddlewarePipe

The core middleware pipeline implementation that manages the execution order and delegation of middleware components.

## Overview

`MiddlewarePipe` is the central component that orchestrates middleware execution in the cmd-bus system. It maintains a queue of middleware components and ensures they execute in the correct order (FIFO - First In, First Out).

## Class Definition

```php
final class MiddlewarePipe implements MiddlewarePipelineInterface
{
    /** @var SplQueue<MiddlewareInterface> */
    private SplQueue $pipeline;

    public function __construct();
    public function __clone();
    public function handle(CommandInterface $command): mixed;
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed;
    public function pipe(MiddlewareInterface $middleware): void;
}
```

## Constructor

Creates a new middleware pipeline with an empty queue.

```php
public function __construct()
{
    $this->pipeline = new SplQueue();
}
```

## Methods

### handle(CommandInterface $command): mixed

Processes a command through the entire pipeline, using an `EmptyPipelineHandler` as the fallback handler.

**Parameters:**

- `$command` - The command to process

**Returns:**

- `mixed` - The result from the pipeline execution

### process(CommandInterface $command, CommandHandlerInterface $handler): mixed

Processes a command through the pipeline with a specific final handler.

**Parameters:**

- `$command` - The command to process
- `$handler` - The handler to use when the pipeline is exhausted

**Returns:**

- `mixed` - The result from the pipeline execution

### pipe(MiddlewareInterface $middleware): void

Adds middleware to the end of the pipeline queue.

**Parameters:**

- `$middleware` - The middleware to add

### __clone()

Performs a deep clone of the pipeline queue to ensure cloned instances have independent pipelines.

## Usage Examples

### Basic Pipeline Setup

```php
use PhpCmd\CmdBus\MiddlewarePipe;
use App\Middleware\LoggingMiddleware;
use App\Middleware\ValidationMiddleware;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;

$pipeline = new MiddlewarePipe();

// Add middleware in execution order
$pipeline->pipe(new LoggingMiddleware($logger));
$pipeline->pipe(new CommandHandlerMiddleware($handlerFactory));

// Process a command
$result = $pipeline->handle($command);
```

## Pipeline Execution Flow

The middleware pipeline executes in FIFO order:

1. **First Middleware** - Executes first (highest priority)
2. **Second Middleware** - Called by first middleware
3. **Third Middleware** - Called by second middleware
4. **Final Handler** - Called when pipeline is exhausted

## Related Components

- [MiddlewarePipelineInterface](middleware-pipeline-interface.md) - Interface this class implements
- [MiddlewareInterface](middleware-interface.md) - Interface for middleware components
- [Next](next.md) - Helper class for pipeline delegation
- [MiddlewarePipeFactory](container/middleware-pipe-factory.md) - Factory for creating instances
