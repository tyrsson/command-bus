# CmdBusInterface

The core contract defining the command bus behavior in the cmd-bus library.

## Overview

`CmdBusInterface` extends `CommandHandlerInterface` to provide a unified contract for command processing. This interface ensures that any command bus implementation can handle commands consistently.

## Class Definition

```php
interface CmdBusInterface extends CommandHandlerInterface
{
}
```

## Inheritance

This interface extends `CommandHandlerInterface`, which means any implementation must provide:

```php
public function handle(CommandInterface $command): mixed;
```

## Purpose

The `CmdBusInterface` serves as:

1. **Abstraction Layer** - Allows different command bus implementations
2. **Dependency Injection Target** - Services can depend on the interface rather than concrete implementations
3. **Type Safety** - Provides compile-time guarantees for command handling

## Usage Examples

### Service Injection

```php
class OrderHandler
{
    public function __construct(
        private CmdBusInterface $commandBus
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
        private CmdBusInterface $commandBus
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

The library provides a default implementation via the `CmdBus` class:

```php
final class CmdBus implements CmdBusInterface
{
    public function handle(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
```

## Related Components

- [CmdBus](cmdbus.md) - Default implementation of this interface
- [CommandHandlerInterface](command-handler-interface.md) - Parent interface
- [CommandInterface](command-interface.md) - Interface for commands
- [CmdBusFactory](container/cmdbus-factory.md) - Factory for creating command bus instances
