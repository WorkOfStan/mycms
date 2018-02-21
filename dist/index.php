<?php

require './set-environment.php';

// Under construction section
if (UNDER_CONSTRUCTION && !(in_array($_SERVER['REMOTE_ADDR'], $debugIpArray))) {
    include './under-construction.html';
    exit;
}

require_once './prepare.php';

if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    require_once './process.php';
}
$MyCMS->csrf();

use Tracy\Debugger;

Debugger::barDump($MyCMS, 'MyCMS before controller');
$controller = new GodsDev\MYCMSPROJECTNAME\Controller($MyCMS, array("get" => $_GET, "session" => $_SESSION));
$controllerResult = $controller->controller();
$MyCMS->template = $controllerResult['template'];
$MyCMS->context = $controllerResult['context'];
Debugger::barDump($controllerResult, 'ControllerResult');

// texy initialization (@todo refactor) .. used in CustomFilters
$Texy = null;
\GodsDev\MYCMSPROJECTNAME\ProjectSpecific::prepareTexy();

use \GodsDev\Tools\Tools;

$customFilters = new \GodsDev\MYCMSPROJECTNAME\Latte\CustomFilters($MyCMS);

$MyCMS->renderLatte(DIR_TEMPLATE_CACHE, array($customFilters, 'common'), array_merge(
    array(
        'WEBSITE' => $MyCMS->WEBSITE,
        'SETTINGS' => $MyCMS->SETTINGS,
        'ref' => $MyCMS->template,
        'gauid' => GA_UID,
        'token' => $_SESSION['token'],
        'search' => Tools::setifnull($_GET['search'], ''),
        'messages' => Tools::setifnull($_SESSION['messages'], array()),
        'translations' => $MyCMS->TRANSLATIONS,
        'development' => $developmentEnvironment
    ), $MyCMS->context
));
