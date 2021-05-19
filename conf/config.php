<?php

/**
 * Config for the project
 */

ini_set('display_errors', '0'); //errors only in the log
ini_set('session.use_strict_mode', '1');

define('DEFAULT_LANGUAGE', 'cs'); // named constant 'DEFAULT_LANGUAGE' necessary for PHPUnit tests

$backyardConf = array(
    'logging_level' => 3,
);

if (file_exists(__DIR__ . '/env_config_private.php')) {//backward compatible (still in 0.3.15)
    //delete this `if` in the next version
    error_log("env_config_private.php MUST NOT be used ANYMORE!!! It will be discontinued in the next minor version.");
    include_once __DIR__ . '/env_config_private.php';
}
if (file_exists(__DIR__ . '/config.local.php')) {
    include_once __DIR__ . '/config.local.php';
}
