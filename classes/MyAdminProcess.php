<?php

namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;
use GodsDev\MyCMS\MyCommon;
use GodsDev\MyCMS\MyTableAdmin;
use GodsDev\MyCMS\Tracy\BarPanelTemplate;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;

use function GodsDev\MyCMS\ThrowableFunctions\filemtime;
use function GodsDev\MyCMS\ThrowableFunctions\glob;
use function GodsDev\MyCMS\ThrowableFunctions\json_encode;

/**
 * Class to process standard operations in MyCMS
 * dependencies:
 *   ZipArchive
 *   TableAdmin.php
 */
class MyAdminProcess extends MyCommon
{
    use \Nette\SmartObject;

    /** @const int general limit of selected rows and repeated fields in export */
    const PROCESS_LIMIT = 100;

    /** @var int how many seconds may pass before admin's record-editing tab is considered closed */
    protected $ACTIVITY_TIME_LIMIT = 180;

    /** @var MyTableAdmin */
    protected $tableAdmin;

    /**
     * Ends Admin rendering with TracyPanels.
     *
     * @return void
     */
    public function endAdmin()
    {
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION));
        }
        $sqlStatementsArray = $this->MyCMS->dbms->getStatementsArray();
        if (!empty($sqlStatementsArray)) {
            $sqlBarPanel = new BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray);
            if ($this->MyCMS->dbms->getStatementsError()) {
                $sqlBarPanel->setError();
            }
            Debugger::getBar()->addPanel($sqlBarPanel);
        }
//        Debugger::barDump(debug_backtrace(), 'debug_backtrace');
    }

    /**
     * Tracy wrapper of AJAX calls, i.e. exit(json_encode($result))
     *
     * @param mixed $result Can be any type except a resource.
     * @return never
     */
    protected function exitJson($result)
    {
        header('Content-type: application/json');
        $this->endAdmin();
        exit(json_encode($result));
    }

    /**
     * Convert variables confining records of a table into a WHERE clause of a SQL statement.
     *
     * @param array<string> $checks array of conditions, e.g. ["where[id]=4", "where[id]=5"]
     * @param array<string> $errors &$errors this variable is filled with
     * @return string SQL WHERE clause
     */
    protected function filterChecks(array $checks, array &$errors)
    {
        $errors = [];
        $result = '';
        foreach ($checks as $check) {
            $partial = '';
            foreach (explode('&', $check) as $condition) {
                $condition = explode('=', $condition, 2);
                if (Tools::begins($condition[0], 'where[') && Tools::ends($condition[0], ']')) { //@todo doesn't work for nulls
                    // TODO fix: Call to function is_null() with string will always evaluate to false.
                    $condition[1] = // is_null($condition[1]) ? ' IS NULL' : // TODO make this work for NULL values as well
                        (is_numeric($condition[1]) ? ' = ' . $condition[1] : ' = "' . $this->tableAdmin->escapeSQL($condition[1]) . '"');
                    $partial .= ' AND ' . $this->tableAdmin->escapeDbIdentifier(substr($condition[0], 5, -1)) . $condition[1];
                } else {
                    $errors [] = $condition[0];
                    $partial = '';
                    break;
                }
            }
            if ($partial) {
                $result .= ' OR (' . substr($partial, 6) . ')';
            }
        }
        return substr($result, 4);
    }

    /**
     * Process the "activity" action - update activity column of all admins, delete old tabs.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void (if engaged, outputs JSON and then return never)
     */
    public function processActivity(&$post)
    {
        if (isset($post['activity'], $post['table'], $post['id'])) {
            $admins = $this->MyCMS->fetchAll('SELECT id,activity,admin FROM ' . TAB_PREFIX . 'admin');
            $coeditors = []; // possible other admin(s) editing this record
            $online = []; // other admins online
            $time = time();
            foreach ($admins as $admin) {
                $tabs = json_decode($admin['activity'], true);
                $tabs = is_array($tabs) ? $tabs : [];
                $new = true;
                foreach ($tabs as $tabIndex => $tab) {
                    if (isset($tab['table'], $tab['id'], $tab['token'], $tab['time'])) {
                        if ($tab['table'] == $post['table'] && $tab['id'] == $post['id']) { //same record
                            if ($admin['admin'] != $_SESSION['user']) { // edited by different admin
                                $coeditors [] = $admin['admin'];
                            } elseif ($tab['token'] == $post['token']) { // same tab - update access time
                                $new = false;
                                $tabs[$tabIndex]['time'] = time();
                            }
                        }
                        if ($tab['time'] + $this->ACTIVITY_TIME_LIMIT < $time) { // delete tabs with "old" access times
                            unset($tabs[$tabIndex]);
                        }
                    }
                }
                $tabs = array_values($tabs);
                if ($admin['admin'] == $_SESSION['user']) {
                    if ($new) {
                        Assert::scalar($post['id']);
                        $tabs [] = [
                            'table' => $post['table'],
                            'id' => (int) $post['id'],
                            'token' => $post['token'],
                            'time' => time()
                        ];
                    }
                } elseif ($tabs) {
                    $online += $admin['admin'];
                }
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin SET activity = "' . $this->MyCMS->escapeSQL(json_encode($tabs ?: [])) . '" WHERE id = ' . (int) $admin['id']);
            }
            $this->exitJson(['success' => true, 'coeditors' => $coeditors, 'online' => $online]);
        }
    }

    /**
     * Process the "clone" action.
     * TODO check if this code is working as expected by AdminProcess call // clone table rows and fix unreachable code below accordingly (No CRS2 guidance)
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processClone(&$post)
    {
        if (isset($post['clone'], $post['database-table'])) {
            Assert::isArray($post['check']);
            if ((isset($post['check']) && count($post['check'])) || Tools::set($post['total-rows'])) {
                if (Tools::set($post['total-rows'])) {
                    $sql = $this->tableAdmin->selectSQL($this->tableAdmin->getColumns([]), $_GET);
                    Tools::dump($sql, $post);
                    exit; //@todo
                } else {
                    $sql = $this->filterChecks($post['check'], $errors);
                    Tools::dump($post, $sql);
                    exit; //@todo
                }
                // TODO Unreachable statement - code above always terminates. -- compare with similar Admin code
//                if ($sql) {
//                    $this->MyCMS->dbms->query('SELECT ');
//                } else {
//                    Tools::addMessage('info', $this->tableAdmin->translate('No records selected.'));
//                }
            } else {
                Tools::addMessage('info', 'Wrong input parameters.');
            }
        }
    }

    /**
     * Process the "export" action. If $post[download] is non-zero prompt the output as a download attachment.
     * TODO check if this code does what it should, as ... Metoda filterToSql neexistuje. $errors ani $sql nejsou definovány. (No CRS2 guidance)
     *
     * @param array<string|array> $post $_POST by reference
     * @param array<string|array> $get
     * @return void
     */
    public function processExport(&$post, $get)
    {
        //Debugger::barDump([$post, $get, $_GET], 'processExport(&$post, $get)');
        if (isset($post['table-export'], $post['database-table'])) {
            Assert::isArray($post['check']);
            if ((isset($post['check']) && count($post['check'])) || Tools::set($post['total-rows'])) {
                if (Tools::set($post['total-rows'])) { //export whole resultset (regard possible $get limitations)
                    $sql = $this->tableAdmin->selectSQL($this->tableAdmin->getColumns([]), $get);
                    $sql = $sql['select'];
                //Debugger::barDump($sql, 'SQL array');
                } else { //export only checked rows
                    Assert::string($post['database-table']);
                    $sql = $this->tableAdmin->selectSQL($this->tableAdmin->getColumns([]), $get);
                    $sql = $sql['select'] . ' WHERE `' . $post['database-table'] . '`.`id` IN (' .
                        implode(
                            ',',
                            array_map(
                                function ($whereString) {
                                    $tempSplit = explode('=', $whereString);
                                    return (int) $tempSplit[1];
                                },
                                $post['check'] // array of strings like that: 'where[id]=1'
                            )
                        ) . ')';
                    Debugger::barDump($sql, 'SQL array');
                }
                Assert::string($sql);
                if ($sql) {
                    Assert::string($post['database-table']);
                    $post['database-table'] = $this->tableAdmin->escapeDbIdentifier($post['database-table']);
                    $output = $this->MyCMS->fetchSingle('SHOW CREATE TABLE ' . $post['database-table']);
                    $output = "-- " . date('Y-m-d H:i:s') . "\n"
                        . "-- " . $this->MyCMS->fetchSingle('SELECT VERSION()') . "\n\n"
                        . "SET NAMES utf8;\n"
                        . "SET time_zone = '+00:00';\n"
                        . "SET foreign_key_checks = 0;\n"
                        . "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n"
                        . "DROP TABLE {$post['database-table']} IF EXISTS;\n{$output['Create Table']};\n\n"; //@todo specific to MySQL/MariaDb
                    $query = $this->MyCMS->dbms->queryStrictObject($sql);
                    $duplicateKey = '';
                    for ($i = 0; $row = $query->fetch_assoc(); $i++) {
                        if ($i % self::PROCESS_LIMIT == 0) {
                            $output = ($i ? substr($output, 0, -2) . ($duplicateKey = "\nON DUPLICATE KEY UPDATE " . $this->MyCMS->dbms->values($row, '%column% = VALUES(%column%)')) . ";\n" : $output)
                                . "INSERT INTO " . $post['database-table'] . '(' . $this->MyCMS->dbms->values($row, 'fields') . ") VALUES\n";
                        }
                        $output .= '(' . $this->MyCMS->dbms->values($row, 'values') . "),\n";
                    }
                    $output = substr($output, 0, -2) . ($i ? $duplicateKey : '') . ";\n";
                    // we got output
                    // TODO: add download/display-only option
//                    if (Tools::set($post['download'])) {
                    header('Content-Disposition: attachment; filename=' . $post['database-table']
                        . '_' . date('Y-m-d') . '.sql;');
                    header('Content-Transfer-Encoding: binary');
//                    }
                    header('Content-type: text/plain; charset=utf-8');
                    // todo: display SQL here instead of Debugger::barDump($sql, 'SQL array'); above//$this->endAdmin();
                    exit($output);
                } else {
                    Tools::addMessage('info', $this->tableAdmin->translate('No records selected.'));
                }
            } else {
                Tools::addMessage('info', 'Wrong input parameters.');
            }
        }
    }

    /**
     * Process the "file delete" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void and may output array
     *   JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFileDelete(&$post)
    {
        if (isset($post['subfolder'], $post['delete-files'])) {
            $result = [
                'processed-files' => 0,
                'success' => false,
                'messages' => ''
            ];
            Assert::string($post['subfolder']);
            if (is_dir(DIR_ASSETS . $post['subfolder']) && is_array($post['delete-files'])) {
                foreach ($post['delete-files'] as $value) {
                    if (unlink(DIR_ASSETS . $post['subfolder'] . "/$value")) {
                        $result['processed-files']++;
                    }
                }
                Tools::addMessage(
                    'info',
                    $result['message'] = $this->tableAdmin->translate('Total of deleted files: ')
                    . $result['processed-files'] . '.'
                );
                $result['success'] = $result['processed-files'] > 0;
            }
            $this->exitJson($result); // terminates
        }
    }

    /**
     * Process the "file pack" action.
     * Files are added into the archive from the current directory and stored without directory.
     * The ZipArchive->addFile() method is used. Standard file/error handling is used.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void and may output array
     *   JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFilePack(&$post)
    {
        if (isset($post['subfolder'], $post['pack-files'], $post['archive'])) {
            Assert::string($post['subfolder']);
            Assert::string($post['archive']);
            $result = [
                'processed-files' => 0,
                'success' => false,
                'messages' => ''
            ];
            if (!$post['archive'] || !preg_match('~[a-z0-9-]\.zip~six', $post['archive'])) {
                $result['errors'] = $this->tableAdmin->translate('Please, fill up a valid file name.');
            } elseif (!class_exists('\ZipArchive')) {
                $result['messages'] = $this->tableAdmin->translate('This function is not supported.');
            } elseif (is_dir(DIR_ASSETS . $post['subfolder']) && is_array($post['pack-files']) && count($post['pack-files'])) {
                $path = DIR_ASSETS . $post['subfolder'] . '/';
                $ZipArchive = new \ZipArchive();
                if ($open = $ZipArchive->open($path . $post['archive'], \ZipArchive::CREATE) === true) {
                    foreach ($post['pack-files'] as $file) {
                        if (file_exists($path . $file)) {
                            $result['processed-files'] += $ZipArchive->addFile($path . $file, $file) ? 1 : 0;
                        }
                    }
                    $result['success'] = $ZipArchive->close() && ($result['processed-files'] > 0);
                    $result['messages'] = $result['success'] ? $this->tableAdmin->translate('Archive created.') . ' <tt><a href="' . ($file = $path . $post['archive']) . '">' . $file . '</a></tt>' : $this->tableAdmin->translate('Error occured opening the archive.');
                    Tools::resolve($result['success'], $result['messages'], $result['messages']);
                    $tmp = $this->tableAdmin->translate('Total of processed files: ') . $result['processed-files'] . '.';
                    Tools::addMessage('info', $tmp);
                    $result['messages'] .= "<br />$tmp";
                } else {
                    $result['messages'] = $this->tableAdmin->translate('Error occured opening the archive.') . ' – ' . $this->tableAdmin->translate('ZipArchive::' . $open);
                }
            } else {
                $result['messages'] = $this->tableAdmin->translate('Wrong input parameter');
            }
            $this->exitJson($result);
        }
    }

    /**
     * Process the "file rename" action.
     * $post indexes [old_name], [file_rename], [subfolder] and [new_folder] are required.
     * If [new_folder] <> [subfolder] then file will be moved, otherwise just renamed.
     * If the new file exists then the operation is aborted.
     * New file name must keep the same extension and consist only of letters, digits or ( ) . _
     *
     * @param array<string|array> $post $_POST by reference
     * @return void but if engaged return never and outputs JSON
     *   containing indexes: "success" (bool), "messages" (string) and "data" (string) of renamed file, if successful
     */
    public function processFileRename(&$post)
    {
        if (isset($post['file_rename'], $post['old_name'], $post['subfolder'], $post['new_folder'])) {
            $result = [
                'data' => $post['old_name'],
                'success' => false,
                'messages' => ''
            ];
            Assert::string($post['file_rename']);
            $post['file_rename'] = pathinfo($post['file_rename'], PATHINFO_BASENAME);
            Assert::string($post['subfolder']);
            $path = DIR_ASSETS . strtr($post['subfolder'], '../', '') . '/';
            Assert::string($post['new_folder']);
            $newpath = DIR_ASSETS . strtr($post['new_folder'], '../', '') . '/';
            Assert::string($post['old_name']);
            if (!is_file($path . $post['old_name'])) {
                $result['messages'] = $this->tableAdmin->translate('File does not exist.');
            } elseif (pathinfo($post['old_name'], PATHINFO_EXTENSION) != pathinfo($post['file_rename'], PATHINFO_EXTENSION)) {
                $result['messages'] = $this->tableAdmin->translate('File extensions must be the same.');
            } elseif (!is_dir($path) || !is_dir($newpath)) {
                $result['messages'] = $this->tableAdmin->translate("The subfolder doesn't exist.");
            } elseif (file_exists($newpath . $post['file_rename'])) {
                $result['messages'] = $this->tableAdmin->translate('File already exists.');
            } elseif (!preg_match('/^([-\.\w\(\)]+)$/', $post['file_rename'])) { // apply some basic regex pattern
                $result['messages'] = $this->tableAdmin->translate('Wrong file name.');
            } elseif (!rename($path . $post['old_name'], $newpath . $post['file_rename'])) {
                $result['messages'] = $this->tableAdmin->translate('Error occured renaming the file.');
            } else {
                $result['messages'] = $this->tableAdmin->translate('File renamed.') . ' (<a href="' . $newpath . $post['file_rename'] . '" target="_blank"><tt>' . Tools::h($newpath . $post['file_rename']) . '</tt></a>)';
                Tools::addMessage('success', $result['messages']);
                $result['data'] = $post['file_rename'];
                $result['success'] = true;
            }
            if (!$result['success']) {
                $this->MyCMS->logger->warning('Error occured renaming the file. ' . $path . $post['old_name'] . ' --> ' . $newpath . $post['file_rename']);
            }
            $this->exitJson($result);
        }
    }

    /**
     * Process the "files unpack" action. Currently, only the zip files without password are supported.
     * The ZipArchive->extractTo() method is used. Subfolders will be created, preexisting files overwritten.
     * Security issues:
     * 1) white list of file extentions
     * 2) file size limitation
     *
     * @param array<string|array> $post $_POST by reference
     * @return void but if engaged return never and outputs JSON
     *   JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFileUnpack(&$post)
    {
        if (isset($post['file_unpack'], $post['subfolder'], $post['new_folder'])) {
            $result = [
                'success' => false,
                'messages' => '',
                'processed-files' => 0
            ];
            if (class_exists('\ZipArchive')) {
                Assert::string($post['file_unpack']);
                $post['file_unpack'] = pathinfo($post['file_unpack'], PATHINFO_BASENAME);
                Assert::string($post['subfolder']);
                $path = DIR_ASSETS . $post['subfolder'] . '/';
                $ZipArchive = new \ZipArchive();
                if ($ZipArchive->open($path . $post['file_unpack']) === true) {
                    // extract it to the path we determined above
                    Assert::string($post['new_folder']);
                    $result['success'] = $ZipArchive->extractTo(DIR_ASSETS . $post['new_folder'] . '/');
                    $result['processed-files'] = $ZipArchive->numFiles;
                    $result['messages'] = $result['success'] ? $this->tableAdmin->translate('Archive unpacked.') . ' ' . $this->tableAdmin->translate('Affected files: ') . $ZipArchive->numFiles . '.' : $this->tableAdmin->translate('Error occured unpacking the archive.');
                    Tools::addMessage($result['success'], $result['messages']);
                    $ZipArchive->close();
                } else {
                    $result['messages'] = $this->tableAdmin->translate('Error occured unpacking the archive.');
                }
            } else {
                $result['messages'] = $this->tableAdmin->translate('This function is not supported.');
            }
            $this->exitJson($result);
        }
    }

    /**
     * Process the "files upload" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void and on success reload the page
     * @todo change to return bool success. Or add $post[redir] as an option
     */
    public function processFileUpload(&$post)
    {
        if (isset($post['upload-media'], $post['subfolder'])) {
            Debugger::barDump($_FILES, '_FILES');
            Assert::string($post['subfolder']);
            if (!file_exists(DIR_ASSETS . $post['subfolder'])) {
                Tools::addMessage('error', $this->tableAdmin->translate("The subfolder doesn't exist."));
                $this->redir();
            }
            $_SESSION['subfolder'] = $post['subfolder'];
            $i = 0;
            foreach ($_FILES['files']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $name = basename($_FILES['files']['name'][$key]);
                    $result = move_uploaded_file($_FILES['files']['tmp_name'][$key], DIR_ASSETS . $post['subfolder'] . ($post['subfolder'] ? '/' : '') . $name);
                    // @todo any other safety issues?
                    Tools::addMessage($result ? 'success' : 'error', '<tt>' . Tools::h($name) . '</tt> '
                        . ($result ? $this->tableAdmin->translate('File was uploaded to server.') : $this->tableAdmin->translate('Error occured uploading the file to server.')));
                    $i++;
                } elseif ($_FILES['files']['name'][$key]) {
                    Debugger::log("Upload error {$error} for '{$_FILES['files']['name'][$key]}'", \Tracy\ILogger::WARNING);
                }
            }
            Tools::addMessage(
                'info',
                !$i ?
                    $this->tableAdmin->translate('No files selected.') . ' '
                    . $this->tableAdmin->translate("Aren't they too big?")  // fail
                    : $this->tableAdmin->translate('Total of processed files: ') . $i . '.' // ok
            );
            $this->redir();
        }
    }

    /**
     * Process the "login" action.
     * Also checks for CSRF
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processLogin(&$post)
    {
        if (isset($post['user'], $post['password'], $post['login'])) {
            Assert::string($post['user']);
            Assert::scalar($post['token']);
            if (!isset($post['token']) || !$this->MyCMS->csrfCheck((int) $post['token'])) {
                // let it fall to 'Error occured logging You in.'
            } elseif ($row = $this->MyCMS->fetchSingle('SELECT * FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($post['user']) . '"')) {
                if ($row['active'] == '1' && $row['password_hashed'] == sha1($post['password'] . $row['salt'])) {
                    Assert::string($post['password']);
                    $_SESSION['user'] = $post['user'];
                    $_SESSION['rights'] = $row['rights'];
                    $this->MyCMS->logger->info("Admin {$_SESSION['user']} logged in.");
                    session_regenerate_id();
                    setcookie('mycms_login', $post['user'] . "\0" . Tools::xorCipher($post['password'], MYCMS_SECRET), time() + 86400 * 30, '', '', true);
                    if (!isset($post['autologin'])) {
                        Tools::addMessage('success', $this->tableAdmin->translate('You are logged in.'));
                        $this->redir();
                    }
                }
                $this->MyCMS->logger->warning('Admin not logged in - wrong password.');
            } else {
                $this->MyCMS->logger->warning('Admin not logged in - wrong name.');
            }
            Tools::addMessage('error', isset($post['autologin']) ? $this->tableAdmin->translate('Error occured automatically logging You in.') : $this->tableAdmin->translate('Error occured logging You in.'));
            if (!isset($post['no-redir']) && !isset($post['autologin'])) {
                $this->redir();
            }
        }
    }

    /**
     * Process the "logout" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processLogout(&$post)
    {
        if (isset($post['logout'])) {
            unset($_SESSION['user'], $_SESSION['rights'], $_SESSION['token']);
            Tools::addMessage('info', $this->tableAdmin->translate('You are logged out.'));
            setcookie('mycms_login', '', 0);
            $this->tableAdmin->script .= "localStorage.clear();\n";
            $this->redir();
        }
    }

    /**
     * Return files in /assets or its subfolder
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processSubfolder(&$post)
    {
        static $IMAGE_EXTENSIONS = ['jpg', 'gif', 'png', 'jpeg', 'bmp', 'wbmp', 'webp', 'xbm', 'xpm', 'swf', 'tif', 'tiff', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff', 'ico']; //file extensions the getimagesize() or exif_read_data() can read
        static $IMAGE_TYPE = ['unknown', 'GIF', 'JPEG', 'PNG', 'SWF', 'PSD', 'BMP', 'TIFF_II', 'TIFF_MM', 'JPC', 'JP2', 'JPX', 'JB2', 'SWC', 'IFF', 'WBMP', 'XBM', 'ICO', 'COUNT'];
        if (isset($post['media-files'], $post['subfolder'])) {
            Assert::string($post['subfolder']);
            $result = [
                'subfolder' => DIR_ASSETS . $post['subfolder'],
                'data' => [],
                'success' => true
            ];
            if (is_dir(DIR_ASSETS . $post['subfolder'])) {
                $_SESSION['assetsSubfolder'] = $post['subfolder'];
                Tools::setifnotset($post['info'], null);
                if ($post['info'] && class_exists('\ZipArchive')) {
                    $ZipArchive = new \ZipArchive();
                }
                Assert::string($post['subfolder']);
//                Assert::string($post['wildcard']); // TODO fix Expected a string. Got: NULL
                foreach (
                    glob(
                        DIR_ASSETS . $post['subfolder'] . '/' . (isset($post['wildcard']) ? $post['wildcard'] : '*.*'),
                        isset($post['wildcard']) ? GLOB_BRACE : 0
                    ) as $file
                ) {
                    if (is_file($file)) {
                        $pathinfo = pathinfo($file);
                        $entry = [
                            'name' => $pathinfo['filename'],
                            'extension' => isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '',
                            'size' => filesize($file),
                            'modified' => date("Y-m-d H:i:s", filemtime($file)),
                            'info' => ''
                        ];
                        if ($post['info']) {
                            Assert::keyExists($pathinfo, 'extension');
                            if (in_array($pathinfo['extension'], $IMAGE_EXTENSIONS)) {
                                $size = getimagesize($file);
                                $entry['info'] .= $size ?
                                    Tools::wrap(Tools::set($IMAGE_TYPE[$size[2]], ''), '', ' ') . $size[0] . '×' . $size[1] :
                                    (
                                        ($exif = exif_read_data($file)) ? (Tools::wrap(Tools::set($IMAGE_TYPE[$exif['FILE']['FileType']]), ' ') . Tools::set($exif['COMPUTED']['Width']) . '×' . Tools::set($exif['COMPUTED']['Height'])) : ''
                                    );
                            /**
                             * @phpstan-ignore-next-line
                             * $ZipArchive is created within condition ($post['info'] && class_exists('\ZipArchive'))
                             * above and the elseif below embodies the same condition, albeit divided into 2 ifs
                             */
                            } elseif (substr($file, -4) == '.zip' && is_a($ZipArchive, '\ZipArchive')) {
                                if ($ZipArchive->open($file)) {
                                    for ($i = 0; $i < min($ZipArchive->numFiles, 10); $i++) {
                                        $entry['info'] .= $ZipArchive->getNameIndex($i) . "\n";
                                    }
                                    $entry['info'] .= ($ZipArchive->numFiles > 10 ? "…\n" : "") . $this->tableAdmin->translate('total') . ': ' . $ZipArchive->numFiles;
                                }
                            }
                        }
                        $result['data'] [] = $entry;
                    }
                }
            } else {
                $result['subfolder'] = DIR_ASSETS . ($_SESSION['mediaSubfolder'] = '');
                $result['success'] = false;
            }
            $this->exitJson($result); // terminates
        }
    }

    /**
     * Process the "user change activation" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void and output array JSON array containing indexes: "success" (bool), "data" (string) admin name
     */
    public function processUserActivation(&$post)
    {
        if (isset($post['activate-user'], $post['active']) && is_numeric($post['activate-user'])) {
            $result = [
                'data' => ['id' => $post['activate-user']],
                'success' => $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin SET active="' . ($post['active'] ? 1 : 0) . '" WHERE id=' . (int) $post['activate-user'] . ' AND admin<>"' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"') && $this->MyCMS->dbms->affected_rows,
            ];
            $result['messages'] = $result['success'] ? ($post['active'] ? 'User activated.' : 'User deactivated.') : ($post['active'] ? 'Error activating the user.' : 'Error deactivating the user.');
            $result['messages'] = $this->tableAdmin->translate($result['messages']);
            $this->exitJson($result); // terminates
        }
    }

    /**
     * Process the "user change password" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processUserChangePassword(&$post)
    {
        if (!isset($post['change-password'], $post['old-password'], $post['new-password'], $post['retype-password'])) {
            return; // return void
        }
        if ($post['new-password'] != $post['retype-password']) {
            Tools::addMessage('error', $this->tableAdmin->translate('The new password was not retyped correctly.'));
            return; // return void
        }
        $row = $this->MyCMS->fetchSingle(
            'SELECT * FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"'
        );
        if ($row && $row['active'] === '1' && $row['password_hashed'] == sha1($post['old-password'] . $row['salt'])) {
            $result = $this->MyCMS->dbms->query(
                'UPDATE ' . TAB_PREFIX . 'admin SET password_hashed="' . $this->MyCMS->escapeSQL(
                    sha1($post['new-password'] . $row['salt'])
                ) . '" WHERE admin="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"',
                1, // log errors
                false // don't log query! (security)
            );
            if ($result) {
                Tools::addMessage('success', $this->tableAdmin->translate('Password was changed.'));
                Debugger::log("User '{$_SESSION['user']}' changed password.", ILogger::INFO);
            } else {
                Tools::addMessage('error', $this->tableAdmin->translate('Error occured changing password.'));
            }
            $this->redir();
        }
        Tools::addMessage('error', $this->tableAdmin->translate('Error occured changing password.'));
        Debugger::log("User '{$_SESSION['user']}' failed changing password.", ILogger::INFO);
        $this->redir();
    }

    /**
     * Process the "user create" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processUserCreate(&$post)
    {
        if (isset($post['create-user'], $post['user'], $post['password'], $post['retype-password']) && $post['user'] && $post['password'] && $post['retype-password']) {
            Assert::string($post['user']);
            $salt = mt_rand((int) 1e8, (int) 1e9);
            Tools::resolve(
                $this->MyCMS->dbms->queryStrictBool(
                    'INSERT INTO ' . TAB_PREFIX . 'admin SET admin="' . $this->MyCMS->escapeSQL($post['user']) . '", password_hashed="' . $this->MyCMS->escapeSQL(sha1($post['password'] . $salt)) . '", salt=' . $salt . ', rights=2',
                    1,
                    false
                ),
                $this->tableAdmin->translate('User added.'),
                $this->tableAdmin->translate($this->MyCMS->dbms->errorDuplicateEntry() ? 'User already exists.' : 'Error occured adding the user.')
            );
            Debugger::log("User '{$post['user']}' was created by '{$_SESSION['user']}'.", ILogger::INFO);
            $this->redir();
        }
    }

    /**
     * Process the "user delete" action.
     *
     * @param array<string|array> $post $_POST by reference
     * @return void
     */
    public function processUserDelete(&$post)
    {
        if (isset($post['delete-user'])) {
            Assert::string($post['delete-user']);
            Tools::resolve(
                $this->MyCMS->dbms->queryStrictBool('DELETE FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($post['delete-user']) . '" LIMIT 1'),
                $this->tableAdmin->translate('User deleted.'),
                $this->tableAdmin->translate('Error occured deleting the user.')
            );
            $this->redir();
        }
    }

    /**
     * Tracy wrapper of Tools::redir.
     *
     * @param string $url OPTIONAL
     * @return never
     */
    protected function redir($url = '')
    {
        $this->endAdmin();
        Tools::redir($url, 303, false);
    }
}
