<?php

// Admin
require_once './set-environment.php';
require_once './prepare.php';

//$AGENDAS is used in AdminProcess.php. If $_SESSION['language'] is used in it, set it after prepare.php, where $_SESSION['language'] is fixed. For reference see README.md.
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
];

$TableAdmin = new \GodsDev\mycmsprojectnamespace\TableAdmin(
    $MyCMS->dbms,
    (isset($_GET['table']) ? $_GET['table'] : ''),
    ['SETTINGS' => $MyCMS->SETTINGS, 'language' => $_SESSION['language']]
);


$MyCMS->csrfStart();
if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    $adminProcess = new \GodsDev\mycmsprojectnamespace\AdminProcess($MyCMS, [
        'tableAdmin' => $TableAdmin,
        'agendas' => $AGENDAS
    ]);
    $adminProcess->adminProcess($_POST);
}
$admin = new \GodsDev\mycmsprojectnamespace\Admin($MyCMS, [
    'agendas' => $AGENDAS,
    'TableAdmin' => $TableAdmin,
//        'clientSideResources' => array(
//            'css' => array(
//            ),
//            'js' => array(
//            )
//        )
    ]);
echo $admin->outputAdmin();
$admin->endAdmin();
