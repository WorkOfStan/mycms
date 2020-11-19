<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\LogMysqli;
use GodsDev\MyCMS\MyTableAdmin;
use GodsDev\Tools\Tools;

class TableAdmin extends MyTableAdmin
{
    use \Nette\SmartObject;

    /**
     *
     * @param LogMysqli $dbms database management system (e.g. new mysqli())
     * @param string $table table name
     * @param array $options
     */
    public function __construct(LogMysqli $dbms, $table, array $options = [])
    {
        parent::__construct($dbms, $table, $options);
        $this->TRANSLATIONS = $options['TRANSLATIONS'];
        if (Tools::setifempty($_SESSION['language'], 'en') == 'cs') {
            $this->TRANSLATION += [
                'Activate/deactivate' => 'Aktivovat/deaktivovat',
                'Affected files: ' => 'Ovlivněných souborů: ',
                'Affected rows: ' => 'Ovlivněných záznamů: ',
                'Agendas' => 'Agendy',
                'All rows' => 'Všechny řádky',
                'append' => 'přidat za',
                'Aren\'t they too big?' => 'Nejsou příliš velké?', //that's what she said
                'Archive created.' => 'Archiv vytvořen.',
                'Archive unpacked.' => 'Archiv rozbalen.',
                'Back to listing' => 'Zpět na výpis',
                'By type' => 'Podle typu',
                'Clone' => 'Klonovat',
                'close' => 'zavřít',
                'Columns' => 'Sloupce',
                'Convert' => 'Převést',
                'Could not delete the record.' => 'Záznam se nepodařilo smazat.',
                'Could not save the record.' => 'Záznam se nepodařilo uložit.',
                'Count' => 'Počet',
                'Create user' => 'Vytvořit uživatele',
                'current' => 'aktuální',
                'Dashboard' => 'Palubová deska',
                'Delete' => 'Smazat',
                'Delete user' => 'Smazat uživatele',
                'Deleted files: ' => 'Smazaných souborů: ',
                'descending' => 'sestupně',
                'descending order' => 'sestupné řazení',
                'Edit' => 'Upravit',
                'Edit selected' => 'Upravit vybrané',
                'empty' => 'prázdná',
                'Error activating the user.' => 'Uživatele se nepodařilo aktivovat.',
                'Error deactivating the user.' => 'Uživatele se nepodařilo deaktivovat.',
                'Error occured adding the user.' => 'Uživatele se nepodařilo přidat.',
                'Error occured automatically logging You in.' => 'Při automatickém přihlašování nastala chyba.',
                'Error occured deleting the user.' => 'Uživatele se nepodařilo smazat.',
                'Error occured changing password.' => 'Při změně hesla došlo k chybě.',
                'Error occured logging You in.' => 'Při přihlašování nastala chyba.',
                'Error occured opening the archive.' => 'Nepodařilo se otevřít archiv.',
                'Error occured renaming the file.' => 'Soubor se nepodařilo přesunout/přejmenovat.',
                'Error occured unpacking the archive.' => 'Při rozbalování archivu nastala chyba.',
                'Error occured uploading the file to server.' => 'Soubor se nepodařilo nahrát na server.',
                'Export' => 'Exportovat',
                'File' => 'Soubor',
                'File already exists.' => 'Soubor již existuje.',
                'File does not exist.' => 'Soubor neexistuje.',
                'File extensions must be the same.' => 'Přípony souborů musejí být stejné.',
                'File renamed.' => 'Soubor přejmenován.',
                'File was uploaded to server.' => 'Soubor byl nahrán na server.',
                'filename' => 'jméno souboru',
                'Files' => 'Soubory',
                'Filter records' => 'Vyfiltrovat záznamy',
                'Folder' => 'Složka',
                'For more detailed browsing with filtering etc. you may select one of the following tables…' =>
                'Pro detailnější procházení tabulek s filtrováním, řazením atd. klikněte na jednu z následujících…', // phpcs:ignore
                'go back' => 'jít zpět',
                'go to page' => 'Jít na stránku',
                'Change password' => 'Změnit heslo',
                'Check all' => 'Označit všechny',
                '--choose--' => '--vyberte--',
                'Image' => 'Obrázek',
                'Image selector' => 'Výběr obrázku',
                'Image URL' => 'URL obrázku',
                'Insert' => 'Vložit',
                'Insert NULL' => 'Vložit hodnotu NULL',
                'Link will open in a new window' => 'Odkaz se otevře v novém okně',
                'links' => 'odkazy',
                'Listing' => 'Výpis',
                'Login' => 'Přihlásit se',
                'Logout' => 'Odhlásit se',
                'Media' => 'Média',
                'modified' => 'změněno',
                'name' => 'jméno',
                'New password' => 'Nové heslo',
                'New record' => 'Nový záznam',
                'New row' => 'Nová řádka',
                'New value:' => 'Nová hodnota:',
                'Next' => 'Další',
                'No files' => 'Žádné soubory',
                'No files selected.' => 'Nebyly vybrány žádné soubory.',
                'No records found.' => 'Nebyly nalezeny žádné záznamy.',
                'No records selected.' => 'Nebyly vybrány žádné záznamy.',
                'None' => 'Žádné',
                'Nothing to change.' => 'Nic ke změně.',
                'Nothing to save.' => 'Nic k uložení.',
                'Now' => 'Teď',
                'Old password' => 'Staré heslo',
                'Order change processed.' => 'Změna pořadí zpracována.',
                'original' => 'původní',
                'Other' => 'Ostatní',
                'Own value:' => 'Vlastní hodnota:',
                'Pack' => 'Sbalit',
                'Page' => 'Stránka',
                'Password' => 'Heslo',
                'Password was changed.' => 'Heslo změněno.',
                'Passwords don\'t match!' => 'Hesla se neshodují!',
                'Please, fill necessary data.' => 'Prosím, vyplňte potřebná data.',
                'Please, fill up a valid file name.' => 'Prosím, zvolte platné jméno souboru.',
                'Please, choose a new name.' => 'Prosím, vyberte nové jméno.',
                'prepend' => 'přidat před',
                'Previous' => 'Předchozí',
                'Really delete?' => 'Opravdu smazat?',
                'Really?' => 'Opravdu?',
                'Record deleted.' => 'Záznam smazán.',
                'Record saved.' => 'Záznam uložen.',
                'Reload' => 'Znovu nahrát',
                'Rename' => 'Přejmenovat',
                'Retype password' => 'Opište heslo',
                'Rows per page' => 'Řádek na stránku',
                'Save' => 'Uložit',
                'Search' => 'Hledat',
                'Select' => 'Vyberte',
                'Select at least one file and try again.' =>
                'Označte alespoň jeden soubor a zkuste to ještě jednou.',
                'Selected records' => 'Vybrané záznamy',
                'Settings' => 'Nastavení',
                'Sidebar' => 'Postranní panel',
                'size' => 'velikost',
                'Sort' => 'Řadit',
                'stay here' => 'zůstat zde',
                'Text lengths' => 'Délky textů',
                'The new password was not retyped correctly.' => 'Nové heslo nebylo opsáno správně.',
                'The subfolder doesn\'t exist.' => 'Podsložka neexistuje.',
                'This function is not supported.' => 'Tato funkce není podporována.',
                'Toggle sidebar' => 'Zapnout/vypnout postranní panel',
                'total' => 'celkem',
                'Total of deleted files: ' => 'Celkem smazaných souborů: ',
                'Total of processed files: ' => 'Celkem zpracovaných souborů: ',
                'Total rows: ' => 'Celkem řádků: ',
                'Type' => 'Typ',
                'Unlock' => 'Odemknout',
                'Unpack' => 'Rozbalit',
                'Upload' => 'Nahrát na server',
                'Uploaded files' => 'Nahrané soubory',
                'User' => 'Uživatel',
                'User activated.' => 'Uživatel aktivován.',
                'User added.' => 'Uživatel přidán.',
                'User already exists.' => 'Uživatel již existuje.',
                'User deactivated.' => 'Uživatel deaktivován.',
                'User deleted.' => 'Uživatel smazán.',
                'value' => 'hodnota',
                'variable' => 'proměnná',
                'View' => 'Zobrazit',
                'Whole resultset' => 'Celý výsledek',
                'Wrong file name.' => 'Chybné jméno souboru.',
                'Wrong input' => 'Chybný vstup',
                'Wrong input parameter' => 'Chybný vstupní parametr',
                'You are logged in.' => 'Jste přihlášeni.',
                'You are logged out.' => 'Jste odhlášeni.',
                //ZipArchive
                'ZipArchive::1' => 'Multi-diskové zip archivy nejsou podporovány',
                'ZipArchive::2' => 'Selhalo dočasné přejmenování souboru',
                'ZipArchive::3' => 'Selhalo uzavření zip archivu',
                'ZipArchive::4' => 'Chyba posunu ukazatele do souboru',
                'ZipArchive::5' => 'Chyba čtení.',
                'ZipArchive::6' => 'Chyba zápisu',
                'ZipArchive::7' => 'Chyba CRC',
                'ZipArchive::8' => 'Obsažený zip archiv byl zavřen',
                'ZipArchive::9' => 'Neexistující soubor.',
                'ZipArchive::10' => 'Archiv již existuje.',
                'ZipArchive::11' => 'Soubor se nepodařilo otevřít.',
                'ZipArchive::12' => 'Selhalo vytvoření dočasného souboru',
                'ZipArchive::13' => 'Chyba knihovny Zlib',
                'ZipArchive::14' => 'Selhání alokace paměti.',
                'ZipArchive::15' => 'Položka byla změněna',
                'ZipArchive::16' => 'Nepodporovaná kompresní metoda',
                'ZipArchive::17' => 'Předčasný konec souboru',
                'ZipArchive::18' => 'Neplatný parametr.',
                'ZipArchive::19' => 'Není zip archiv.',
                'ZipArchive::21' => 'Nekonzistentní zip archiv.',
                'ZipArchive::20' => 'Interní chyba',
                'ZipArchive::22' => "Can't remove file",
                'ZipArchive::23' => 'Položka byla smazána',
                // project-specific
                'anchors' => 'kotvy',
                'Articles' => 'Články',
                'Categories' => 'Kategorie',
                'Categories and products' => 'Kategorie a produkty',
                'category' => 'kategorie',
                'content' => 'obsah',
                'Content linked to this category' => 'Obsah spojený s touto kategorií',
                'Content linked to this product' => 'Obsah spojený s tímto produktem',
                'Duplicit URL' => 'Duplicitní URL',
                'external' => 'externí',
                'internal' => 'interní',
                'Move down' => 'Posunout dolů',
                'Move up' => 'Posunout nahoru',
                'open' => 'otevřít',
                'Open/close' => 'otevřít/zavřít',
                'Pages' => 'Stránky',
                'parametrical' => 'parametrický',
                'Parent category' => 'Rodičovská kategorie',
                'product' => 'produkt',
                'Products' => 'Produkty',
                'Products linked to this category' => 'Produkty spojené s touto kategorií',
                'Select your agenda, then particular row.' => 'Vyberte agendu, poté záznam.',
                'Toggle' => 'Zapnout/vypnout',
                'Toggle image thumbnails' => 'Zapnout/vypnout náhledy obrázků',
                'Toggle inactive' => 'Zapnout/vypnout neaktivní',
                'Toggle number of texts' => 'Zapnout/vypnout počet textů',
                // column:XXXX
                'column:active' => 'aktivní',
                'column:added' => 'přidáno',
                'column:category_cs' => 'název kategorie (česky)',
                'column:category_en' => 'název kategorie (anglicky)',
                'column:category_id' => 'odkaz na kategorii',
                'column:code' => 'rozlišovací kód',
                'column:content_cs' => 'název (česky)',
                'column:content_en' => 'název (anglicky)',
                'column:context' => 'kontext',
                'column:description_cs' => 'popis (česky)',
                'column:description_en' => 'popis (anglicky)',
                'column:image' => 'obrázek',
                'column:intro_cs' => 'úvod (česky)',
                'column:intro_en' => 'úvod (anglicky)',
                'column:new_url' => 'URL (nové)',
                'column:old_url' => 'URL (staré)',
                'column:password_hashed' => 'heslo (šifrované)',
                'column:path' => 'cesta v hierarchii',
                'column:perex_cs' => 'perex (česky)',
                'column:perex_en' => 'perex (anglicky)',
                'column:product_cs' => 'název produktu (česky)',
                'column:product_en' => 'název produktu (anglicky)',
                'column:product_id' => 'odkaz na produkt',
                'column:rights' => 'práva',
                'column:sort' => 'řazení',
                'column:type' => 'typ',
                'column:url_cs' => 'URL (česky)',
                'column:url_en' => 'URL (anglicky)',
                'column:visits' => 'počet návštěv',
            ];
        } elseif ($_SESSION['language'] == 'en') {
            $this->TRANSLATION += [
                //ZipArchive
                'ZipArchive::1' => 'Multi-disk zip archives not supported',
                'ZipArchive::2' => 'Renaming temporary file failed',
                'ZipArchive::3' => 'Closing zip archive failed',
                'ZipArchive::4' => 'Seek error',
                'ZipArchive::5' => 'Read error',
                'ZipArchive::6' => 'Write error',
                'ZipArchive::7' => 'CRC error',
                'ZipArchive::8' => 'Containing zip archive was closed',
                'ZipArchive::9' => 'No such file',
                'ZipArchive::10' => 'File already exists',
                'ZipArchive::11' => 'Can\'t open file',
                'ZipArchive::12' => 'Failure to create temporary file',
                'ZipArchive::13' => 'Zlib error',
                'ZipArchive::14' => 'Malloc failure',
                'ZipArchive::15' => 'Entry has been changed',
                'ZipArchive::16' => 'Compression method not supported',
                'ZipArchive::17' => 'Premature EOF',
                'ZipArchive::18' => 'Invalid argument',
                'ZipArchive::19' => 'Not a zip archive',
                'ZipArchive::20' => 'Internal error',
                'ZipArchive::21' => 'Zip archive inconsistent',
                'ZipArchive::22' => "Can't remove file",
                'ZipArchive::23' => 'Entry has been deleted',
                // column:XXXX
                'column:category_cs' => 'Category (Czech)',
                'column:category_en' => 'Category (English)',
                'column:content_cs' => 'Content title (Czech)',
                'column:content_en' => 'Content title (English)',
                'column:description_cs' => 'Description (Czech)',
                'column:description_en' => 'Description (English)',
                'column:intro_cs' => 'Intro (Czech)',
                'column:intro_en' => 'Intro (English)',
                'column:new_url' => 'New URL',
                'column:old_url' => 'Old URL',
                'column:perex_cs' => 'Perex (Czech)',
                'column:perex_en' => 'Perex (English)',
                'column:product_cs' => 'Product name (Czech)',
                'column:product_en' => 'Product name (English)',
                'column:url_cs' => 'URL (Czech)',
                'column:url_en' => 'URL (English)',
            ];
        } elseif ($_SESSION['language'] == 'fr') {
            $this->TRANSLATION += [
                'Select your agenda, then particular row.' => 'Sélectionnez votre agenda, puis une ligne particulière.', // phpcs:ignore
            ];
        }
    }

    /**
     * Customize particular field's HTML of current $this->table
     *
     * @param string $field
     * @param string $value field's value
     * @param array $record
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customInput($field, $value, array $record = [])
    {
        $result = false;
        $fieldLang = '##';
        $fieldName = $field;
        if (substr($field, -3, 1) === '_' && in_array(substr($field, -2), array_keys($this->TRANSLATIONS))) {
            // Localised fields may have language independant behaviour
            $fieldLang = substr($field, -2); // language of the field
            $fieldName = substr($field, 0, -2) . '##';
        }
        switch (mb_substr($this->table, mb_strlen(TAB_PREFIX)) . "\\" . $fieldName) {
            //case "tableName\\fieldName": $result = ""; break; // SPECIMEN
            // URL fields have btn-webalize button
            case "content\\url_##":
                $result = '<div class="input-group">'
                    . Tools::htmlInput(
                        "fields[$field]",
                        '',
                        $value,
                        ['class' => 'form-control input-url', 'id' => $field . $this->rand]
                    )
                    . '<span class="input-group-btn">'
                    // btn-webalize referes to listener in admin.js
                    // TODO webalize according to the first row on the page (i.e. name, not description)
                    . '<button type="button" class="btn btn-secondary btn-webalize"'
                    . ' data-url="' . Tools::h($field . $this->rand) . '"'
                    . ' data-name="' . Tools::h(
                        mb_substr($this->table, mb_strlen(TAB_PREFIX)) . '_' . mb_substr($field, -2) . $this->rand
                    ) . '"'
                    . ' data-table="' . Tools::h($this->table) . '"'
                    . ' title="' . $this->translate('Convert') . '">'
                    . '<i class="fa fa-adjust" aria-hidden="true"></i></button>'
                    . '</span></div>';
                break;
        }
        return $result;
    }

    /**
     * Custom saving of a record. Record fields are in $_POST['fields'], other data in $_POST['database-table']
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customSave()
    {
        if (!isset($_POST['record-save']) || !$this->authorized()) {
            return false;
        }
        // category.path - if admin changes the parent category (or picks it for a new record)
        // @todo insert this into TableAdmin.php
        if (
            isset($_POST['table'], $_POST['path-original'], $_POST['path-parent'], $_POST['fields']['id']) &&
            $_POST['table'] == TAB_PREFIX . 'category' && (!Tools::begins(
                $_POST['path-original'],
                $_POST['path-parent']
            ) || Tools::set($_POST['fields-null']['path']))
        ) {
            $length = [strlen($_POST['path-parent']), strlen($_POST['path-original'])];
            // existing record whose parent category changed - we need to shift its sibblings
            // that follow after it to fill the gap
            if ($_POST['path-original'] && $_POST['fields']['id'] != '') {
                // we can't allow for other admin to write into categories during this op
                $this->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['table']) . ' WRITE');
                //category.path is a unique key so to allow for the following change we need to set this path to NULL
                $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                    . ' SET path = NULL WHERE id=' . (int) $_POST['fields']['id'] . ' LIMIT 1');
                if (Tools::set($_POST['fields-null']['path'])) {
                    $this->dbms->query('UNLOCK TABLES');
                    return false;
                }
                $_POST['original']['path'] = null;
                $update = $this->dbms->fetchAndReindex('SELECT id, CONCAT(LEFT(path, '
                    . ($length[1] - PATH_MODULE) . '),
                        LPAD(MID(path, ' . ($length[1] - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, '
                    . PATH_MODULE . ', "0"),
                        MID(path, ' . ($length[1] + PATH_MODULE + 1) . '))
                    FROM ' . Tools::escapeDbIdentifier($_POST['table']) . '
                    WHERE LEFT(path, ' . ($length[1] - PATH_MODULE) . ') = "'
                    . $this->dbms->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '"
                    AND LENGTH(path) >= ' . $length[1] . ' AND path > "'
                    . $this->dbms->escapeSQL($_POST['path-original']) . '"');
                if ($update) {
                    $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                        . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
                    foreach ($update as $key => $value) {
                        $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                            . ' SET path = "' . $this->dbms->escapeSQL($value) . '" WHERE id = ' . (int) $key);
                    }
                }
            }
            // get path of the "last" child of given parent category, add +1
            $tmp = $this->dbms->fetchSingle('SELECT MAX(MID(path, ' . ($length[0] + PATH_MODULE) . ')) FROM '
                . Tools::escapeDbIdentifier($_POST['table'])
                . ' WHERE LEFT(path, ' . $length[0] . ')="' . $this->dbms->escapeSQL($_POST['path-parent'])
                . '" AND LENGTH(path)=' . ($length[0] + PATH_MODULE));
            $_POST['fields']['path'] = $_POST['path-parent']
                . str_pad((string) (intval($tmp) + 1), PATH_MODULE, '0', STR_PAD_LEFT);
            $this->dbms->query('UNLOCK TABLES');
            return false;
        }
        // todo prozkoumat, co to udělá, když to dojde až sem, zda return false je správná odpověď
        return false;
    }

    /**
     * Custom deletion of a record
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customDelete()
    {
        if (!isset($_POST['record-delete']) || !$this->authorized()) {
            return false;
        }
        if (!$this->recordDelete()) {
            return true;
        }
        // After a category is deleted, shift categories after it accordingly.
        if ($_POST['table'] == TAB_PREFIX . 'category' && isset($_POST['path-original']) && $_POST['path-original']) {
            $length = strlen($_POST['path-original']);
            $update = $this->dbms->fetchAndReindex('SELECT id, CONCAT(LEFT(path, ' . ($length - PATH_MODULE) . '),
                    LPAD(MID(path, ' . ($length - PATH_MODULE + 1) . ', ' . PATH_MODULE . ') - 1, '
                . PATH_MODULE . ', "0"),
                    MID(path, ' . ($length + PATH_MODULE + 1) . '))
                FROM ' . Tools::escapeDbIdentifier($_POST['table']) . '
                WHERE LEFT(path, ' . ($length - PATH_MODULE) . ') = "'
                . $this->dbms->escapeSQL(substr($_POST['path-original'], 0, -PATH_MODULE)) . '"
                AND LENGTH(path) >= ' . $length . ' AND path > "'
                . $this->dbms->escapeSQL($_POST['path-original']) . '"');
            if ($update) {
                $this->dbms->query('LOCK TABLES ' . Tools::escapeDbIdentifier($_POST['table']) . ' WRITE');
                $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                    . ' SET path = NULL WHERE id IN (' . implode(', ', array_keys($update)) . ')');
                foreach ($update as $key => $value) {
                    $this->dbms->query('UPDATE ' . Tools::escapeDbIdentifier($_POST['table'])
                        . ' SET path = "' . $this->dbms->escapeSQL($value) . '" WHERE id = ' . (int) $key);
                }
                $this->dbms->query('UNLOCK TABLES');
            }
            return true;
        }
        // TODO prozkoumat, co to udělá, když to dojde až sem, zda return false je správná odpověď
        return false;
    }

    /**
     * Custom search. Called to optionally fill the search select
     *
     * @return void
     */
    public function customSearch()
    {
        // no action
    }

    /**
     * Custom condition for filtering.
     * Called to optionally fill conditions to WHERE clause of the SQL statement selecting given table
     *
     * @return void
     */
    public function customCondition()
    {
        // no action
    }

    /**
     * User-defined manipulating with column value of given table
     *
     * @param string $column
     * @param array $row
     * @return mixed
     */
    public function customValue($column, array $row)
    {
        return $row[$column];
    }
}
