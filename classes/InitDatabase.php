<?php

namespace WorkOfStan\MyCMS;

use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * Populating constants from phinx.yml
 * (Last MyCMS/dist revision: 2021-05-20, v0.4.0)
 */
class InitDatabase
{
    use \Nette\SmartObject;

    /**
     *
     * @param string $phinxEnvironment
     * @param string $pathToPhinx
     * @return void
     */
    public function __construct($phinxEnvironment, $pathToPhinx = __DIR__ . '/../')
    {
        // TODO consider refactoring to not use constants , but rather change configuration to use object
        $phinxYml = Yaml::parseFile($pathToPhinx . 'phinx.yml');
        Assert::isArray($phinxYml);
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
