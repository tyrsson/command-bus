# InvalidConfigurationException

Exception thrown when configuration is missing, invalid, or malformed in the cmd-bus system.

## Overview

`InvalidConfigurationException` handles configuration-related errors in the command bus system. It provides specific factory methods for common configuration problems like missing keys, invalid values, unmapped commands, and handler resolution issues.

## Class Definition

```php
final class InvalidConfigurationException extends InvalidArgumentException
{
    public static function fromMissingKey(string $key): self;
    public static function fromInvalidValue(string $key, mixed $value): self;
    public static function fromInvalidType(string $key, mixed $value): self;
    public static function fromUnMappedCommand(string $commandClass): self;
    public static function fromInvalidHandler(string $handlerClass, mixed $handler): self;
    public static function fromHandlerNotFound(string $handlerClass): ServiceNotFoundException;
}
```

## Factory Methods

### fromMissingKey(string $key): self

Creates an exception for missing configuration keys.

**Parameters:**

- `$key` - The missing configuration key

**Returns:**

- Exception with message: "Configuration key "{key}" is missing."

### fromInvalidValue(string $key, mixed $value): self

Creates an exception for invalid configuration values.

**Parameters:**

- `$key` - The configuration key
- `$value` - The invalid value

**Returns:**

- Exception with message showing the key and value type

### fromInvalidType(string $key, mixed $value): self

Creates an exception for incorrect configuration value types.

**Parameters:**

- `$key` - The configuration key
- `$value` - The value with wrong type

**Returns:**

- Exception with message showing expected vs actual type

### fromUnMappedCommand(string $commandClass): self

Creates an exception for commands without handler mappings.

**Parameters:**

- `$commandClass` - The unmapped command class

**Returns:**

- Exception with message: "Missing CommandMap entry for "{commandClass}"."

### fromInvalidHandler(string $handlerClass, mixed $handler): self

Creates an exception for invalid command handlers.

**Parameters:**

- `$handlerClass` - The expected handler class
- `$handler` - The invalid handler instance

**Returns:**

- Exception describing the type mismatch

### fromHandlerNotFound(string $handlerClass): ServiceNotFoundException

Creates a service not found exception for missing handlers.

**Parameters:**

- `$handlerClass` - The missing handler class

**Returns:**

- `ServiceNotFoundException` instance

## Related Components

- [ServiceNotFoundException](service-not-found-exception.md) - Related service resolution exception
- [CommandException](command-exception.md) - Related command processing exception
- [ConfigProvider](../config-provider.md) - Configuration provider that might throw this exception
