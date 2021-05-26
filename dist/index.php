<?php

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;
use WorkOfStan\mycmsprojectnamespace\Controller;
use WorkOfStan\mycmsprojectnamespace\Latte\CustomFilters;
use WorkOfStan\mycmsprojectnamespace\ProjectSpecific;
use WorkOfStan\mycmsprojectnamespace\Utils;

require './set-environment.php';

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
    Debugger::getBar()->addPanel(new BarPanelTemplate('HTTP POST', $_POST));
    // set up translation for some multi-lingual messages
    $MyCMS->getSessionLanguage($_GET, $_SESSION, true); // todo check if it is really necessary here
    require_once './process.php';
}
$MyCMS->csrfStart();

DEBUG_VERBOSE and Debugger::barDump($MyCMS, 'MyCMS before controller');
$controller = new Controller($MyCMS, [
    'get' => Debugger::barDump(array_merge($_GET, $_POST), 'request'),
    'httpMethod' => $_SERVER['REQUEST_METHOD'], // TODO: pre-filter!
    // todo change in MyCMS, that get and requestUri are not processed in duplicate way (changed here + in .htaccess)
    // but .htaccess isn't modified, is it?
    'requestUri' => preg_replace(
        '/(api)(\/)([a-zA-Z0-9=]*)(\?)?(.*)/',
        '?\1-\3&\5',
        $_SERVER['REQUEST_URI']
    ), // necessary for FriendlyURL feature: /api/item?id=14 || /api/item/?id=14 => ?api-item&id=14
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

if (array_key_exists('json', $MyCMS->context)) {
    $MyCMS->renderJson(
        Utils::directJsonCall($_SERVER['HTTP_ACCEPT']),
        $backyard,
        isset($_GET['human']) // context human
    );
    exit; //renderLatte etc isn't required
}

// texy initialization (@todo refactor) .. used in CustomFilters
$Texy = null;
ProjectSpecific::prepareTexy();

$customFilters = new CustomFilters($MyCMS);

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
