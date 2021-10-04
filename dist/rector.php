<?php

/**
 * Rector configuration as proposed at https://github.com/rectorphp/rector
 */

declare(strict_types=1);

use Rector\Core\Configuration\Option;
//use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Renaming\Rector\Namespace_\RenameNamespaceRector;
//use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @phpstan-ignore-next-line
 */
return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    /**
     * @phpstan-ignore-next-line
     */
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/*.php',
            __DIR__ . '/api',
            __DIR__ . '/classes',
            __DIR__ . '/Test'
        ]
    );

    // Define what rule sets will be applied
    //$containerConfigurator->import(SetList::DEAD_CODE);
    //
    // get services (needed for register a single rule)
    /**
     * @phpstan-ignore-next-line
     */
    $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
    //
    // RENAME TO THE APP NAMESPACE
    $services->set(RenameNamespaceRector::class)
        ->call('configure', [[
            RenameNamespaceRector::OLD_TO_NEW_NAMESPACES => [
                'WorkOfStan\mycmsprojectnamespace' => 'WorkOfStan\YourRepoName',
            ],]]);
    //TODO: fix unnecessary long object names, such as `new \WorkOfStan\YourRepoName\ProjectSpecific`
    //when `use WorkOfStan\YourRepoName\ProjectSpecific;` was used
};
