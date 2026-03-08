<?php

declare(strict_types=1);

namespace Webware\CommandBus;

use Override;
use SplQueue;
use Webware\CommandBus\Handler\EmptyPipelineHandler;

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
    public function handle(CommandInterface $command): Command\CommandResultInterface
    {
        return $this->process($command, new EmptyPipelineHandler());
    }

    /**
     * Middleware invocation.
     *
     * Executes the internal pipeline, passing $handler as the "final handler".
     * If this looks familiar it's because it works almost exactly like Mezzio.
     * Which is intentional.
     */
    #[Override]
    public function process(CommandInterface $command, CommandHandlerInterface $handler): Command\CommandResultInterface
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
