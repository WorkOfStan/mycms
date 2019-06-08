<?php

/**
 * This is specimen content of config.local.php:
 */
//ini_set('display_errors', 1); //allow ONLY in your own development environment
define('DB_USERNAME', 'username');
define('DB_PASSWORD', 'password');
define('DB_DATABASE', 'dbname');
define('RECAPTCHA_SECRET', '...');
//define('UNDER_CONSTRUCTION', true);
//$backyardConf['logging_level'] = 5;//debug
//define('GA_UID', 'UA-39642385-1');//if you want other than default test GA UID
//define ('SMTP_HOST', 'localhost');
//define ('SMTP_PORT', 25);
define('MAIL_SENDING_ACTIVE', false);
define('NOTIFY_FROM_ADDRESS', 'notifier-MYCMSPROJECTSPECIFIC@godsapps.eu'); //@todo založit příslušnou schránku
define('NOTIFY_FROM_NAME', 'Notifikátor');
define('EMAIL_ADMIN', 'rejthar@gods.cz'); //email used by Tracy\Debugger    

//define('FRIENDLY_URL', false);
//define('HOME_TOKEN', 'parent-directory'); //když web běží v rootu domény, tak je defaultní token `PATHINFO_FILENAME` prázdný řetězec; pokud běží jinde, tak je tím jméno rodičovského adresáře k nastavení v config.local.php
//define('USE_CAPTCHA', false); //to turn off CAPTCHA for your dev environment. (Never turn it off however for environment available over internet.)
