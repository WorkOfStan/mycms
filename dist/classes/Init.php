<?php

namespace WorkOfStan\mycmsprojectnamespace;

use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * Populating constants from phinx.yml
 * (Last MyCMS/dist revision: 2021-05-20, v0.4.0)
 */
class Init
{
    use \Nette\SmartObject;

    /**
     *
     * @param string $phinxEnvironment
     * @return void
     */
    public function __construct($phinxEnvironment)
    {
        // TODO consider refactoring to not use constants , but rather change configuration to use object
        $phinxYml = Yaml::parseFile(__DIR__ . '/../phinx.yml');
        if (array_key_exists($phinxEnvironment, $phinxYml['environments'])) {
            foreach (
                [
                    'DB_HOST' => 'host',
                    'DB_DATABASE' => 'name',
                    'DB_USERNAME' => 'user',
                    'DB_PASSWORD' => 'pass',
                    'DB_PORT' => 'port', // ini_get('mysqli.default_port')
                    'TAB_PREFIX' => 'table_prefix', // database tables' prefix
                ] as $tempConst => $tempField
            ) {
                if (!defined($tempConst) && isset($phinxYml['environments'][$phinxEnvironment][$tempField])) {
                    Assert::scalar($phinxYml['environments'][$phinxEnvironment][$tempField]);
                    define($tempConst, $phinxYml['environments'][$phinxEnvironment][$tempField]);
                }
            }
        }
    }
}
