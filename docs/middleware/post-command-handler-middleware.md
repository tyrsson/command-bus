# PostCommandHandlerMiddleware

A pipeline middleware that processes command results after command handler execution.

## Overview

`PostCommandHandlerMiddleware` is responsible for processing the results of command execution. It unwraps `CommandResult` objects to extract the actual results and serves as a hook point for post-processing operations such as result transformation, logging, caching, or cleanup.

## Class Definition

```php
final class PostCommandHandlerMiddleware implements MiddlewareInterface
{
    public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed;
}
```

## Purpose

The middleware serves as:

1. **Result Unwrapping** - Extract results from `CommandResult` objects
2. **Post-processing Hook** - Execute logic after command handling
3. **Result Transformation** - Modify or format results
4. **Cleanup Point** - Perform cleanup operations
5. **Response Logging** - Log command execution results

## Core Functionality

### CommandResult Unwrapping

The primary function is to unwrap `CommandResult` objects created by the command handler middleware:

```php
public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof CommandResult) {
        // Extract the actual result from the wrapper
        return $command->getResult();
    }

    // For regular commands, delegate to next handler
    return $handler->handle($command);
}
```

## Usage Examples

### Basic Usage

```php
use Webware\CommandBus\Middleware\PostCommandHandlerMiddleware;

// The default implementation unwraps CommandResult objects
$middleware = new PostCommandHandlerMiddleware();
$result = $middleware->process($commandResult, $nextHandler);
```

### Custom Post-processing Middleware

```php
final class LoggingPostCommandMiddleware extends PostCommandHandlerMiddleware
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
    {
        if ($command instanceof CommandResult) {
            $result = $command->getResult();

            // Log the result
            $this->logger->info('Command executed', [
                'command' => $command->getCommand()::class,
                'status' => $command->getStatus()->name,
                'result_type' => is_object($result) ? $result::class : gettype($result),
                'success' => $command->getStatus() === CommandStatus::Success
            ]);

            return $result;
        }

        return parent::process($command, $handler);
    }
}
```

### Result Transformation Middleware

```php
final class ResponseFormattingPostCommandMiddleware extends PostCommandHandlerMiddleware
{
    public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
    {
        if ($command instanceof CommandResult) {
            $result = $command->getResult();

            // Transform successful results
            if ($command->getStatus() === CommandStatus::Success) {
                return $this->formatSuccessResponse($result);
            }

            // Handle failure results
            return $this->formatErrorResponse($result);
        }

        return parent::process($command, $handler);
    }

    private function formatSuccessResponse(mixed $result): array
    {
        return [
            'success' => true,
            'data' => $result,
            'timestamp' => time()
        ];
    }

    private function formatErrorResponse(mixed $error): array
    {
        return [
            'success' => false,
            'error' => $error instanceof Throwable ? $error->getMessage() : 'Unknown error',
            'timestamp' => time()
        ];
    }
}
```

### Caching Middleware

```php
final class CachingPostCommandMiddleware extends PostCommandHandlerMiddleware
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {}

    public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
    {
        if ($command instanceof CommandResult) {
            $result = $command->getResult();
            $originalCommand = $command->getCommand();

            // Cache successful results for cacheable commands
            if ($command->getStatus() === CommandStatus::Success
                && $originalCommand instanceof CacheableCommand) {

                $cacheKey = $originalCommand->getCacheKey();
                $ttl = $originalCommand->getCacheTtl();

                $this->cache->set($cacheKey, $result, $ttl);
            }

            return $result;
        }

        return parent::process($command, $handler);
    }
}
```

### Error Handling Middleware

```php
final class ErrorHandlingPostCommandMiddleware extends PostCommandHandlerMiddleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ErrorReporterInterface $errorReporter
    ) {}

    public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
    {
        if ($command instanceof CommandResult) {
            $result = $command->getResult();

            // Handle failed commands
            if ($command->getStatus() === CommandStatus::Failure) {
                $this->handleFailure($command->getCommand(), $result);

                // Re-throw the exception or return a default error response
                if ($result instanceof Throwable) {
                    throw $result;
                }
            }

            return $result;
        }

        return parent::process($command, $handler);
    }

    private function handleFailure(CommandInterface $command, mixed $error): void
    {
        $this->logger->error('Command execution failed', [
            'command' => $command::class,
            'error' => $error instanceof Throwable ? $error->getMessage() : 'Unknown error'
        ]);

        if ($error instanceof Throwable) {
            $this->errorReporter->report($error);
        }
    }
}
```

## Pipeline Configuration

### Adding to Middleware Pipeline

```php
// config/autoload/commandbus.global.php
return [
    Webware\CommandBus\CommandBusInterface::class => [
        'middleware_pipeline' => [
            // Pre-command middleware...
            [
                'middleware' => Webware\CommandBus\Middleware\CommandHandlerMiddleware::class,
                'priority'   => 0,  // Command handler
            ],
            [
                'middleware' => App\Middleware\CachingPostCommandMiddleware::class,
                'priority'   => -10, // Execute after command handler
            ],
            [
                'middleware' => App\Middleware\LoggingPostCommandMiddleware::class,
                'priority'   => -20, // Execute after caching
            ],
            [
                'middleware' => Webware\CommandBus\Middleware\PostCommandHandlerMiddleware::class,
                'priority'   => -100, // Default post-processing (lowest priority)
            ],
        ],
    ],
];
```

### Complete Pipeline Example

```php
'middleware_pipeline' => [
    // Pre-processing (high priority)
    ['middleware' => AuthenticationMiddleware::class, 'priority' => 100],
    ['middleware' => ValidationMiddleware::class, 'priority' => 90],
    ['middleware' => PreCommandHandlerMiddleware::class, 'priority' => 10],

    // Command execution
    ['middleware' => CommandHandlerMiddleware::class, 'priority' => 0],

    // Post-processing (negative priority)
    ['middleware' => CachingPostCommandMiddleware::class, 'priority' => -10],
    ['middleware' => LoggingPostCommandMiddleware::class, 'priority' => -20],
    ['middleware' => ResponseFormattingPostCommandMiddleware::class, 'priority' => -30],
    ['middleware' => PostCommandHandlerMiddleware::class, 'priority' => -100],
],
```

## CommandResult Structure

Understanding the `CommandResult` object structure:

```php
interface CommandResultInterface extends CommandInterface
{
    public function getCommand(): CommandInterface;
    public function getStatus(): CommandStatus;
    public function getResult(): mixed;
}

enum CommandStatus
{
    case Success;
    case Failure;
}
```

### Handling Different Result Types

```php
public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof CommandResult) {
        $result = $command->getResult();

        return match($command->getStatus()) {
            CommandStatus::Success => $this->handleSuccess($result),
            CommandStatus::Failure => $this->handleFailure($result),
        };
    }

    return parent::process($command, $handler);
}
```

## Best Practices

### 1. Handle Both Success and Failure Cases

```php
// ✅ Good - handle both success and failure
public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof CommandResult) {
        $result = $command->getResult();

        if ($command->getStatus() === CommandStatus::Success) {
            return $this->processSuccess($result);
        }

        return $this->processFailure($result);
    }

    return parent::process($command, $handler);
}
```

### 2. Preserve Original Exceptions

```php
// ✅ Good - preserve exception information
public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof CommandResult && $command->getStatus() === CommandStatus::Failure) {
        $error = $command->getResult();

        $this->logError($error);

        // Re-throw to preserve stack trace
        if ($error instanceof Throwable) {
            throw $error;
        }
    }

    return parent::process($command, $handler);
}
```

### 3. Use Type-Safe Result Handling

```php
// ✅ Good - type-safe result processing
public function process(CommandInterface|CommandResult $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof CommandResult) {
        $result = $command->getResult();

        // Type-safe processing based on result type
        return match(true) {
            $result instanceof UserEntity => $this->formatUserResponse($result),
            is_array($result) => $this->formatArrayResponse($result),
            $result instanceof Throwable => $this->formatErrorResponse($result),
            default => $result
        };
    }

    return parent::process($command, $handler);
}
```

## Testing

### Unit Testing

```php
class PostCommandHandlerMiddlewareTest extends TestCase
{
    public function testProcessWithCommandResultReturnsUnwrappedResult(): void
    {
        $middleware = new PostCommandHandlerMiddleware();
        $command = $this->createMock(CommandInterface::class);
        $expectedResult = 'test result';

        $commandResult = new CommandResult(
            $command,
            CommandStatus::Success,
            $expectedResult
        );

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $middleware->process($commandResult, $handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessWithRegularCommandDelegatesToHandler(): void
    {
        $middleware = new PostCommandHandlerMiddleware();
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('handler result');

        $result = $middleware->process($command, $handler);

        $this->assertEquals('handler result', $result);
    }
}
```

## Related Components

- [PreCommandHandlerMiddleware](pre-command-handler-middleware.md) - Middleware for pre-processing
- [CommandHandlerMiddleware](command-handler-middleware.md) - The command handler middleware that creates CommandResult objects
- [CommandResult](../command/command-result.md) - The result wrapper objects this middleware processes
- [CommandStatus](../command/command-status.md) - The status enumeration used in CommandResult
- [MiddlewareInterface](../middleware-interface.md) - Interface this class implements
