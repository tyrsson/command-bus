---
applyTo: "test/**/*.php"
---

# Copilot Agent Instructions: PHPUnit Test Suite Generation for Command Bus Library

## Overview

This document provides detailed instructions for generating comprehensive PHPUnit 11.5 test suites for PHP Command Bus libraries, based on the successful implementation of the `cmd-bus` repository test coverage expansion project.

## Project Context

- **Repository**: cmd-bus (PHP Command Bus implementation for Mezzio Framework)
- **Target PHP Framework**: Mezzio
- **Unit testing Framework**: PHPUnit 11.5
- **PHP Version**: 8.1+
- **Architecture**: Command/Query pattern with middleware pipeline driven by SPLPriorityQueue
- **Quality Standards**: PHPStan Level 10, PHP CodeSniffer PSR-12, Laminas Coding Standard 3.1.0+

## Test Generation Methodology

### Phase 1: Repository Analysis and Planning

#### 1.1 Initial Codebase Assessment

```bash
# Commands to understand the project structure
tree src/ /f
tree test/ /f
vendor/bin/phpunit.bat --testdox
composer static-analysis
composer cs-check
```

**Key Analysis Points:**

- Identify all source classes in `src/` directory
- Map existing test coverage in `test/` directory
- Understand dependency injection patterns
- Review interface implementations
- Assess complexity of class interactions

#### 1.2 Test Coverage Gap Analysis

- Compare `src/` structure with `test/unit/` structure
- Identify untested classes
- Prioritize by architectural importance:
  1. Core interfaces and implementations
  2. Handler and middleware components
  3. Factory classes
  4. Configuration providers
  5. Utility classes

### Phase 2: Test File Generation Pattern

#### 2.1 Standard Test File Template

```php
<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\{SubNamespace};

use {RequiredImports};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass({TargetClass}::class)]
final class {TargetClass}Test extends TestCase
{
    private {TargetClass} ${targetInstance};

    /** @var {DependencyType}&MockObject */
    private {DependencyType} ${dependency};

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize mocks and test subject
        $this->{dependency} = $this->createMock({DependencyType}::class);
        $this->{targetInstance} = new {TargetClass}($this->{dependency});
    }

    // Test methods follow...
}
```

#### 2.2 Test Method Categories

**Interface Compliance Tests:**

```php
public function testClassImplementsCorrectInterfaces(): void
{
    $this->assertInstanceOf(RequiredInterface::class, $this->targetInstance);
}
```

**Constructor and Initialization Tests:**

```php
public function testConstructorAcceptsDependencies(): void
{
    $this->assertInstanceOf(TargetClass::class, $this->targetInstance);
}
```

**Core Functionality Tests:**

```php
public function testPrimaryMethodBehavior(): void
{
    // Arrange
    $input = 'test input';
    $expectedOutput = 'expected result';

    // Act
    $result = $this->targetInstance->primaryMethod($input);

    // Assert
    $this->assertEquals($expectedOutput, $result);
}
```

**Exception Testing:**

```php
public function testMethodThrowsExceptionOnInvalidInput(): void
{
    $this->expectException(SpecificException::class);
    $this->expectExceptionMessage('Expected error message');

    $this->targetInstance->methodThatShouldThrow($invalidInput);
}
```

**Edge Case Testing:**

```php
public function testMethodHandlesEdgeCases(): void
{
    $testCases = [
        ['input1', 'expected1'],
        ['input2', 'expected2'],
        [null, 'default'],
    ];

    foreach ($testCases as [$input, $expected]) {
        $result = $this->targetInstance->method($input);
        $this->assertEquals($expected, $result);
    }
}
```

### Phase 3: Specific Implementation Patterns

#### 3.1 Factory Class Testing Pattern

```php
public function testFactoryCreatesCorrectInstance(): void
{
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
        ->method('get')
        ->with(DependencyInterface::class)
        ->willReturn($mockDependency);

    $factory = new TargetFactory();
    $result = $factory($container);

    $this->assertInstanceOf(TargetClass::class, $result);
}

public function testFactoryThrowsExceptionWhenDependencyMissing(): void
{
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
        ->method('has')
        ->with(DependencyInterface::class)
        ->willReturn(false);

    $this->expectException(ServiceNotFoundException::class);

    $factory = new TargetFactory();
    $factory($container);
}
```

#### 3.2 Middleware Testing Pattern

```php
public function testMiddlewareProcessesCommand(): void
{
    $command = $this->createMock(CommandInterface::class);
    $handler = $this->createMock(CommandHandlerInterface::class);

    $handler->expects($this->once())
        ->method('handle')
        ->with($command)
        ->willReturn('handler result');

    $result = $this->middleware->process($command, $handler);

    $this->assertEquals('handler result', $result);
}
```

#### 3.3 Pipeline Testing with Execution Order

```php
public function testPipelineExecutesMiddlewareInCorrectOrder(): void
{
    $executionOrder = [];

    $middleware1 = new class ($executionOrder) implements MiddlewareInterface {
        /** @param array<string> $executionOrder */
        public function __construct(
            /** @phpstan-ignore property.onlyWritten */
            private array &$executionOrder
        ) {}

        public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
        {
            $this->executionOrder[] = 'middleware1';
            return $handler->handle($command);
        }
    };

    // Test execution and verify order
}
```

### Phase 4: Mock Management Strategies

#### 4.1 Final Class Handling

```php
// For final classes that cannot be mocked, use real instances
$container = $this->createMock(ContainerInterface::class);
$this->factory = new CommandHandlerFactory($container); // Real instance
```

#### 4.2 Interface Mocking

```php
// Standard interface mocking
$this->dependency = $this->createMock(DependencyInterface::class);
```

#### 4.3 Anonymous Class Implementation

```php
// For testing complex interactions
$testImplementation = new class implements RequiredInterface {
    public function method(): mixed
    {
        return 'test result';
    }
};
```

### Phase 5: PHPStan Compliance

#### 5.1 Type Annotations

```php
/** @var SpecificType&MockObject */
private SpecificType $dependency;

/** @param array<string> $items */
public function __construct(private array &$items) {}
```

#### 5.2 Property Usage Annotations

```php
/** @phpstan-ignore property.onlyWritten */
private array &$capturedData
```

#### 5.3 Return Type Handling

```php
/** @var SplQueue<MiddlewareInterface> $pipeline */
$pipeline = $property->getValue($this->instance);
```

### Phase 6: Quality Assurance Process

#### 6.1 Test Execution Verification

```bash
# Run tests to ensure they pass
vendor/bin/phpunit.bat

# Run with coverage (if configured)
vendor/bin/phpunit.bat --coverage-text

# Run specific test file
vendor/bin/phpunit.bat test/unit/TargetClassTest.php --testdox
```

#### 6.2 Static Analysis Compliance

```bash
# Ensure PHPStan passes
composer static-analysis

# Fix any issues found
# Common fixes:
# - Add proper type hints
# - Use @phpstan-ignore for false positives
# - Improve method return types
```

#### 6.3 Code Style Compliance

```bash
# Check coding standards
composer cs-check

# Auto-fix violations
composer cs-fix

# Manual fixes for complex violations
```

### Phase 7: Test Organization Best Practices

#### 7.1 Directory Structure

```
test/
├── integration/
│   └── CmdBusTest.php
└── unit/
    ├── CmdBusTest.php
    ├── ConfigProviderTest.php
    ├── MiddlewarePipeTest.php
    ├── NextTest.php
    ├── Container/
    │   ├── CmdBusFactoryTest.php
    │   ├── CommandHandlerResolverFactoryTest.php
    │   ├── CommandHandlerMiddlewareFactoryTest.php
    │   └── MiddlewarePipeFactoryTest.php
    ├── Handler/
    │   └── EmptyPipelineHandlerTest.php
    └── Middleware/
        └── CommandHandlerMiddlewareTest.php
        └── PostCommandHandlerMiddlewareTest.php
        └── PreCommandHandlerMiddlewareTest.php
```

#### 7.2 Test Method Naming Conventions

- `testClassImplementsCorrectInterfaces()`
- `testConstructorAcceptsDependencies()`
- `testMethodNameWithSpecificScenario()`
- `testMethodThrowsExceptionOnInvalidInput()`
- `testMethodHandlesEdgeCases()`

#### 7.3 Test Categories for Each Class

1. **Interface Compliance** (1-2 tests)
2. **Constructor/Initialization** (1-2 tests)
3. **Core Functionality** (3-5 tests)
4. **Exception Handling** (1-3 tests)
5. **Edge Cases** (2-4 tests)
6. **Integration Points** (1-3 tests)
7. **State Management** (1-2 tests if applicable)

### Phase 8: Common Pitfalls and Solutions

#### 8.1 Mock Expectation Issues

**Problem**: PHPStan complains about always-true assertions

```php
// Instead of this:
$this->assertTrue(is_callable($factory));

// Use this:
$this->assertInstanceOf(FactoryInterface::class, $factory);
// Or
$this->assertSame('__invoke', (new ReflectionClass($factory))->getMethod('__invoke')->getName());
```

#### 8.2 Anonymous Class Property Issues

**Problem**: Properties marked as only written

```php
// Solution: Add proper annotations and ensure usage
/** @param array<string> $results */
public function __construct(
    /** @phpstan-ignore property.onlyWritten */
    private array &$results
) {}

// Ensure the property is read in tests
$this->assertContains('expected', $results);
```

#### 8.3 Reflection Property Access

**Problem**: PHPStan can't determine types from reflection

```php
// Solution: Add type annotations
/** @var SplQueue<MiddlewareInterface> $pipeline */
$pipeline = $property->getValue($this->instance);
```

### Phase 9: Automation and Workflow

#### 9.1 Test Generation Order

1. Core interfaces and base classes
2. Middleware classes
3. Handler classes
4. Factory classes (dependency injection) targeting Laminas ServiceManager ^4.0.0
5. Configuration providers
6. Utility classes

#### 9.2 Iterative Improvement Process

1. Generate basic test structure
2. Run tests and fix failures
3. Run PHPStan and fix type issues
4. Run code style checks and fix violations
5. Add edge cases and improve coverage
6. Review and refactor for maintainability

#### 9.3 Continuous Validation

```bash
# Combined validation command
vendor/bin/phpunit.bat && composer static-analysis && composer cs-check
```

## Expected Outcomes

### Quantitative Results

- **Total Tests**: 104 (up from ~20)
- **Total Assertions**: 349
- **PHPStan Errors**: 0
- **Code Style Violations**: 0
- **Test Success Rate**: 100%

### Qualitative Improvements

- Complete unit test coverage for all source classes
- Comprehensive edge case testing
- Use data providers for parameterized tests
- Proper mock usage and dependency isolation
- Clean, maintainable test code
- Documentation through test method names
- Confidence in refactoring and changes

## Tools and Dependencies Required

### Core Testing Tools

- PHPUnit 11.5+
- PHPStan (Level 10)
- PHP CodeSniffer (PSR-12)
- Composer for dependency management

### Development Environment

- PHP 8.1+
- IDE with PHPUnit integration
- Command line access for tool execution

## Conclusion

This instruction set provides a comprehensive methodology for generating high-quality PHPUnit test suites for PHP Command Bus libraries. The approach emphasizes systematic coverage, quality compliance, and maintainable code practices. Following these instructions should result in a robust test suite that provides confidence in code quality and facilitates future development.
