<?php

declare(strict_types=1);

namespace PhpCmd;

interface CommandInterface {
    public function execute(): mixed;
}
