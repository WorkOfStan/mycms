<?php

/**
 * This is a specimen content of config.local.php:
 */

/**
 * Debugging
 */
//ini_set('display_errors', '1'); // allow ONLY in your own development environment
//define('DEBUG_VERBOSE', true); // show all debug messages to the admin
define('EMAIL_ADMIN', 'rejthar@gods.cz'); // email used by Tracy\Debugger
define('MAIL_SENDING_ACTIVE', false);
define('NOTIFY_FROM_ADDRESS', 'notifier-MYCMSPROJECTSPECIFIC@godsapps.eu'); // @todo založit příslušnou schránku
define('NOTIFY_FROM_NAME', 'Notifikátor');
//define('SMTP_HOST', 'localhost');
//define('SMTP_PORT', 25);
//define('UNDER_CONSTRUCTION', true);
//$backyardConf['logging_level'] = 5; // debug

/**
 * Database
 */
define('DB_USERNAME', 'username');
define('DB_PASSWORD', 'password');
define('DB_DATABASE', 'MYCMSPROJECTSPECIFIC');
define('RECAPTCHA_SECRET', '...');

/**
 * If MySQL timezone differs from PHP timezone settings
 * SELECT @@global.time_zone, @@session.time_zone, @@system_time_zone
 * vs
 * Default timezone in php_info();
 *
 * Meaning that creating item and adding inventory leads to a fail, fix by harmonising the timezones:
 */
//date_default_timezone_set('Europe/Prague');


/**
 * UI and FriendlyURL
 */
//define('DEFAULT_LANGUAGE', 'en'); // change the default language for a particular instance of the application
//define('FORCE_301', false); // true=enforce 301 redirect to the most friendly URL available
//define('FRIENDLY_URL', false); // show friendly URL
//define('GA_UID', 'UA-39642385-1'); // if you want other than default test GA UID
//define('HOME_TOKEN', 'parent-directory'); // set if the web doesn't run in the root of the domain,
//then the default token `PATHINFO_FILENAME` is an empty string; if the web does not run in the root directory,
//set its parent folder name (not the whole path) here.
//define('REDIRECTOR_ENABLED', true); // table redirector with columns old_url, new_url, active exists
//unset($myCmsConf['TRANSLATIONS']['zh']); // unset language for a particular instance of application, here e.g. Chinese

/**
 * Development
 */
//define('USE_CAPTCHA', false); // to turn off CAPTCHA for your dev environment.
//(Never turn USE_CAPTCHA off however for environment available over internet.)
//feature flags (use keys without spaces to avoid problems in javascript)
$featureFlags = [
//    'offline_dev' => true,
//    'console_log_list_values' => true,
];
//$debugIpArray[] = '192.168.1.145'; // add other IP addresses to see full errors
// 'web_domain' for GodsDev\mycmsprojectnamespace\Test\FriendlyUrlTest::testPageStatusOverHttp without trailing `/`
$backyardConf['web_domain'] = 'https://localhost:9090';
// 'web_path' for GodsDev\mycmsprojectnamespace\Test\FriendlyUrlTest::testPageStatusOverHttp including trailing `/`
$backyardConf['web_path'] = '/mycmsprojectnamespace/';
