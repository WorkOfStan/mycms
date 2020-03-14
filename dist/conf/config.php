<?php

/**
 * Config for the project
 */
ini_set('session.use_strict_mode', 1);
ini_set('display_errors', 0); //errors only in the log; override it in your config.local.php if you need

define('DB_HOST', 'localhost');
define('DB_PORT', ini_get('mysqli.default_port'));
define('TAB_PREFIX', 'MYCMSPROJECTSPECIFIC_'); //prefix for database tables

define('LOG_FILE', './log/log.txt');
define('PATH_MODULE', 10); // length of one node in category.path in digits
define('RECAPTCHA_KEY', '............');
define('EXPAND_INFIX', "\t"); // infix for JSON-exapandable values
define('DIR_TEMPLATE', __DIR__ . '/../template'); //for Latte
define('DIR_TEMPLATE_CACHE', __DIR__ . '/../cache'); //for Latte
define('L_UCFIRST', max(MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE) + 1);
define('URL_RECAPTCHA_VERIFY', 'https://www.google.com/recaptcha/api/siteverify');
define('DIR_ASSETS', 'assets/');
define('MYCMS_SECRET', 'u7-r!!T7.&&7y6ru'); //16-byte random string, unique per project

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
        'domain' => 'MYCMSPROJECTSPECIFIC.com',
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
        'title' => 'MYCMSPROJECTSPECIFIC Website name',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Website name',
        'slogan' => 'MYCMSPROJECTSPECIFIC Website claim',
        'intro' => 'MYCMSPROJECTSPECIFIC Website description',
    ),
    'cs' => array(
        'title' => 'MYCMSPROJECTSPECIFIC Website name',
        'navigation-title' => 'MYCMSPROJECTSPECIFIC Website name',
        'slogan' => 'MYCMSPROJECTSPECIFIC Website claim',
        'intro' => 'MYCMSPROJECTSPECIFIC Website description',
    )
);


include_once __DIR__ . '/config.local.php'; //use config.local.dist.php as specimen
//constants not set in config.local.php
foreach (
array(
    'DEFAULT_LANGUAGE' => 'cs',
    'UNDER_CONSTRUCTION' => false,
    'GA_UID' => 'UA-39642385-1',
//    'PAGINATION_SEARCH' => 10,
//    'PAGINATION_NEWS' => 2,
    'SMTP_HOST' => 'localhost',
    'SMTP_PORT' => 25,
    'NOTIFY_FROM_ADDRESS' => 'notifier-MYCMSPROJECTSPECIFIC@godsapps.eu', //@todo založit příslušnou schránku
    'NOTIFY_FROM_NAME' => 'Notifikátor',
    'EMAIL_ADMIN' => 'rejthar@gods.cz', //email used by Tracy\Debugger
    'PAGE_RESOURCE_VERSION' => 1,
    'USE_CAPTCHA' => false,
    'DEBUG_VERBOSE' => false,
//    'FRIENDLY_URL' => true,
//    'HOME_TOKEN' => '', //když web běží v rootu domény, tak je defaultní token `PATHINFO_FILENAME` prázdný řetězec; pokud běží jinde, tak je tím jméno rodičovského adresáře k nastavení v config.local.php
//    'FORCE_301' => true, //if FRIENDLY_URL but called as parametric, force 301 redirect, it is good for SEO
//    'REDIRECTOR_ENABLED' => false, //table redirector with columns old_url, new_url, active exists
) as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}
//If you want to receive fatal errors in mail, set in config.local.php: $backyardConf['mail_for_admin_enabled'] = true;
if (isset($backyardConf['mail_for_admin_enabled']) && $backyardConf['mail_for_admin_enabled']) {
    $backyardConf['mail_for_admin_enabled'] = EMAIL_ADMIN;
}
