<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/controllers',
        __DIR__ . '/helpers',
        __DIR__ . '/middleware',
        __DIR__ . '/plugins',
        __DIR__ . '/public',
        __DIR__ . '/services',
        __DIR__ . '/views',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        // carbon: true,
        // rectorPreset: true
    );
    //->withTypeCoverageLevel(80);
    //->withDeadCodeLevel(0)
    //->withCodeQualityLevel(0);
