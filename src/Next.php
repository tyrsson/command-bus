<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

use Override;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\Exception\NextHandlerAlreadyCalledException;
use PhpCmd\CmdBus\Handler\EmptyPipelineHandler;
use SplQueue;

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
        private CommandHandlerInterface $emptyPipelineHandler = new EmptyPipelineHandler()
    ) {
        $this->queue = clone $queue;
    }

    #[Override]
    public function handle(CommandInterface $command): mixed
    {
        if ($this->queue === null) {
            throw NextHandlerAlreadyCalledException::create();
        }

        if ($this->queue->isEmpty()) {
            $this->queue = null;
            return $this->emptyPipelineHandler->handle($command);
        }

        $middleware  = $this->queue->dequeue();
        $next        = clone $this; // deep clone is not used intentionally
        $this->queue = null; // mark queue as processed at this nesting level

        return $middleware->process($command, $next);
    }
}
