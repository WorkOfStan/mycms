<?php

require './set-environment.php';

// Under construction section
if (UNDER_CONSTRUCTION && !(in_array($_SERVER['REMOTE_ADDR'], $debugIpArray))) {
    include './under-construction.html';
    exit;
}

require_once './prepare.php';

if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    //set up translation for some multi-lingual messages
    $MyCMS->getSessionLanguage($_GET, $_SESSION, true);    
    require_once './process.php';
}
$MyCMS->csrfStart();

use Tracy\Debugger;

Debugger::barDump($MyCMS, 'MyCMS before controller');
$controller = new \GodsDev\MYCMSPROJECTNAMESPACE\Controller($MyCMS, array(
    'get' => $_GET, 
    'session' => $_SESSION,
    'language' => $_SESSION['language'],    
    ));
$controllerResult = $controller->controller();
$MyCMS->template = $controllerResult['template'];
$MyCMS->context = $controllerResult['context'];
Debugger::barDump($controllerResult, 'ControllerResult');

// texy initialization (@todo refactor) .. used in CustomFilters
$Texy = null;
\GodsDev\MYCMSPROJECTNAMESPACE\ProjectSpecific::prepareTexy();

use \GodsDev\Tools\Tools;

$customFilters = new \GodsDev\MYCMSPROJECTNAMESPACE\Latte\CustomFilters($MyCMS);

$MyCMS->renderLatte(DIR_TEMPLATE_CACHE, array($customFilters, 'common'), array_merge(
    array(
        'WEBSITE' => $MyCMS->WEBSITE,
        'SETTINGS' => $MyCMS->SETTINGS,
        'ref' => $MyCMS->template,
        'gauid' => GA_UID,
        'token' => end($_SESSION['token']),
        'search' => Tools::setifnull($_GET['search'], ''),
        'messages' => Tools::setifnull($_SESSION['messages'], []),
        'language' => $_SESSION['language'],        
        'translations' => $MyCMS->TRANSLATIONS,
        'development' => $developmentEnvironment,
        'pageResourceVersion' => PAGE_RESOURCE_VERSION,
        'useCaptcha' => USE_CAPTCHA,
    ), $MyCMS->context
));
