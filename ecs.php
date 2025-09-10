<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Skip generated files
        __DIR__ . '/bootstrap',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/public',
        // Skip specific files that might have different formatting requirements
        __DIR__ . '/database/migrations/*',
    ])
    ->withSets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::COMMON,
    ])
    ->withRootFiles();
