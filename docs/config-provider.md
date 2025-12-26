# ConfigProvider

The Laminas configuration provider that registers all cmd-bus components with the service container.

## Overview

`ConfigProvider` is the central configuration class that integrates the cmd-bus library with Laminas ServiceManager. It defines service factories, aliases, command mappings, and middleware pipeline configuration following Laminas component standards.

## Class Definition

```php
final class ConfigProvider
{
    public const COMMAND_MAP_KEY = 'command-map';
    public const DEFAULT_PRIORITY = 1;
    public const MIDDLEWARE_PIPELINE_KEY = 'middleware_pipeline';

    public function __invoke(): array;
    public function getDependencies(): array;
    public function getCommandMap(): array;
    public function getMiddleware(): array;
}
```

## Constants

- `COMMAND_MAP_KEY` - Configuration key for command-to-handler mappings
- `DEFAULT_PRIORITY` - Default priority for middleware (1)
- `MIDDLEWARE_PIPELINE_KEY` - Configuration key for middleware pipeline

## Methods

### __invoke(): array

Returns the complete configuration array for the cmd-bus module.

**Returns:**

- Array with `dependencies` and module-specific configuration

### getDependencies(): array

Returns service manager configuration including factories and aliases.

### getCommandMap(): array

Returns the default command-to-handler mapping (empty by default).

### getMiddleware(): array

Returns the default middleware pipeline configuration.

## Usage Examples

### Basic Mezzio Integration

```php
// config/config.php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    // Other config providers...
    Webware\CommandBus\ConfigProvider::class,
]);
```

### Extended Configuration

```php
// config/autoload/cmd-bus.global.php
return [
    Webware\CommandBus\ConfigProvider::class => [
        'command-map' => [
            // User management commands
            App\Command\User\CreateUserCommand::class => App\Handler\User\CreateUserHandler::class,

            // Order management commands
            App\Command\Order\PlaceOrderCommand::class => App\Handler\Order\PlaceOrderHandler::class,

            // Notification commands
            App\Command\Notification\SendEmailCommand::class => App\Handler\Notification\SendEmailHandler::class,
        ],
        'middleware_pipeline' => [
            [
                'middleware' => Webware\CommandBus\Middleware\CommandHandlerMiddleware::class,
                'priority' => 1
            ],
        ],
    ],
];
```

## Default Services

The ConfigProvider automatically registers these services:

### Core Services

- `CommandBusInterface` → `CommandBus`
- `MiddlewarePipelineInterface` → `MiddlewarePipe`

### Factories

- `CommandBus` → `Container\CommandBusFactory`
- `CommandHandlerFactory` → `Container\CommandHandlerFactoryFactory`
- `MiddlewarePipe` → `Container\MiddlewarePipeFactory`
- `CommandHandlerMiddleware` → `Container\CommandHandlerMiddlewareFactory`
- `EmptyPipelineHandler` → `InvokableFactory`

## Configuration Structure

### Command Map Structure

```php
'command-map' => [
    // Examples:
    App\Command\CreateUserCommand::class => App\Handler\CreateUserHandler::class,
    App\Query\GetUserQuery::class        => App\Handler\GetUserHandler::class,
]
```

### Middleware Pipeline Structure

```php
'middleware_pipeline' => [
    // Example:
    [
        'middleware' => App\Middleware\AuthMiddleware::class,
        'priority'   => 2 // Higher numbers execute first
    ],
]
```

## Related Components

- [CommandBus](CommandBus.md) - Main service configured by this provider
- [MiddlewarePipe](middleware-pipe.md) - Pipeline service configured by this provider
- [CommandHandlerFactory](command-handler-factory.md) - Factory for resolving handlers
- [Container Factories](container/) - Service factories registered by this provider
