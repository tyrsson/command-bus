# Next

Pipeline continuation handler that manages middleware execution flow in the command bus.

## Overview

The `Next` class implements the continuation pattern for middleware pipelines. It maintains a queue of middleware and handles the sequential execution of each middleware in the pipeline, ensuring proper state management and preventing multiple executions.

## Class Definition

```php
final class Next implements CommandHandlerInterface
{
    public function __construct(
        SplQueue $queue,
        CommandHandlerInterface $emptyPipelineHandler = new EmptyPipelineHandler()
    );
    public function handle(CommandInterface $command): mixed;
}
```

## Constructor Parameters

- `$queue` - Queue of middleware to process
- `$emptyPipelineHandler` - Handler to use when pipeline is empty (defaults to EmptyPipelineHandler)

## Usage

The Next class is primarily used internally by the middleware pipeline and should not be instantiated directly in application code.

```php
// Internal usage within middleware
class CustomMiddleware implements MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $next): mixed
    {
        // Pre-processing logic
        $this->validateCommand($command);

        // Continue to next middleware
        $result = $next->handle($command);

        // Post-processing logic
        $this->logResult($result);

        return $result;
    }
}
```

## Behavior

1. **State Protection** - Prevents multiple calls to the same Next instance
2. **Queue Management** - Safely dequeues and processes middleware
3. **Fallback Handling** - Uses EmptyPipelineHandler when no middleware remains
4. **Shallow Cloning** - Creates new Next instances for each middleware level

## Exceptions

- `NextHandlerAlreadyCalledException` - Thrown when attempting to reuse a Next instance

## Related Components

- [MiddlewarePipe](middleware-pipe.md) - Creates Next instances
- [MiddlewareInterface](middleware-interface.md) - Processes commands with Next
- [EmptyPipelineHandler](handler/empty-pipeline-handler.md) - Fallback handler
- [NextHandlerAlreadyCalledException](exception/next-handler-already-called-exception.md) - State protection exception
