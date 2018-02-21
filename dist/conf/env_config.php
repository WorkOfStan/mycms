<?php

/**
 * Config for the project
 */
define('DB_HOST', 'localhost');
define('DB_PORT', ini_get('mysqli.default_port'));
define('TAB_PREFIX', 'MYCMSPROJECTNAME_'); //prefix for database tables

ini_set('session.use_strict_mode', 1);
define('LOG_FILE', './log/log.txt');
define('DEFAULT_LANGUAGE', 'cs');
define('PATH_MODULE', 10); // length of one node in category.path in digits
define('RECAPTCHA_KEY', '............');
define('EXPAND_INFIX', "\t"); // infix for JSON-exapandable values
define('DIR_TEMPLATE', __DIR__ . '/../template'); //for Latte
define('DIR_TEMPLATE_CACHE', __DIR__ . '/../cache'); //for Latte
define('L_UCFIRST', max(MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE) + 1);
define('URL_RECAPTCHA_VERIFY', 'https://www.google.com/recaptcha/api/siteverify');
define('DIR_ASSETS', 'assets/');

//for godsdev/backyard
$backyardConf = array(
    'logging_level' => 3,
    'error_log_message_type' => 3,
    'logging_file' => __DIR__ . '/../log/backyard-error.log',
);

$debugIpArray = array(
    '89.239.1.13', //GODS Ladvi
    '93.99.12.18', //Slavek
    '90.176.60.56', //GODS EOL
);

// configuration for a loaded MyCMS object

$myCmsConf = array(
    // Note: languages are defined in language-XX.inc.php
    'TRANSLATIONS' => array(
        'en' => 'English',
        'cs' => 'Česky'
    ),
    'PAGES_SPECIAL' => array(
        'terms-conditions' => 'Terms & Conditions',
        'privacy-policy' => 'Privacy policy',
        'cookies-policy' => 'Recently visited',
        'sitemap' => 'sitemap'
    ),
    'SETTINGS' => array(
        'domain' => 'MYCMSPROJECTNAME.com',
        'form-email' => 'seidl@gods.cz',
        'PATH_HOME' => '0000000001',
        'PATH_CATEGORY' => '0000000002'
    ),
    'WEBSITE' => array(
    //this will be filled with $WEBSITE['cs'] or $WEBSITE['en'] according to the current language
    )
);
$WEBSITE = array(
    'en' => array(
        'title' => 'MYCMSPROJECTNAME Website name',
        'navigation-title' => 'MYCMSPROJECTNAME Website name',
        'slogan' => 'MYCMSPROJECTNAME Website claim',
        'intro' => 'MYCMSPROJECTNAME Website description',
    ),
    'cs' => array(
        'title' => 'MYCMSPROJECTNAME Website name',
        'navigation-title' => 'MYCMSPROJECTNAME Website name',
        'slogan' => 'MYCMSPROJECTNAME Website claim',
        'intro' => 'MYCMSPROJECTNAME Website description',
    )
);


ini_set('display_errors', 0); //errors only in the log; override it in your env_config.local.php if you need
include_once __DIR__ . '/env_config.local.php'; //use env_config.local.dist.php as specimen
//constants not set in env_config.local.php
foreach (
array(
    'UNDER_CONSTRUCTION' => false,
    'GA_UID' => 'UA-39642385-1',
//    'PAGINATION_SEARCH' => 10,
//    'PAGINATION_NEWS' => 2,
    'SMTP_HOST' => 'localhost',
    'SMTP_PORT' => 25,
    'NOTIFY_FROM_ADDRESS' => 'notifier-MYCMSPROJECTNAME@godsapps.eu', //@todo založit příslušnou schránku
    'NOTIFY_FROM_NAME' => 'Notifikátor',
    'EMAIL_ADMIN' => 'rejthar@gods.cz', //email used by Tracy\Debugger
) as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}
//If you want to receive fatal errors in mail, set in env_config.local.php: $backyardConf['mail_for_admin_enabled'] = true;
if (isset($backyardConf['mail_for_admin_enabled']) && $backyardConf['mail_for_admin_enabled']) {
    $backyardConf['mail_for_admin_enabled'] = EMAIL_ADMIN;
}
