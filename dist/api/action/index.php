<?php

/**
 * This template
 * API returns static number
 */

require './../../set-environment.php';

// Under construction section
// Note: if condition changes, pls change also $developmentEnvironment assignement in prepare.php
if (
    UNDER_CONSTRUCTION && !(
    // line below to be used only if behind firewall and the original REMOTE_ADDR present in HTTP_CLIENT_IP
    //  - otherwise it should not be used as it would be a vulnerability
    //isset($_SERVER['HTTP_CLIENT_IP']) ? in_array($_SERVER['HTTP_CLIENT_IP'], $debugIpArray) :
    in_array($_SERVER['REMOTE_ADDR'], $debugIpArray)
    )
) {
    include './../../under-construction.html';
    exit;
}

require_once './../../prepare.php';

use Tracy\Debugger;

Debugger::enable(Debugger::PRODUCTION, DIR_TEMPLATE . '/../log');


$backyard->Json->outputJSON('{"action":"3-' . $_SESSION['language'] . '"}', true);

// The code below is a real example
//
// $this->get['article']) && (isset($this->get['id']
//Debugger::barDump($MyCMS, 'MyCMS before controller');
$controller = new GodsDev\mycmsprojectnamespace\Controller($MyCMS, array(
    'get' => array(
        'action' => '',
        'id' => (int) $_GET['id'],
    ),
    'session' => $_SESSION,
    'language' => $_SESSION['language'],
    'verbose' => DEBUG_VERBOSE,
    'requestUri' => $_SERVER['REQUEST_URI'], //Note: this API expects ?article&id=N anyway
    ));
$controllerResult = $controller->controller();
//$MyCMS->template = $controllerResult['template'];
//$MyCMS->context = $controllerResult['context'];
//Debugger::barDump($controllerResult, 'ControllerResult');
Debugger::barDump($controllerResult['context'], 'ControllerResult[context]');

if (isset($_GET['wrap']) && $_GET['wrap'] === 'simple') {
    //jobs, pro které to je využíváno, nemají formátovanou obálku; a takto je doplněna
    echo '<div class="container content tileProduct">'
    . '<div class="container" data-aos="fade-up"> '
    . '<div class="box-white0">'
    . $controllerResult['context']['article']['description']
    . '</div></div></div>';
} else {
    echo $controllerResult['context']['article']['description'];
}

exit;
