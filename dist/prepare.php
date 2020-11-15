<?php

/**
 * Used by scripts accessed by clients
 * I.e. index.php and admin.php and also e.g. testmail.php
 *
 */

// The Composer auto-loader (official way to load Composer contents) to load external stuff automatically
require_once __DIR__ . '/vendor/autoload.php';

use Tracy\Debugger;

//Tracy is able to show Debug bar and Bluescreens for AJAX and redirected requests.
//You just have to start session before Tracy
session_start() || error_log('session_start failed');
$developmentEnvironment = (
    in_array($_SERVER['REMOTE_ADDR'], array('::1', '127.0.0.1')) || in_array($_SERVER['REMOTE_ADDR'], $debugIpArray)
);

Debugger::enable($developmentEnvironment ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, __DIR__ . '/log');
Debugger::$email = EMAIL_ADMIN;

$backyard = new \GodsDev\Backyard\Backyard($backyardConf);
$myCmsConf['logger'] = $backyard->BackyardError;
$myCmsConf['dbms'] = new \GodsDev\MyCMS\LogMysqli(
    DB_HOST . ":" . DB_PORT,
    DB_USERNAME,
    DB_PASSWORD,
    DB_DATABASE,
    $myCmsConf['logger']
);
$MyCMS = new \GodsDev\mycmsprojectnamespace\MyCMSProject($myCmsConf);
//set $_SESSION['language'] also in PHPUnit test (do not set TRANSLATION by include, as language may be redetermined)
$_SESSION['language'] = $MyCMS->getSessionLanguage($_GET, $_SESSION, false);
//language might change later//$MyCMS->WEBSITE = $WEBSITE[$_SESSION['language']];
//define('PATH_CATEGORY', $MyCMS->SETTINGS['PATH_CATEGORY']); // TODO unused in this application
