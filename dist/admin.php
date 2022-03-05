<?php

/**
 * Admin
 * (Last MyCMS/dist revision: 2022-03-05, v0.4.6)
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
$admin = new Admin($MyCMS, [
    'agendas' => $AGENDAS,
    'featureFlags' => $featureFlags,
    'prefixUiL10n' => $myCmsConf['prefixL10n'],
    'tableAdmin' => $tableAdmin,
    // to replace default CSS and/or JS in admin.php, uncomment the array below
//    'clientSideResources' => [
//        'css' => [
//        ],
//        'js' => [
//        ]
//    ]
    ]);
echo $admin->outputAdmin();
$admin->endAdmin();
