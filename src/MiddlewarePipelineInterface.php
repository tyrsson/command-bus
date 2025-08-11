<?php

declare(strict_types=1);

namespace PhpCmd;

interface MiddlewarePipelineInterface
{
    public function pipe(MiddlewareInterface $middleware): void;
}
