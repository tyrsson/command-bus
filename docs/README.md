# cmd-bus Documentation

A Command Bus implementation for Mezzio applications, providing a clean way to handle commands through a middleware pipeline.

## Table of Contents

### Core Components

- [CmdBus](cmdbus.md) - The main command bus implementation
- [CmdBusInterface](cmdbus-interface.md) - Command bus contract
- [MiddlewarePipe](middleware-pipe.md) - Middleware pipeline management
- [MiddlewarePipelineInterface](middleware-pipeline-interface.md) - Pipeline contract
- [CommandHandlerFactory](command-handler-factory.md) - Factory for command handlers
- [ConfigProvider](config-provider.md) - Laminas configuration provider
- [Next](next.md) - Pipeline delegation helper

### Interfaces

- [CommandInterface](command-interface.md) - Command contract
- [CommandHandlerInterface](command-handler-interface.md) - Handler contract
- [MiddlewareInterface](middleware-interface.md) - Middleware contract

### Container Factories

- [CmdBusFactory](container/cmdbus-factory.md) - Service factory for CmdBus
- [CommandHandlerFactoryFactory](container/command-handler-factory-factory.md) - Factory for CommandHandlerFactory
- [CommandHandlerMiddlewareFactory](container/command-handler-middleware-factory.md) - Factory for CommandHandlerMiddleware
- [MiddlewarePipeFactory](container/middleware-pipe-factory.md) - Factory for MiddlewarePipe

### Middleware

- [PreCommandHandlerMiddleware](middleware/pre-command-handler-middleware.md) - Middleware for pre-processing commands
- [CommandHandlerMiddleware](middleware/command-handler-middleware.md) - Final middleware for command execution
- [PostCommandHandlerMiddleware](middleware/post-command-handler-middleware.md) - Middleware for post-processing results

### Handlers

- [EmptyPipelineHandler](handler/empty-pipeline-handler.md) - Default handler for empty pipelines

### Exceptions

- [CommandException](exception/command-exception.md) - Base command exception
- [InvalidConfigurationException](exception/invalid-configuration-exception.md) - Configuration errors
- [NextHandlerAlreadyCalledException](exception/next-handler-already-called-exception.md) - Pipeline state errors
- [ServiceNotFoundException](exception/service-not-found-exception.md) - Service resolution errors

## Quick Start

### Installation

```bash
composer require php-cmd/cmd-bus
```

### Basic Configuration

```php
// config/config.php
return [
    PhpCmd\CmdBus\ConfigProvider::class => [
        'command-map' => [
            App\Command\CreateUserCommand::class => App\Handler\CreateUserHandler::class,
        ],
        'middleware_pipeline' => [
            ['middleware' => \PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware::class, 'priority' => 1],
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
        private \PhpCmd\CmdBus\CmdBusInterface $commandBus
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
