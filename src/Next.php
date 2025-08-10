<?php

declare(strict_types=1);

namespace PhpCmd;

use SplQueue;

final class Next implements CommandHandlerInterface
{
        /** @var SplQueue<MiddlewareInterface> */
    private ?SplQueue $queue;

    /**
     * Clones the queue provided to allow re-use.
     *
     * @param SplQueue<MiddlewareInterface> $queue
     * @param RequestHandlerInterface $fallbackHandler Fallback handler to
     *     invoke when the queue is exhausted.
     */
    public function __construct(SplQueue $queue, ?CommandHandlerInterface $fallbackHandler = null)
    {
        $this->queue           = clone $queue;
        //$this->fallbackHandler = $fallbackHandler;
    }

    public function handle(CommandInterface $command): mixed
    {
        if ($this->queue === null) {
            //throw MiddlewarePipeNextHandlerAlreadyCalledException::create();
        }

        if ($this->queue->isEmpty()) {
            $this->queue = null;
            throw new \RuntimeException('Empty Queue!');
            //return $this->fallbackHandler->handle($request);
        }

        $middleware  = $this->queue->dequeue();
        $next        = clone $this; // deep clone is not used intentionally
        $this->queue = null; // mark queue as processed at this nesting level

        return $middleware->process($command, $next);
    }
}
