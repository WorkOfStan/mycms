<?php

/**
 * Used by scripts accessed by clients
 * I.e. index.php and admin.php and also e.g. testmail.php
 * 
 */
// The Composer auto-loader (official way to load Composer contents) to load external stuff automatically
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

use Tracy\Debugger;

$developmentEnvironment = (in_array($_SERVER['REMOTE_ADDR'], array('::1', '127.0.0.1')) || in_array($_SERVER['REMOTE_ADDR'], $debugIpArray));

Debugger::enable($developmentEnvironment ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, __DIR__ . '/log');
Debugger::$email = EMAIL_ADMIN;

$backyard = new \GodsDev\Backyard\Backyard($backyardConf);
$myCmsConf['logger'] = $backyard->BackyardError;
$myCmsConf['dbms'] = new \GodsDev\MyCMS\LogMysqli(DB_HOST . ":" . DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE, $myCmsConf['logger']);
$MyCMS = new \GodsDev\MYCMSPROJECTNAMESPACE\MyCMSProject($myCmsConf);
//set a known language
$_SESSION['language'] = $MyCMS->getSessionLanguage($_GET, $_SESSION); //set also in PHPUnit test
$MyCMS->WEBSITE = $WEBSITE[$_SESSION['language']];
define('PATH_CATEGORY', $MyCMS->SETTINGS['PATH_CATEGORY']);
