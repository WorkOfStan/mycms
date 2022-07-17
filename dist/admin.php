<?php

/**
 * Admin
 * (Last MyCMS/dist revision: 2022-07-17, v0.4.7)
 */

use Tracy\Debugger;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;
use WorkOfStan\mycmsprojectnamespace\Admin;
use WorkOfStan\mycmsprojectnamespace\AdminProcess;
use WorkOfStan\mycmsprojectnamespace\TableAdmin;

require_once './conf/set-environment.php';
require_once './prepare.php';

if (isset($_POST) && !empty($_POST)) {
    Debugger::getBar()->addPanel(new BarPanelTemplate('HTTP POST', $_POST));
}

//$AGENDAS is used in AdminProcess.php. If $_SESSION['language'] is used in it, set it after prepare.php,
//where $_SESSION['language'] is fixed. For reference see README.md.
require_once './conf/config-admin.php';
/* Delete
$AGENDAS = [
    'category' => [
        'column' => "name_{$_SESSION['language']}",
        'prefill' => [
            'sort' => 0,
            'added' => 'now',
        ]
    ],
    'product' => [
        'column' => "name_{$_SESSION['language']}",
        'prefill' => [
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'article' => [
        'table' => 'content',
        'where' => 'type="article"',
        'column' => ['code', "name_{$_SESSION['language']}"],
        'prefill' => [
            'type' => 'article',
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'ad' => [
        'table' => 'content',
        'where' => 'type="ad"',
        'column' => ['code', "name_{$_SESSION['language']}"],
        'prefill' => [
            'type' => 'ad',
            'context' => '{}',
            'sort' => 0,
            'added' => 'now',
        ],
    ],
    'redirector' => [
        'table' => 'redirector',
        'column' => 'old_url',
        'prefill' => [
            'added' => 'now',
        ],
    ],
];
*/

$tableAdmin = new TableAdmin(
    $MyCMS->dbms,
    (isset($_GET['table']) ? $_GET['table'] : ''),
    [
        'language' => (isset($_SESSION['language']) && $_SESSION['language']) ? $_SESSION['language'] : 'en',
        'prefixL10n' => __DIR__ . '/conf/l10n/admin-',
        'SETTINGS' => $MyCMS->SETTINGS,
        'TRANSLATIONS' => $MyCMS->TRANSLATIONS,
    ]
);

$MyCMS->csrfStart();
if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    $adminProcess = new AdminProcess($MyCMS, [
        'agendas' => $AGENDAS,
        'featureFlags' => $featureFlags,
        'prefixUiL10n' => $myCmsConf['prefixL10n'],
        'tableAdmin' => $tableAdmin,
    ]);
    $adminProcess->adminProcess($_POST);
}
$params = [
    'agendas' => $AGENDAS,
    'featureFlags' => $featureFlags,
    'get' => $_GET,
    'get2template' => $myCmsConfAdmin['get2template'],
    'prefixUiL10n' => $myCmsConf['prefixL10n'],
    'renderParams' => [],
    'searchColumns' => $myCmsConfAdmin['searchColumns'],
    'tableAdmin' => $tableAdmin,
//    'tabs' => $myCmsConfAdmin['tabs'],//delete
    // to replace default CSS and/or JS in admin.php, uncomment the array below
//    'clientSideResources' => [
//        'css' => [
//        ],
//        'js' => [
//        ]
//    ]
];
//foreach ($myCmsConfAdmin['tabs'] as $switch => $name) {
//    if (isset($_GET[$switch])) {
//        $params['renderParams']['switches'][] = $switch;
//    }
//}
$admin = new Admin($MyCMS, $params);
if (isset($featureFlags['admin_latte_render']) && $featureFlags['admin_latte_render']) {
    // new version since 0.4.7 or higer
    $admin->renderAdmin();
} else {
    // legacy till 0.4.6
    echo $admin->outputAdmin();
    $admin->endAdmin();
}
