<?php

/**
 * This is a specimen content of config.local.php:
 */
/**
 * Debugging
 */
//ini_set('display_errors', 1); //allow ONLY in your own development environment
//define('UNDER_CONSTRUCTION', true);
//$backyardConf['logging_level'] = 5;//debug
//define ('SMTP_HOST', 'localhost');
//define ('SMTP_PORT', 25);
define('MAIL_SENDING_ACTIVE', false);
define('NOTIFY_FROM_ADDRESS', 'notifier-MYCMSPROJECTSPECIFIC@godsapps.eu'); //@todo založit příslušnou schránku
define('NOTIFY_FROM_NAME', 'Notifikátor');
define('EMAIL_ADMIN', 'rejthar@gods.cz'); //email used by Tracy\Debugger
//define('DEBUG_VERBOSE', true); //show all debug messages to the admin

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
//define('GA_UID', 'UA-39642385-1');//if you want other than default test GA UID
//define('FRIENDLY_URL', false);
//define('HOME_TOKEN', 'parent-directory'); //když web běží v rootu domény, tak je defaultní token `PATHINFO_FILENAME` prázdný řetězec; pokud běží jinde, tak je tím jméno rodičovského adresáře k nastavení v config.local.php
//TODO: lépe popsat F3 a RE
//define('FORCE_301', false); //NOT if FRIENDLY_URL but called as parametric, force 301 redirect, it is good for SEO
//define('REDIRECTOR_ENABLED', true); //table redirector with columns old_url, new_url, active exists    

/**
 * Development
 */
//define('USE_CAPTCHA', false); //to turn off CAPTCHA for your dev environment. (Never turn it off however for environment available over internet.)
//feature flags (use keys without spaces to avoid problems in javascript)
$featureFlags = [
//    'offline_dev' => true,
//    'console_log_list_values' => true,
];
//$debugIpArray[] = '192.168.1.145';//add other IP addresses to see full errors
