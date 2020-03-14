<?php

// Admin
require_once './set-environment.php';

//$AGENDAS setting MUST be before prepare.php because it is used in AdminProcess.php and after set-environment.php where DEFAULT_LANGUAGE is set. For reference see README.md.
$AGENDAS = [
    'category' => ['path' => 'path'], //TODO: create some sample table to demonstrate the usage
];

require_once './prepare.php';

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
