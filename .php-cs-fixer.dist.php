<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use Webware\CodingStandard\Webware1x0Set;

return (new Config())
    ->registerCustomRuleSets([
        new Webware1x0Set()
    ])
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(true)
    ->setRules([
        '@Webware/coding-standard-1.0' => true,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
        // 💡 root folder to check
        ->in(__DIR__)
    );
