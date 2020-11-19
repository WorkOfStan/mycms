<?php

require './set-environment.php';

// TODO move class path to header as use statement for easy replacement in a project

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

require_once './prepare.php';

if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    // set up translation for some multi-lingual messages
    $MyCMS->getSessionLanguage($_GET, $_SESSION, true);
    require_once './process.php';
}
$MyCMS->csrfStart();

use Tracy\Debugger;

DEBUG_VERBOSE and Debugger::barDump($MyCMS, 'MyCMS before controller');
$controller = new \GodsDev\mycmsprojectnamespace\Controller($MyCMS, [
    'get' => Debugger::barDump(array_merge($_GET, $_POST), 'request'),
    'httpMethod' => $_SERVER['REQUEST_METHOD'], // TODO: pre-filter!
    'requestUri' => $_SERVER['REQUEST_URI'], // necessary for FriendlyURL feature
    'session' => $_SESSION,
    'language' => $_SESSION['language'],
    'verbose' => DEBUG_VERBOSE,
    'featureFlags' => $featureFlags,
    ]);
$controllerResult = $controller->run();
$MyCMS->template = $controllerResult['template'];
$MyCMS->context = $controllerResult['context'];
$MyCMS->WEBSITE = $WEBSITE[$_SESSION['language']]; // language is already properly set through FriendlyURL mechanism
Debugger::barDump($controllerResult, 'ControllerResult', [Tracy\Dumper::DEPTH => 5]);

// texy initialization (@todo refactor) .. used in CustomFilters
$Texy = null;
\GodsDev\mycmsprojectnamespace\ProjectSpecific::prepareTexy();

use GodsDev\Tools\Tools;

$customFilters = new \GodsDev\mycmsprojectnamespace\Latte\CustomFilters($MyCMS);

$MyCMS->renderLatte(
    DIR_TEMPLATE_CACHE,
    [$customFilters, 'common'],
    array_merge(
        [
            'WEBSITE' => $MyCMS->WEBSITE,
            'SETTINGS' => $MyCMS->SETTINGS,
            'ref' => $MyCMS->template,
            'gauid' => GA_UID,
            'token' => end($_SESSION['token']),
            'search' => Tools::setifnull($_GET['search'], ''),
            'messages' => Tools::setifnull($_SESSION['messages'], []),
            'language' => $_SESSION['language'],
            'translations' => $MyCMS->TRANSLATIONS,
            'languageSelector' => $myCmsConf['LANGUAGE_SELECTOR'],
            'development' => $developmentEnvironment,
            'pageResourceVersion' => PAGE_RESOURCE_VERSION,
            'useCaptcha' => USE_CAPTCHA,
            'featureFlags' => $featureFlags,
        ],
        $MyCMS->context
    )
);
