<?php

use GodsDev\mycmsprojectnamespace\Admin;
use GodsDev\mycmsprojectnamespace\AdminProcess;
use GodsDev\mycmsprojectnamespace\TableAdmin;

// Admin
require_once './set-environment.php';
require_once './prepare.php';

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
        'SETTINGS' => $MyCMS->SETTINGS,
        'language' => $_SESSION['language'],
        'TRANSLATIONS' => $MyCMS->TRANSLATIONS,
    ]
);


$MyCMS->csrfStart();
if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    $adminProcess = new AdminProcess($MyCMS, [
        'tableAdmin' => $tableAdmin,
        'agendas' => $AGENDAS
    ]);
    $adminProcess->adminProcess($_POST);
}
$admin = new Admin($MyCMS, [
    'agendas' => $AGENDAS,
    'tableAdmin' => $tableAdmin,
//        'clientSideResources' => array(
//            'css' => array(
//            ),
//            'js' => array(
//            )
//        )
    ]);
echo $admin->outputAdmin();
$admin->endAdmin();
