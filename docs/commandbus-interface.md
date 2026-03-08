# CommandBusInterface

The core contract defining the command bus behavior in the cmd-bus library.

## Overview

`CommandBusInterface` extends `CommandHandlerInterface` to provide a unified contract for command processing. This interface ensures that any command bus implementation can handle commands consistently.

## Class Definition

```php
interface CommandBusInterface extends CommandHandlerInterface {}
```

## Inheritance

This interface extends `CommandHandlerInterface`, which means any implementation must provide:

```php
public function handle(CommandInterface $command): mixed;
```

## Purpose

The `CommandBusInterface` serves as:

1. **Abstraction Layer** - Allows different command bus implementations
2. **Dependency Injection Target** - Services can depend on the interface rather than concrete implementations
3. **Type Safety** - Provides compile-time guarantees for command handling
4. **CommandBusInterface::class** - Serves as the top level configuration key

## Usage Examples

### Service Injection

```php
class OrderHandler
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $command = new PlaceOrderCommand(
            customerId: $data['customer_id'],
            items: $data['items'],
            shippingAddress: $data['shipping_address']
        );

        $order = $this->commandBus->handle($command);

        return new JsonResponse([
            'order_id' => $order->getId(),
            'status' => 'pending'
        ]);
    }
}
```

### Mezzio Pipeline Integration

```php
class ProcessCommandMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $command = $this->extractCommandFromRequest($request);

        if ($command) {
            $result  = $this->commandBus->handle($command);
            $request = $request->withAttribute('command_result', $result);
        }

        return $handler->handle($request);
    }
}
```

## Default Implementation

The library provides a default implementation via the `CommandBus` class:

```php
final class CommandBus implements CommandBusInterface
{
    public function handle(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
```

## Related Components

- [CommandBus](CommandBus.md) - Default implementation of this interface
- [CommandHandlerInterface](command-handler-interface.md) - Parent interface
- [CommandInterface](command-interface.md) - Interface for commands
- [CommandBusFactory](container/CommandBus-factory.md) - Factory for creating the standard CommandBus instance
