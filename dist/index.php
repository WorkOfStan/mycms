<?php

/**
 * MyCMS app front-end
 * (Last MyCMS/dist revision: 2022-02-04, v0.4.6)
 */

use WorkOfStan\mycmsprojectnamespace\App;

// Sets error_reporting, ini_set, config
require './conf/set-environment.php';

// Under construction section
if (
    UNDER_CONSTRUCTION && !(
    // the line below to be used only if behind firewall and the original REMOTE_ADDR present in HTTP_CLIENT_IP
    // - otherwise it should not be used as it would be a vulnerability
    //isset($_SERVER['HTTP_CLIENT_IP']) ? in_array($_SERVER['HTTP_CLIENT_IP'], $debugIpArray) :
    in_array($_SERVER['REMOTE_ADDR'], $debugIpArray)
    )
) {
    include './under-construction.html';
    exit;
}

// Initiates composer dependencies, session, Debugger, backyard, logger, dbms
require_once './prepare.php';

// Request dispatching
$app = new App($MyCMS, [
    'featureFlags' => $featureFlags,
    'backyard' => $backyard,
    'developmentEnvironment' => $developmentEnvironment,
    'myCmsConf' => $myCmsConf,
    'get' => $_GET,
    'post' => $_POST,
    'session' => $_SESSION,
    'server' => $_SERVER,
    'WEBSITE' => $WEBSITE
    ]);

$app->run();
