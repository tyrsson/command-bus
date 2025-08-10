<?php

declare(strict_types=1);

namespace PhpCmd;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'php-cmd-bus'  => [
                'command-handlers' => $this->getCommandHandlers(),
                'middleware'       => $this->getMiddleware(),
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [],
            'factories' => [],
        ];
    }

    public function getCommandHandlers(): array
    {
        return [
            // Command FQCN => CommandHandler FQCN
        ];
    }

    public function getMiddleware(): array
    {
        return [
            // FQCN => priority
        ];
    }
}
