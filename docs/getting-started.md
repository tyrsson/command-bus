# Getting Started with cmd-bus

A comprehensive guide to using the cmd-bus Command Bus library in your Mezzio applications.

## Installation

```bash
composer require php-cmd/cmd-bus
```

## Quick Setup

### 1. Register the ConfigProvider

```php
// config/config.php

$aggregator = new ConfigAggregator([
    // Other providers...
    Webware\CommandBus\ConfigProvider::class,
    // Other config providers...
]);
```

### 2. Configure Commands and Handlers

```php
// config/autoload/cmd-bus.global.php

use Webware\CommandBus\ConfigProvider;

return [
    ConfigProvider::class => [
        ConfigProvider::COMMAND_MAP_KEY         => [
            App\User\Command\CreateUserCommand::class => App\User\CommandHandler\CreateUserHandler::class,
            App\User\Command\UpdateUserCommand::class => App\User\CommandHandler\UpdateUserHandler::class,
        ],
        ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
            // This is setup by default, its here only as an example
            ['middleware' => Webware\CommandBus\Middleware\CommandHandlerMiddleware::class, 'priority' => 1],
        ],
    ],
];
```

### 3. Create Your First Command

```php
// src/User/Command/CreateUserCommand.php
namespace App\User\Command;

use App\User\Repository\UserRepositoryInterface;
use Webware\CommandBus\CommandInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateUserCommand implements CommandInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly string $userName,
        private readonly string $email
    ) {}
}
```

### 4. Create Your Handler

```php
// src/User/CommandHandler/CreateUserHandler.php
namespace App\User\CommandHandler;

use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use App\User\Command\CreateUserCommand;
use App\Entity\User;

final class CreateUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function handle(CommandInterface $command): User
    {
        return $this->userRepository->createUser(
            $command->userName,
            $command->email
        );
    }
}
```

### 5. Register Handler Factory

```php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            App\User\Handler\CreateUserHandler::class => App\User\Container\CreateUserHandlerFactory::class,
        ],
    ],
];
```

### 6. Use in RequestHandler

```php
// src/User/RequestHandler/CreateUserHandler.php
namespace App\User\RequestHandler;

use Webware\CommandBus\CmdBusInterface;
use Laminas\Diactoros\Response\JsonResponse;

// Note that this is a request handler, not a command handler
class CreateUserHandler
{
    public function __construct(
        private CmdBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = new CreateUserCommand(
            userName: $request->getParsedBody()['userName'],
            email: $request->getParsedBody()['email']
        );

        try {
            $user = $this->commandBus->handle($command);

            return new JsonResponse([
                'success' => true,
                'user_id' => $user->getId(),
                'message' => 'User created successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## Core Concepts

### Commands

Commands represent an intent to perform an action:

- Implement `CommandInterface`
- Are generally a simple DTO

### Handlers

Handlers execute commands:

- Implement `CommandHandlerInterface`
- Handle pre/post command logic
- Return meaningful results

### Middleware

Middleware provides cross-cutting concerns:

- Implement `MiddlewareInterface`
- Handle pre/post command logic

### Pipeline

The middleware pipeline executes middleware in priority order:

- Higher priority executes first
- Each middleware can act on a given command
- Final middleware is typically `CommandHandlerMiddleware`
- Middleware with lower priority than `CommandHandlerMiddleware` can act on the CommandResult.

## Troubleshooting

### Common Issues

1. **Handler Not Found**
   - Check command is registered in command-map
   - Verify handler class exists and is properly namespaced
   - Ensure handler factory is configured

2. **Middleware Not Executing**
   - Check middleware is registered in pipeline
   - Verify priority is set correctly
   - Ensure middleware factory is configured

3. **Service Not Found**
   - Check all dependencies are registered
   - Verify factory classes exist
   - Review container configuration

## Next Steps

- Read the [Architecture Overview](README.md)
- Check out specific component documentation
- Look at the [test generation instructions](../.github/instructions/copilot-test-generation-instructions.md)
- Review the [project overview](../.github/instructions/project-overview.md)

## Support

- GitHub Issues: [Report bugs or request features](https://github.com/php-cmd/cmd-bus/issues)
- Discussions: [Get help and discuss usage](https://github.com/php-cmd/cmd-bus/discussions)
- Documentation: All components have detailed documentation in this directory
