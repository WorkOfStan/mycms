<?php

// User Defined Functions for AdminProcess and mycms/TableAdmin
use \GodsDev\Tools\Tools;
use \GodsDev\MyCMS\TableAdmin;

/**
 * 
 * @param string $table
 * @param string $field
 * @param string $value
 * @param TableAdmin $TableAdmin
 * @return boolean
 */
function TableAdminCustomInput($table, $field, $value, TableAdmin $TableAdmin = null)
{
    global $MyCMS;
    $result = false;
    switch ("$table\\$field") {
        case TAB_PREFIX . "content\\category_id":
        case TAB_PREFIX . "product\\category_id":
            if (is_object($TableAdmin)) {
                $result = $TableAdmin->outputForeignId(
                        "fields[$field]", 'SELECT id,CONCAT(REPEAT("… ",LENGTH(path) / ' . PATH_MODULE . ' - 1),category_' . DEFAULT_LANGUAGE . ') AS name FROM ' . TAB_PREFIX . 'category'
                        . ($table == TAB_PREFIX . 'product' ? ' WHERE LEFT(path, ' . PATH_MODULE . ')="' . $MyCMS->escapeSQL($MyCMS->SETTINGS['PATH_CATEGORY']) . '" AND LENGTH(path) > ' . PATH_MODULE : '')
                        . ' ORDER BY path', $value, array('class' => 'form-control', 'id' => $field . $TableAdmin->rand)
                );
            }
            break;
        case TAB_PREFIX . "category\\image":
        case TAB_PREFIX . "content\\image":
        case TAB_PREFIX . "product\\image":
            $result = '<div class="input-group">'
                    . Tools::htmlInput("fields[$field]", '', $value, array('class' => 'form-control input-image', 'id' => $field . $TableAdmin->rand))
                    . '<span class="input-group-btn">'
                    . '<button type="button" class="btn btn-secondary ImageSelector" data-target="#' . Tools::h($field . $TableAdmin->rand) . '" title="' . $TableAdmin->translate('Select') . '"><i class="fa fa-picture-o" aria-hidden="true"></i></button>'
                    . '</span></div>';
            break;
        case TAB_PREFIX . "category\\path":
            $result = $TableAdmin->translate('Parent category') . ':<br />'
                    . Tools::htmlInput('path-original', '', $value, 'hidden')
                    . '<select class="form-control" name="path-parent"><option/>';
            $rows = $MyCMS->fetchAll('SELECT path,category_' . $_SESSION['language'] . ' AS category FROM ' . TAB_PREFIX . 'category ORDER BY path');
            $tmp = substr($value, 0, -PATH_MODULE);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $result .= Tools::htmlOption($row['path'], str_repeat('… ', max(strlen($row['path']) / PATH_MODULE - 1, 0)) . $row['category'], $tmp, Tools::begins($row['path'], $value));
                }
            }
            $result .= '</select>';
            break;
    }
    return $result;
}

/**
 * 
 * @return boolean
 */
function TableAdminCustomSave()
{
    global $MyCMS, $TableAdmin;
    if (!isset($_POST['record-save']) || !$TableAdmin->authorized()) {
        return false;
    }
    // category.path - if admin changes the parent category (or picks it for a new record)  
    // @todo insert this into TableAdmin.php
    if ($_POST['database-table'] == TAB_PREFIX . 'category' && isset($_POST['path-original'], $_POST['path-parent'], $_POST['fields']['id']) && !Tools::begins($_POST['path-original'], $_POST['path-parent'])) {
        $length = array(strlen($_POST['path-parent']), strlen($_POST['path-original']));
        if ($_POST['path-original'] && $_POST['fields']['id'] != '') { // existing record whose parent category changed - we need to shift its sibblings that follow after it to fill the gap 
            $MyCMS->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' WRITE');
            $MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' SET path = NULL WHERE id=' . (int) $_POST['fields']['id'] . ' LIMIT 1'); //category.path is a unique key so to allow for the following change we need to set this path to NULL
            $_POST['original']['path'] = null;
            $update = $MyCMS->fetchAndReindex($sql = 'SELECT id, CONCAT(LEFT(path, ' . ($length[1] - PATH_MODULE) . '), 
                    LPAD(MID(path, ' . ($length[1] - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, ' . PATH_MODULE . ', "0"), 
                    MID(path, ' . ($length[1] + PATH_MODULE + 1) . '))
                FROM ' . Tools::escapeDbIdentifier($_POST['database-table']) . '
                WHERE LEFT(path, ' . ($length[1] - PATH_MODULE) . ') = "' . $MyCMS->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '" 
                AND LENGTH(path) >= ' . $length[1] . ' AND path > "' . $MyCMS->escapeSQL($_POST['path-original']) . '"');
            if ($update) {
                $MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
                foreach ($update as $key => $value) {
                    $MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' SET path = "' . $MyCMS->escapeSQL($value) . '" WHERE id = ' . (int) $key);
                }
            }
        }
        // get path of the "last" child of given parent category, add +1
        $tmp = $MyCMS->fetchSingle($sql = 'SELECT MAX(MID(path, ' . ($length[0] + PATH_MODULE) . ')) FROM ' . Tools::escapeDbIdentifier($_POST['database-table'])
                . ' WHERE LEFT(path, ' . $length[0] . ')="' . $MyCMS->escapeSQL($_POST['path-parent']) . '" AND LENGTH(path)=' . ($length[0] + PATH_MODULE));
        $_POST['fields']['path'] = $_POST['path-parent'] . str_pad(intval($tmp) + 1, PATH_MODULE, '0', STR_PAD_LEFT);
        $MyCMS->dbms->query('UNLOCK TABLES');
        return false;
    }
}

function TableAdminCustomAfterDelete()
{
    global $MyCMS, $TableAdmin;
    if (!isset($_POST['record-delete']) || !$TableAdmin->authorized()) {
        return false;
    }
    if ($_POST['database-table'] == TAB_PREFIX . 'category' && isset($_POST['path-original']) && $_POST['path-original']) {
        $length = strlen($_POST['path-original']);
        $update = $MyCMS->fetchAndReindex($sql = 'SELECT id, CONCAT(LEFT(path, ' . ($length - PATH_MODULE) . '), 
                LPAD(MID(path, ' . ($length - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, ' . PATH_MODULE . ', "0"), 
                MID(path, ' . ($length + PATH_MODULE + 1) . '))
            FROM ' . Tools::escapeDbIdentifier($_POST['database-table']) . '
            WHERE LEFT(path, ' . ($length - PATH_MODULE) . ') = "' . $MyCMS->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '" 
            AND LENGTH(path) >= ' . $length . ' AND path > "' . $MyCMS->escapeSQL($_POST['path-original']) . '"');
        if ($update) {
            $MyCMS->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' WRITE');
            $MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
            foreach ($update as $key => $value) {
                $MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['database-table']) . ' SET path = "' . $MyCMS->escapeSQL($value) . '" WHERE id = ' . (int) $key);
            }
            $MyCMS->dbms->query('UNLOCK TABLES');
        }
    }
}

/**
 * this function is called after displaying the table
 * open for customization
 * 
 * @param string $table
 */
function TableAdminCustomOperation($table)
{
    return false;
}

/**
 * this function is called to optionally fill the search select
 * open for customization
 * 
 * @param string $table
 */
function TableAdminCustomSearch($table)
{
    return false;
}

/**
 * this function is called to optionally fill conditions to WHERE clause of the SQL statement selecting given table
 * 
 * @param string $table
 */
function TableAdminCustomCondition($table)
{
    return false;
}

//
/**
 * user-defined manipulating with column value of given table  
 *  
 * @param string $table
 * @param string $column
 * @param array $row
 * @return mixed
 */
function TableAdminCustomValue($table, $column, array $row)
{
    return $row[$column];
}

function TableListerTranslate($text)
{
    static $TRANSLATION_CS = array(
        'New row' => 'Nová řádka',
        'New record' => 'Nový záznam',
        'No records found.' => 'Nebyly nalezeny žádné záznamy.',
        'Total rows: ' => 'Celkem řádků: ',
        'Search' => 'Hledat',
        'Sort' => 'Řadit',
        'descending' => 'sestupně',
        'View' => 'Zobrazit',
        'Text length' => 'Délky textů',
        'Rows per page' => 'Řádek na stránku',
        'Edit' => 'Upravit',
        'Page' => 'Stránka',
        'Go to page' => 'Jít na stránku',
        'Back to listing' => 'Zpět na výpis',
        'Previous' => 'Předchozí',
        'Next' => 'Další',
        'Save' => 'Uložit',
        'Delete' => 'Smazat',
        'New value:' => 'Nová hodnota:',
        'By type' => 'Podle typu',
        'Type' => 'Typ',
        'Count' => 'Počet',
        'empty' => 'prázdná',
        'Filter records' => 'Vyfiltrovat záznamy',
        'Insert' => 'Vložit',
        'Insert NULL' => 'Vložit hodnotu NULL',
        'Now' => 'Teď',
        'Unlock' => 'Odemknout',
        'Check all' => 'Označit všechny',
        'Listing' => 'Výpis',
        'variable' => 'proměnná',
        'value' => 'hodnota',
        'name' => 'jméno',
        'size' => 'velikost',
        'Descending' => 'Sestupně',
        'Other' => 'Ostatní',
        'Link will open in a new window' => 'Odkaz se otevře v novém okně',
        '--choose--' => '--vyberte--',
        'Really delete?' => 'Opravdu smazat?',
        'Select at least one file and try again.' => 'Označte alespoň jeden soubor a zkuste to ještě jednou.',
        // process messages
        'Could not save the record.' => 'Záznam se nepodařilo uložit.',
        'Could not delete the record.' => 'Záznam se nepodařilo smazat.',
        'Record saved.' => 'Záznam uložen.',
        'Record deleted.' => 'Záznam smazán.',
        'Deleted files: ' => 'Smazaných souborů: ',
        'You are logged in.' => 'Jste přihlášeni.',
        'Error occured logging You in.' => 'Při přihlašování nastala chyba.',
        'You are logged out.' => 'Jste odhlášeni.',
        'The new password was not retyped correctly.' => 'Nové heslo nebylo opsáno správně.',
        'Password was changed.' => 'Heslo změněno.',
        'Error occured changing password.' => 'Při změně hesla došlo k chybě.',
        "The subfolder doesn't exist." => 'Podsložka neexistuje.',
        'File was uploaded to server.' => 'Soubor byl nahrán na server.',
        'Error occured uploading the file to server.' => 'Soubor se nepodařilo nahrát na server.',
        'No files' => 'Žádné soubory',
        'No files selected.' => 'Nebyly vybrány žádné soubory.',
        'Total of processed files: ' => 'Celkem zpracovaných souborů: ',
        'Total of deleted files: ' => 'Celkem smazaných souborů: ',
        'User already exists.' => 'Uživatel již existuje.',
        'User added.' => 'Uživatel přidán.',
        'Error occured adding the user.' => 'Uživatele se nepodařilo přidat.',
        'User deleted.' => 'Uživatel smazán.',
        'Error occured deleting the user.' => 'Uživatele se nepodařilo smazat.',
        'Nothing to save.' => 'Nic k uložení.',
        'Nothing to change.' => 'Nic ke změně.',
        'Order change processed.' => 'Změna pořadí zpracována.',
        'Affected rows: ' => 'Ovlivněných záznamů: ',
        // project-specific
        'Categories and products' => 'Kategorie a produkty',
        'Categories' => 'Kategorie',
        'Products' => 'Produkty',
        'Articles' => 'Články',
        'Pages' => 'Stránky',
        'Agendas' => 'Agendy',
        'Login' => 'Přihlásit se',
        'Logout' => 'Odhlásit se',
        'User' => 'Uživatel',
        'Password' => 'Heslo',
        'Change password' => 'Změnit heslo',
        'Old password' => 'Staré heslo',
        'New password' => 'Nové heslo',
        'Retype password' => 'Opište heslo',
        "Passwords don't match!" => 'Hesla se neshodují!',
        'Please, fill necessary data.' => 'Prosím, vyplňte potřebná data.',
        'Create user' => 'Vytvořit uživatele',
        'Delete user' => 'Smazat uživatele',
        'Dashboard' => 'Palubová deska',
        'Media' => 'Média',
        'Upload' => 'Nahrát na server',
        'Reload' => 'Znovu nahrát',
        'Folder' => 'Složka',
        'Files' => 'Soubory',
        'Uploaded files' => 'Nahrané soubory',
        'Own value:' => 'Vlastní hodnota:',
        'Settings' => 'Nastavení',
        'Select' => 'Vyberte',
        'Move up' => 'Posunout nahoru',
        'Move down' => 'Posunout dolů',
        'Parent category' => 'Rodičovská kategorie',
        'Content linked to this category' => 'Obsah spojený s touto kategorií',
        'Products linked to this category' => 'Produkty spojené s touto kategorií',
        'Content linked to this product' => 'Obsah spojený s tímto produktem',
        'Select your agenda, then particular row.' => 'Vyberte agendu, poté záznam.',
        'For more detailed browsing with filtering etc. you may select one of the following tables…' => 'Pro detailnější procházení tabulek s filtrováním, řazením atd. klikněte na jednu z následujících…',
        // column:XXXX
        'column:category_id' => 'odkaz na kategorii',
        'column:product_id' => 'odkaz na produkt',
        'column:image' => 'obrázek',
        'column:type' => 'typ',
        'column:visits' => 'počet návštěv',
        'column:context' => 'kontext',
        'column:sort' => 'řazení',
        'column:active' => 'aktivní',
        'column:added' => 'přidáno',
        'column:code' => 'rozlišovací kód',
        'column:path' => 'cesta v hierarchii',
        'column:perex_cs' => 'perex (česky)',
        'column:perex_en' => 'perex (anglicky)',
        'column:intro_cs' => 'úvod (česky)',
        'column:intro_en' => 'úvod (anglicky)',
        'column:description_cs' => 'popis (česky)',
        'column:description_en' => 'popis (anglicky)',
        'column:product_cs' => 'název produktu (česky)',
        'column:product_en' => 'název produktu (anglicky)',
        'column:category_cs' => 'název kategorie (česky)',
        'column:category_en' => 'název kategorie (anglicky)',
        'column:content_cs' => 'název (česky)',
        'column:content_en' => 'název (anglicky)'
    );
    if (isset($_SESSION['language']) && $_SESSION['language'] == 'cs' && isset($TRANSLATION_CS[$text])) {
        return $TRANSLATION_CS[$text];
    }
    return $text;
}
