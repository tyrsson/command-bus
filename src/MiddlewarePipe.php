<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

use Override;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\Handler\EmptyPipelineHandler;
use SplQueue;

final class MiddlewarePipe implements MiddlewarePipelineInterface
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
     * Handle a Command.
     */
    #[Override]
    public function handle(CommandInterface $command): mixed
    {
        return $this->process($command, new EmptyPipelineHandler());
    }

    /**
     * Middleware invocation.
     *
     * Executes the internal pipeline, passing $handler as the "final
     * handler" in cases when the pipeline exhausts itself.
     */
    #[Override]
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        return (new Next($this->pipeline, $handler))->handle($command);
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
