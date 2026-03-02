<?php
declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withAttributesSets()
    ->withSets([
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withSkip([
        CatchExceptionNameMatchingTypeRector::class,
        PreferPHPUnitThisCallRector::class,
        NewlineAfterStatementRector::class,
    ])
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withCache(
    // specify a path that works locally as well as on CI job runners
        cacheDirectory: '/tmp/rector',
        // ensure file system caching is used instead of in-memory
        cacheClass: FileCacheStorage::class,
    )
    ;
