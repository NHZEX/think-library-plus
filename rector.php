<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/legacy',
        __DIR__ . '/bin',
    ])
    ->withSkip([
        MixedTypeRector::class,
    ])
    ->withPreparedSets(deadCode: true, codeQuality: false, codingStyle: false)
    ->withImportNames(importShortClasses: false)
    ->withPhpSets()
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon',
    ]);
