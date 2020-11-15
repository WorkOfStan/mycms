<?php

/**
 * Actions to be taken for a form processing.
 * Many of these below are project specific.
 */

use GodsDev\Tools\Tools;

// Protection against a wrong include from a function
if (!isset($_POST) || !is_array($_POST)) {
    return;
}
if (!isset($_POST['token'], $_SESSION['token'])) {
    return;
} elseif ($_POST['token'] != $_SESSION['token']) {
    $MyCMS->logger->error("CSRF token mismatch {$_POST['token']}!={$_SESSION['token']}");
    return;
}
if (!isset($MyCMS) && isset($GLOBALS['MyCMS'])) {
    $MyCMS = $GLOBALS['MyCMS'];
}
if (isset($_POST['language'], $MyCMS->TRANSLATIONS[$_POST['language']])) {
    header('Content-type: application/json');
    exit(json_encode(array('success' => true)));
}
if ($_POST['newsletter']) {
    if (
        $MyCMS->dbms->query('INSERT INTO ' . TAB_PREFIX . 'subscriber SET email="'
            . $MyCMS->escapeSQL($_POST['newsletter']) . '", info="' . $_SERVER['REMOTE_ADDR'] . '"')
    ) {
        Tools::addMessage('success', $MyCMS->translate('Váš e-mail byl přidán k odběru.'));
        $MyCMS->logger->info("Odběratel {$_POST['newsletter']} přidán k odběru.");
    } elseif ($MyCMS->dbms->errno == 1062) { //duplicate entry = subscriber's e-mail address already exists
        Tools::addMessage('info', $MyCMS->translate('Zadaný e-mail již existuje.'));
        $MyCMS->logger->warning("Odběratel {$_POST['newsletter']} nepřidán k odběru, protože již existuje.");
    } else {
        Tools::addMessage('error', $MyCMS->translate('Váš e-mail se nepodařilo přidat k odběru.'));
        $MyCMS->logger->error("Odběratele {$_POST['newsletter']} se nepodařilo přidat k odběru.");
    }
    Tools::redir();
}
