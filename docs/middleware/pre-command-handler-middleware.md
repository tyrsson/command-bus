# PreCommandHandlerMiddleware

A pipeline middleware that executes before command handler resolution and processing.

## Overview

`PreCommandHandlerMiddleware` is a pass-through middleware designed to execute custom logic before commands reach the command handler resolution phase. It serves as a hook point for pre-processing operations such as validation, logging, authentication, or command transformation.

## Class Definition

```php
final class PreCommandHandlerMiddleware implements MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed;
}
```

## Purpose

The middleware serves as:

1. **Pre-processing Hook** - Execute logic before command handling
2. **Validation Point** - Validate commands before processing
3. **Logging Entry Point** - Log command execution attempts
4. **Authentication Gate** - Verify user permissions
5. **Command Transformation** - Modify or enrich commands

## Usage Examples

### Basic Implementation

```php
use Webware\CommandBus\Middleware\PreCommandHandlerMiddleware;

// The default implementation passes commands through unchanged
$middleware = new PreCommandHandlerMiddleware();
$result = $middleware->process($command, $nextHandler);
```

### Custom Pre-processing Middleware

```php
final class ValidatingPreCommandMiddleware extends PreCommandHandlerMiddleware
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {}

    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Log incoming command
        $this->logger->info('Processing command', [
            'command' => $command::class,
            'timestamp' => time()
        ]);

        // Validate command if it supports validation
        if ($command instanceof ValidatableCommand) {
            $violations = $this->validator->validate($command);
            if (!empty($violations)) {
                throw new ValidationException('Command validation failed', $violations);
            }
        }

        // Continue to next handler
        return parent::process($command, $handler);
    }
}
```

### Authentication Middleware

```php
final class AuthenticationPreCommandMiddleware extends PreCommandHandlerMiddleware
{
    public function __construct(
        private readonly SecurityContextInterface $security
    ) {}

    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Check if command requires authentication
        if ($command instanceof SecureCommand) {
            if (!$this->security->isAuthenticated()) {
                throw new AuthenticationException('User not authenticated');
            }

            // Check permissions
            if (!$this->security->hasPermission($command->getRequiredPermission())) {
                throw new AuthorizationException('Insufficient permissions');
            }
        }

        return parent::process($command, $handler);
    }
}
```

### Command Enrichment Middleware

```php
final class EnrichmentPreCommandMiddleware extends PreCommandHandlerMiddleware
{
    public function __construct(
        private readonly UserContextInterface $userContext
    ) {}

    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Enrich command with user context
        if ($command instanceof UserAwareCommand && $command->getUserId() === null) {
            $command->setUserId($this->userContext->getCurrentUserId());
            $command->setTimestamp(new DateTimeImmutable());
        }

        return parent::process($command, $handler);
    }
}
```

## Pipeline Configuration

### Adding to Middleware Pipeline

```php
// config/autoload/commandbus.global.php
return [
    Webware\CommandBus\CommandBusInterface::class => [
        'middleware-pipeline' => [
            [
                'middleware' => App\Middleware\AuthenticationPreCommandMiddleware::class,
                'priority'   => 100, // High priority - execute first
            ],
            [
                'middleware' => App\Middleware\ValidatingPreCommandMiddleware::class,
                'priority'   => 50,  // Execute after authentication
            ],
            [
                'middleware' => Webware\CommandBus\Middleware\PreCommandHandlerMiddleware::class,
                'priority'   => 10,  // Default middleware
            ],
            // Other middleware...
        ],
    ],
];
```

### Multiple Pre-Command Middleware

```php
// Multiple specialized pre-command middleware
'middleware-pipeline' => [
    ['middleware' => SecurityMiddleware::class, 'priority' => 100],
    ['middleware' => ValidationMiddleware::class, 'priority' => 90],
    ['middleware' => LoggingMiddleware::class, 'priority' => 80],
    ['middleware' => CachingMiddleware::class, 'priority' => 70],
    ['middleware' => PreCommandHandlerMiddleware::class, 'priority' => 10],
    // Command handler middleware...
    ['middleware' => CommandHandlerMiddleware::class, 'priority' => 0],
],
```

## Best Practices

### 1. Keep Pre-processing Lightweight

```php
// ✅ Good - quick validation
public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
{
    if ($command instanceof RequiredFieldsCommand && !$command->hasRequiredFields()) {
        throw new ValidationException('Missing required fields');
    }
    return parent::process($command, $handler);
}

// ❌ Bad - heavy processing that belongs in handler
public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
{
    // Don't do complex business logic here
    $this->performComplexBusinessLogic($command);
    return parent::process($command, $handler);
}
```

### 2. Use Specific Interfaces

```php
// ✅ Good - specific interface for validation
interface ValidatableCommand extends CommandInterface
{
    public function getValidationRules(): array;
}

// ✅ Good - specific interface for security
interface SecureCommand extends CommandInterface
{
    public function getRequiredPermission(): string;
}
```

### 3. Handle Errors Gracefully

```php
public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
{
    try {
        $this->validateCommand($command);
    } catch (ValidationException $e) {
        $this->logger->warning('Command validation failed', [
            'command' => $command::class,
            'errors' => $e->getErrors()
        ]);
        throw $e;
    }

    return parent::process($command, $handler);
}
```

## Testing

### Unit Testing

```php
class PreCommandHandlerMiddlewareTest extends TestCase
{
    public function testProcessDelegatesToHandler(): void
    {
        $middleware = new PreCommandHandlerMiddleware();
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('result');

        $result = $middleware->process($command, $handler);

        $this->assertEquals('result', $result);
    }
}
```

## Related Components

- [PostCommandHandlerMiddleware](post-command-handler-middleware.md) - Middleware for post-processing
- [CommandHandlerMiddleware](command-handler-middleware.md) - The actual command handler middleware
- [MiddlewareInterface](../middleware-interface.md) - Interface this class implements
- [CommandInterface](../command-interface.md) - Interface for commands being processed
