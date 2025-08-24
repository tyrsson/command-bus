# MiddlewareInterface

The contract for middleware components in the cmd-bus pipeline system.

## Overview

`MiddlewareInterface` defines the standard interface for middleware that can intercept and process commands before they reach their final handlers. Middleware enables cross-cutting concerns.

## Class Definition

```php
interface MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed;
}
```

## Methods

### process(CommandInterface $command, CommandHandlerInterface $handler): mixed

Processes a command and optionally delegates to the next handler in the pipeline.

**Parameters:**

- `$command` - The command being processed
- `$handler` - The next handler in the pipeline (could be another middleware or the final handler)

**Returns:**

- `mixed` - The result of processing (typically from calling `$handler->handle($command)`)

## Purpose

Middleware serves to:

1. **Implement Cross-Cutting Concerns** - Handle aspects that span multiple commands
2. **Pipeline Composition** - Allow flexible composition of processing steps
3. **Separation of Concerns** - Keep business logic separate from infrastructure concerns

## Usage Examples

### Middleware Factory

```php
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LoggingMiddlewareFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): LoggingMiddleware {
        return new LoggingMiddleware(
            $container->get(LoggerInterface::class)
        );
    }
}
```

## Best Practices

### 1. Always Call Next Handler

Unless you're intentionally short-circuiting the pipeline:

```php
public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
{
    // Do pre-processing
    $result = $handler->handle($command);
    // Do post-processing
    return $result;
}
```

### 2. Preserve Exceptions

Don't swallow exceptions unless you have a good reason:

```php
try {
    return $handler->handle($command);
} catch (\Throwable $e) {
    // Log or handle the exception
    $this->logger->error('Error', ['exception' => $e]);
    throw $e; // Re-throw the exception
}
```

### 3. Use Specific Interfaces

Create specific interfaces for commands that need special handling:

```php
interface AuthenticatedCommandInterface extends CommandInterface
{
    public function getRequiredPermission(): string;
}
```

### 4. Keep Middleware Focused

Each middleware should have a single responsibility:

```php
// ✅ Good - focused on logging
class LoggingMiddleware implements MiddlewareInterface

// ❌ Bad - does too many things
class LoggingValidationCachingMiddleware implements MiddlewareInterface
```

## Related Components

- [MiddlewarePipe](middleware-pipe.md) - Pipeline that manages middleware execution
- [PreCommandHandlerMiddleware](middleware/pre-command-handler-middleware.md) - Middleware for pre-processing commands
- [CommandHandlerMiddleware](middleware/command-handler-middleware.md) - Final middleware for command execution
- [PostCommandHandlerMiddleware](middleware/post-command-handler-middleware.md) - Middleware for post-processing results
- [CommandInterface](command-interface.md) - Commands processed by middleware
- [CommandHandlerInterface](command-handler-interface.md) - Handlers called by middleware
