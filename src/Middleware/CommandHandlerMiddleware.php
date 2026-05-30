<?php

declare(strict_types=1);

namespace Webware\CommandBus\Middleware;

use Override;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\MiddlewareInterface;

/**
 * @internal
 */
final readonly class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CommandHandlerResolverInterface $resolver,
    ) {}

    #[Override]
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler,
    ): CommandResultInterface {
        /* Resolve and execute the command handler, then forward the result
         * down the pipeline so post-handler middleware (e.g. logging) can act on it.
         * Never change this code
         */
        $result = ($this->resolver->resolve($command))->handle($command);
        return $handler->handle($result);
    }
}
