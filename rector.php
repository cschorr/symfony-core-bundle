<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPhpVersion(80300)
    ->withPhpSets(php83: true)
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
    ])
    ->withSkip([
        __DIR__ . '/src/DataFixtures',
        __DIR__ . '/src/Command',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        codingStyle: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withComposerBased(
        twig: true,
        doctrine: true,
        symfony: true
    )
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
;
