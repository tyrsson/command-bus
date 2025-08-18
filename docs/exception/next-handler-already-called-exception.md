# NextHandlerAlreadyCalledException

Exception thrown when attempting to call the next handler multiple times in a middleware pipeline.

## Overview

`NextHandlerAlreadyCalledException` is thrown when middleware attempts to call the next handler in the pipeline more than once. This exception prevents potential issues with duplicate command processing and maintains pipeline integrity.

## Class Definition

```php
final class NextHandlerAlreadyCalledException extends DomainException
{
    public static function create(): self;
}
```

## Factory Methods

### create(): self

Creates an exception indicating the next handler has already been called.

**Returns:**

- Exception with message: "The next handler has already been called."

## Purpose

This exception serves to:

1. **Prevent Duplicate Processing** - Ensures commands are processed only once
2. **Maintain Pipeline Integrity** - Prevents middleware from breaking the pipeline flow

## Related Components

- [Next](../next.md) - Class that implements this exception protection
- [MiddlewareInterface](../middleware-interface.md) - Interface for middleware that might throw this exception
- [MiddlewarePipe](../middleware-pipe.md) - Pipeline that manages middleware execution
- [CommandException](command-exception.md) - Related exception that might wrap this one
