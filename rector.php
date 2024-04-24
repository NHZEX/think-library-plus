<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/legacy',
        __DIR__ . '/bin',
    ])
    ->withSkip([
        MixedTypeRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        DisallowedEmptyRuleFixerRector::class,
    ])
    ->withPreparedSets(deadCode: true, codeQuality: false, codingStyle: false)
    ->withImportNames(importShortClasses: false)
    ->withPhpSets()
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon',
    ]);
