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
    ->withPhpSets(php56: true);
    //->withTypeCoverageLevel(0)
    //->withDeadCodeLevel(0)
    //->withCodeQualityLevel(0);
