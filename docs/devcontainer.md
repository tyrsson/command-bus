# Dev Container (WSL2)

This project includes a VS Code Dev Container configuration for running the test and CI/CD tooling inside a container via WSL2.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) with the WSL2 backend enabled
- [Visual Studio Code](https://code.visualstudio.com/) with the [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) extension installed
- WSL2 enabled on your Windows machine

## Opening the Project in the Dev Container

1. Clone the repository into your WSL2 filesystem (e.g. `~/projects/command-bus`).
2. Open the folder in VS Code.
3. When prompted, click **Reopen in Container**, or run the command **Dev Containers: Reopen in Container** from the Command Palette (`Ctrl+Shift+P`).
4. VS Code will build and start the container using the configuration in `.devcontainer/devcontainer.json`.
5. Once the container is running, open a terminal in VS Code and run `composer install` to install dependencies.

## What's Included

The dev container is based on the `mcr.microsoft.com/devcontainers/php` image and uses the [PHP dev container feature](https://github.com/devcontainers/features/tree/main/src/php), which provides:

- PHP (with Xdebug pre-installed and configured)
- Composer
- Node.js and npm
- Git

Xdebug is configured out of the box with the following settings:

```ini
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003
```

## Debugging with Xdebug

### Launch Configuration

A **"Listen for Xdebug"** launch configuration is included in the `devcontainer.json` and is automatically available in VS Code when the container starts. You do not need to create a `.vscode/launch.json` file — the configuration is provided by the dev container itself.

To debug:

1. Set a breakpoint in any PHP file (e.g. a test file or source file).
2. Open the **Run and Debug** panel (`Ctrl+Shift+D`).
3. Select **"Listen for Xdebug"** from the dropdown.
4. Press **F5** to start listening.
5. Run PHPUnit from the integrated terminal (see below).

### Running PHPUnit for Debugging

When the debugger is listening, run PHPUnit **directly** from the integrated terminal:

```bash
vendor/bin/phpunit --testsuite "unit test"
```

```bash
vendor/bin/phpunit --testsuite "integration test"
```

Xdebug will connect to VS Code and stop at your breakpoints.

### Why Composer Script Aliases Do Not Work with Xdebug

The `composer.json` file defines script aliases for running the test suites:

```json
"test": "phpunit --colors=always --testsuite \"unit test\"",
"test-integration": "phpunit --colors=always --testsuite \"integration test\""
```

While `composer test` and `composer test-integration` are convenient for normal test runs, **Xdebug breakpoints will not be hit** when using these aliases.

This is because Composer bundles a library called [`composer/xdebug-handler`](https://github.com/composer/xdebug-handler). When you run any `composer` command, this handler detects that Xdebug is loaded, creates a temporary PHP configuration that **excludes the Xdebug extension**, and then restarts the Composer process without it. This is done for performance reasons — Xdebug adds overhead that slows down dependency resolution and other Composer operations.

The result is that by the time Composer executes your script (e.g. `phpunit`), Xdebug has already been stripped from the running PHP process. The debugger has nothing to connect to, and your breakpoints are silently ignored.

**To debug tests, always invoke PHPUnit directly:**

```bash
vendor/bin/phpunit --testsuite "unit test"
```

The Composer script aliases (`composer test`, `composer check`, etc.) remain useful for normal (non-debug) test runs and CI pipelines where Xdebug is not needed.
