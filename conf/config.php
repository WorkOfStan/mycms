<?php

/**
 * Config for the project
 */
ini_set('display_errors', 0); //errors only in the log
//define('DB_HOST', 'localhost'); //TODO - je zde potřeba?
//define('DB_PORT', ini_get('mysqli.default_port'));  //TODO - je zde potřeba?
//define('TAB_PREFIX', 'km_'); //prefix for database tables //TODO - je zde potřeba?

ini_set('session.use_strict_mode', 1);
define('EMAIL_ADMIN', 'seidl@gods.cz'); //@todo maybe put it into the database
define('LOG_FILE', './log/log.txt');
define('DEFAULT_LANGUAGE', 'en');
define('PATH_MODULE', 10); // length of one node in category.path in digits
define('RECAPTCHA_KEY', '6LcDhRIUAAAAAHNWzJ1kVlglaRj-hNaJs4WaxBrG');
define('EXPAND_INFIX', "\t"); // infix for JSON-exapandable values
define('DIR_TEMPLATE', __DIR__ . '/../template'); //for Latte
define('DIR_TEMPLATE_CACHE', __DIR__ . '/../cache'); //for Latte

$backyardConf = array(
    'logging_level' => 3,
);

if (file_exists(__DIR__ . '/env_config_private.php')) {//backward compatible
    error_log("env_config_private.php MUST NOT be used ANYMORE!!! It will be discontinued in the next minor version."); //delete this if in the next version
    include_once __DIR__ . '/env_config_private.php';
}
if (file_exists(__DIR__ . '/config.local.php')) {
    include_once __DIR__ . '/config.local.php';
}
