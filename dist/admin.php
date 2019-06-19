<?php

// Admin
require_once './set-environment.php';

//$AGENDAS setting MUST be before prepare.php because it is used in AdminProcess.php and after set-environment.php where DEFAULT_LANGUAGE is set
$AGENDAS = array(
    'category' => array('path' => 'path'),
);

require_once './prepare.php';

$TableAdmin = new \GodsDev\mycmsprojectnamespace\TableAdmin(
        $MyCMS->dbms,
        (isset($_GET['table']) ? $_GET['table'] : ''),
        array('SETTINGS' => $MyCMS->SETTINGS, 'language' => $_SESSION['language'])
);


$MyCMS->csrfStart();
if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
    $adminProcess = new \GodsDev\mycmsprojectnamespace\AdminProcess($MyCMS, array(
        'tableAdmin' => $TableAdmin,
        'agendas' => $AGENDAS
    ));
    $adminProcess->adminProcess($_POST);
}
$admin = new \GodsDev\mycmsprojectnamespace\Admin($MyCMS, array(
    'agendas' => $AGENDAS,
    'TableAdmin' => $TableAdmin,
//        'clientSideResources' => array(
//            'css' => array(
//            ),
//            'js' => array(
//            )
//        )
        ));
echo $admin->outputAdmin();
$admin->endAdmin();
