# Project Overview: cmd-bus Command Bus Library

---
applyTo: "**/*"
---

## Hey There! 👋

Welcome to the `cmd-bus` project! This is a Command Bus implementation specifically designed for the Mezzio ecosystem, heavily inspired by Laminas Stratigility and Mezzio's middleware approach. Think of it as a way to cleanly separate your business logic from your HTTP layer using the Command pattern with a sprinkle of middleware magic.

## What Are We Building Here?

This library provides a **Command Bus** that lets you:
- Send commands through a middleware pipeline
- Handle commands with dedicated handlers
- Leverage dependency injection via Laminas ServiceManager
- Keep your code clean and testable following CQRS principles

It's like having a well-organized conveyor belt for your business logic - commands go in one end, get processed through various middleware layers, and results come out the other end.

## Project Architecture Overview

### Core Components

**🚌 CmdBus (`src/CmdBus.php`)**
- The main entry point - your command dispatcher
- Takes commands and runs them through the middleware pipeline
- Simple but powerful - just handles the orchestration

**🔗 MiddlewarePipe (`src/MiddlewarePipe.php`)**
- The heart of the system - manages the middleware pipeline
- Uses `SplQueue` internally for FIFO processing
- Handles middleware execution order and delegation

**⚙️ CommandHandlerMiddleware (`src/Middleware/CommandHandlerMiddleware.php`)**
- The final middleware in the chain (usually)
- Resolves and executes the actual command handlers
- Integrates with Laminas ServiceManager for handler resolution

**🏭 Factory Classes (`src/Container/*.php`)**
- Laminas ServiceManager factories for dependency injection
- Follow Laminas component patterns
- Handle configuration and service wiring

### Key Interfaces

- `CmdBusInterface` - Main command bus contract
- `CommandInterface` - All commands must implement this
- `CommandHandlerInterface` - All handlers must implement this
- `MiddlewareInterface` - For custom middleware components
- `MiddlewarePipelineInterface` - Pipeline management contract

## Development Standards & Quality

We're pretty serious about code quality here - not in a stuffy way, just in a "let's write maintainable code" way:

### Quality Tools
- **PHPStan Level 10** - Yes, Level 10! We like our types strict
- **PHP CodeSniffer (PSR-12)** - Consistent formatting is a beautiful thing
- **PHPUnit 11.5** - Comprehensive test coverage with modern patterns
- **PHP 8.2+** - Taking advantage of modern PHP features

### Coding Standards
- **PSR-4** autoloading (obviously)
- **PSR-12** code style (the modern one)
- **Intersection types** for precise type definitions
- **#[Override]** attributes for explicit intent
- **declare(strict_types=1)** everywhere

## Working with This Codebase

### Getting Started
```bash
# Get your dependencies
composer install

# Run the quality checks
composer cs-check      # Code style
composer static-analysis  # PHPStan level 10
vendor/bin/phpunit.bat    # All tests

# Fix code style issues automatically
composer cs-fix
```

### Adding New Features

**Commands & Handlers**
- Commands should be simple DTOs implementing `CommandInterface`
- Handlers should be single-purpose classes implementing `CommandHandlerInterface`
- Register command-to-handler mappings in configuration

**Middleware**
- Implement `MiddlewareInterface`
- Remember: middleware can modify the command, handle cross-cutting concerns, or short-circuit execution
- Register in the middleware pipeline configuration

**Factory Classes**
- Follow Laminas ServiceManager patterns
- Implement `FactoryInterface` from laminas-servicemanager
- Handle configuration injection and dependency resolution

### Testing Approach

We've got a solid testing strategy (check out `copilot-test-generation-instructions.md` for the full details):

**Unit Tests (`test/unit/`)**
- One test class per source class
- Use `#[CoversClass]` attributes
- Mock dependencies, test behavior
- Focus on individual class functionality

**Integration Tests (`test/integration/`)**
- Test component interactions
- Use real implementations where practical
- Verify end-to-end workflows

**Quality Standards**
- Every public method should be tested
- Edge cases matter (empty inputs, null values, exceptions)
- Mock external dependencies
- Use reflection sparingly and with proper type annotations

## Laminas/Mezzio Integration

This library is designed to play nicely with the Laminas ecosystem:

### ServiceManager Integration
- All components are registered via `ConfigProvider`
- Factory classes handle dependency injection
- Support for abstract factories and delegators
- Configuration-driven component wiring

### Mezzio Applications
- Use in middleware or request handlers
- Commands can be triggered from HTTP requests
- Handlers can interact with other Mezzio services
- Clean separation between HTTP and business logic

### Configuration
The `ConfigProvider` class exposes:
- Command-to-handler mappings
- Middleware pipeline configuration
- Service manager factory registrations
- Delegator and initializer configurations

## Common Patterns & Best Practices

### Command Design
```php
final readonly class CreateUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $username,
        public ?string $displayName = null
    ) {}
}
```

### Handler Implementation
```php
final class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger
    ) {}

    public function handle(CommandInterface $command): User
    {
        assert($command instanceof CreateUserCommand);

        // Business logic here
        $user = new User($command->email, $command->username);
        $this->userRepository->save($user);

        $this->logger->info('User created', ['userId' => $user->getId()]);

        return $user;
    }
}
```

### Custom Middleware
```php
final class ValidationMiddleware implements MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        $this->validateCommand($command);
        return $handler->handle($command);
    }
}
```

## Configuration Examples

### Basic Setup
```php
// In your Mezzio config
return [
    'cmd-bus' => [
        'command-map' => [
            CreateUserCommand::class => CreateUserHandler::class,
            UpdateUserCommand::class => UpdateUserHandler::class,
        ],
        'middleware_pipeline' => [
            ['middleware' => ValidationMiddleware::class, 'priority' => 100],
            ['middleware' => LoggingMiddleware::class, 'priority' => 50],
            ['middleware' => CommandHandlerMiddleware::class, 'priority' => 1],
        ],
    ],
];
```

### Advanced Pipeline
```php
'middleware_pipeline' => [
    ['middleware' => SecurityMiddleware::class, 'priority' => 1000],
    ['middleware' => ValidationMiddleware::class, 'priority' => 500],
    ['middleware' => CachingMiddleware::class, 'priority' => 100],
    ['middleware' => TransactionMiddleware::class, 'priority' => 50],
    ['middleware' => LoggingMiddleware::class, 'priority' => 25],
    ['middleware' => CommandHandlerMiddleware::class, 'priority' => 1],
],
```

## Troubleshooting & Common Issues

### PHPStan Complaints
- Use intersection types carefully (`MiddlewarePipelineInterface&MiddlewarePipe`)
- Add `@phpstan-ignore` annotations for false positives
- Ensure proper type hints on all methods
- Use `@var` annotations for complex reflection operations

### ServiceManager Issues
- Check factory registrations in `ConfigProvider`
- Verify command-to-handler mappings
- Ensure all dependencies are properly registered
- Use abstract factories for dynamic service resolution

### Pipeline Problems
- Middleware order matters (higher priority = executed first)
- Always call `$handler->handle($command)` unless short-circuiting
- Be careful with command modification in middleware
- Don't forget the `CommandHandlerMiddleware` at the end!

## Development Workflow

### Before Committing
```bash
# The holy trinity of quality checks
composer cs-fix && composer static-analysis && vendor/bin/phpunit.bat
```

### Adding Tests
- Check `copilot-test-generation-instructions.md` for detailed patterns
- Follow the established naming conventions
- Use proper mock expectations
- Test edge cases and error conditions
