<?php

// Admin
require_once './set-environment.php';

//$AGENDAS setting MUST be before prepare.php because it is used in admin-process.php and after set-environment.php where DEFAULT_LANGUAGE is set
$AGENDAS = array(
);

require_once './prepare.php';
$GLOBALS['TableAdmin'] = new \GodsDev\MyCMS\TableAdmin($MyCMS->dbms, (isset($_GET['table']) ? $_GET['table'] : ''));
require_once './user-defined.php';

if (isset($_POST) && is_array($_POST) && !empty($_POST)) {    
    $adminProcess = new \GodsDev\MYCMSPROJECTNAME\AdminProcess($MyCMS, array(
        "tableAdmin" => $TableAdmin,
        "agendas" => $AGENDAS,
    ));
    $adminProcess->adminProcess();
}
$MyCMS->csrf();

$admin = new \GodsDev\MYCMSPROJECTNAME\Admin($MyCMS, array('agendas' => $AGENDAS));
$admin->outputAdmin();
$admin->endAdmin();
