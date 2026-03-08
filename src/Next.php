<?php

declare(strict_types=1);

namespace Webware\CommandBus;

use Override;
use SplQueue;
use Webware\CommandBus\Exception\NextHandlerAlreadyCalledException;
use Webware\CommandBus\Handler\EmptyPipelineHandler;

/**
 * @internal
 */
final class Next implements CommandHandlerInterface
{
    /** @var SplQueue<MiddlewareInterface> */
    private ?SplQueue $queue;

    /**
     * Clones the queue provided to allow re-use.
     *
     * @param SplQueue<MiddlewareInterface> $queue
     */
    public function __construct(
        SplQueue $queue,
        private CommandHandlerInterface $emptyPipelineHandler = new EmptyPipelineHandler(),
    ) {
        $this->queue = clone $queue;
    }

    #[Override]
    public function handle(CommandInterface $command): Command\CommandResultInterface
    {
        if ($this->queue === null) {
            throw NextHandlerAlreadyCalledException::create();
        }

        if ($this->queue->isEmpty()) {
            $this->queue = null;

            return $this->emptyPipelineHandler->handle($command);
        }

        $middleware  = $this->queue->dequeue();
        $next        = clone $this;
        $this->queue = null;

        return $middleware->process($command, $next);
    }
}
