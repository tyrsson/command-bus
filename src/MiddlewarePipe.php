<?php

declare(strict_types=1);

namespace PhpCmd;

use Override;
use SplQueue;

final class MiddlewarePipe implements MiddlewarePipelineInterface, CommandHandlerInterface
{
    /** @var SplQueue<MiddlewareInterface> */
    private SplQueue $pipeline;

    /**
     * Initializes the queue.
     */
    public function __construct()
    {
        $this->pipeline = new SplQueue();
    }

    /**
     * Perform a deep clone.
     */
    public function __clone()
    {
        $this->pipeline = clone $this->pipeline;
    }

    /**
     * Handle an incoming Command.
     *
     * Attempts to handle an incoming command by doing the following:
     *
     * - Cloning itself, to produce a command handler.
     * - Dequeuing the first middleware in the cloned handler.
     * - Processing the first middleware using the command and the cloned handler.
     *
     * If the pipeline is empty at the time this method is invoked, it will
     * raise an exception.
     *
     */
    #[Override]
    public function handle(CommandInterface $command, ?CommandHandlerInterface $handler = null): mixed
    {
        return $this->process($command);
    }

    /**
     * Middleware invocation.
     *
     * Executes the internal pipeline, passing $handler as the "final
     * handler" in cases when the pipeline exhausts itself.
     */
    public function process(CommandInterface $command): mixed
    {
        return (new Next($this->pipeline))->handle($command);
    }

    /**
     * Attach middleware to the pipeline.
     */
    #[Override]
    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->pipeline->enqueue($middleware);
    }
}
