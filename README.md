# cmd-bus Documentation

A Command Bus implementation for Mezzio applications, providing a clean way to handle commands through a middleware pipeline.

## Table of Contents

### Core Components

- [CommandBus](docs/commandbus.md) - The main command bus implementation
- [CommandBusInterface](docs/commandbus-interface.md) - Command bus contract
- [MiddlewarePipe](docs/middleware-pipe.md) - Middleware pipeline management
- [MiddlewarePipelineInterface](docs/middleware-pipeline-interface.md) - Pipeline contract
- [ConfigProvider](docs/config-provider.md) - Laminas configuration provider
- [Next](docs/next.md) - Pipeline delegation helper

### Interfaces

- [CommandInterface](docs/command-interface.md) - Command contract
- [CommandHandlerInterface](docs/command-handler-interface.md) - Handler contract
- [MiddlewareInterface](docs/middleware-interface.md) - Middleware contract

### Container Factories

- [CommandBusFactory](docs/container/command-bus-factory.md) - Service factory for CmdBus
- [CommandHandlerMiddlewareFactory](docs/container/command-handler-middleware-factory.md) - Factory for CommandHandlerMiddleware
- [MiddlewarePipeFactory](docs/container/middleware-pipe-factory.md) - Factory for MiddlewarePipe

### Middleware

- [PreCommandHandlerMiddleware](docs/middleware/pre-command-handler-middleware.md) - Middleware for pre-processing commands
- [CommandHandlerMiddleware](docs/middleware/command-handler-middleware.md) - Final middleware for command execution
- [PostCommandHandlerMiddleware](docs/middleware/post-command-handler-middleware.md) - Middleware for post-processing results

### Handlers

- [EmptyPipelineHandler](docs/handler/empty-pipeline-handler.md) - Default handler for empty pipelines

### Exceptions

- [CommandException](docs/exception/command-exception.md) - Base command exception
- [InvalidConfigurationException](docs/exception/invalid-configuration-exception.md) - Configuration errors
- [NextHandlerAlreadyCalledException](docs/exception/next-handler-already-called-exception.md) - Pipeline state errors
- [ServiceNotFoundException](docs/exception/service-not-found-exception.md) - Service resolution errors

## Getting Started

For a comprehensive guide to installation, configuration, and usage, see the [Getting Started](docs/getting-started.md) documentation. Full documentation for all components is available in the [docs](docs/) directory.

## Quick Start

### Installation

```bash
composer require webware/command-bus
```

### Basic Configuration

```php
// config/config.php
return [
    Webware\CommandBus\ConfigProvider::class => [
        'command_map' => [
            App\Command\CreateUserCommand::class => App\Handler\CreateUserHandler::class,
        ],
        'middleware_pipeline' => [
            ['middleware' => \Webware\CommandBus\Middleware\CommandHandlerMiddleware::class, 'priority' => 1],
        ],
    ],
];
```

### Usage in Mezzio

```php
// In a request handler or middleware
class UserHandler
{
    public function __construct(
        private \Webware\CommandBus\CmdBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $command = new CreateUserCommand(
            email: $data['email'],
            username: $data['username']
        );

        $user = $this->commandBus->handle($command);

        return new JsonResponse(['user' => $user->toArray()]);
    }
}
```

## Architecture Overview

The cmd-bus library follows these key principles:

1. **Commands** are data objects that implement `CommandInterface`
2. **Handlers** process commands and implement `CommandHandlerInterface`
3. **Middleware** can intercept and potentially modify command processing
4. **Pipeline** manages middleware execution order
5. **Factory classes** integrate with Laminas ServiceManager/ Psr\Container\ContainerInterface

## Contributing

See the project repository for contribution guidelines and development setup instructions.
