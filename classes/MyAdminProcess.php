<?php

namespace GodsDev\MyCMS;

/**
 * Class to process standard operations in MyCMS
 * dependencies:
 *   ZipArchive
 *   TableAdmin.php
 */
use GodsDev\Tools\Tools;
use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyCommon;
use Tracy\Debugger;

class MyAdminProcess extends MyCommon
{
    /** @const int general limit of selected rows */
    const PROCESS_LIMIT = 100;

    /** @var \GodsDev\MyCMS\TableAdmin */
    protected $tableAdmin;

    /**
     * Ends Admin rendering with TracyPanels
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
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray));
        }
//        Debugger::barDump(debug_backtrace(), 'debug_backtrace');
    }

    /**
     * Tracy wrapper of AJAX calls, i.e. exit(json_encode($result))
     *
     * @param mixed $result Can be any type except a resource.
     * @return void
     */
    protected function exitJson($result)
    {
        header('Content-type: application/json');
        $this->endAdmin();
        exit(json_encode($result));
    }

    /**
     * Process the "export" action. If $post[download] is non-zero prompt the output as a download attachment.
     *
     * @param array &$post $_POST
     * @param array $get
     * @return void
     */
    public function processExport(&$post, $get)
    {
        if (isset($post['table-export'], $post['database-table'])) {
            if ((isset($post['check']) && count($post['check'])) || Tools::set($post['total-rows'])) {
                $sql = $where = '';
                if (Tools::set($post['total-rows'])) { //export whole resultset (regard possible $get limitations)
                    $columns = $this->tableAdmin->getColumns([]);
                    $sql = $this->tableAdmin->composeSQL($columns, $_GET);
                    $sql = $sql['select'];
                } else { //export only checked rows
                    $errors = [];
                    foreach ($post['check'] as $check) {
                        $partialWhere = '';
                        foreach (explode('&', $check) as $condition) {
                            $condition = explode('=', $condition);
                            if (count($condition) == 2 && Tools::begins($condition[0], 'where[') && Tools::ends($condition[0], ']')) { //@todo doesn't work for nulls
                                $condition[1] = is_null($condition[1]) ? ' IS NULL' : (is_numeric($condition[1]) ? ' = ' . $condition[1] : ' = "' . $this->tableAdmin->escapeSQL($condition[1]) . '"');
                                $partialWhere .= ' AND ' . $this->tableAdmin->escapeDbIdentifier(substr($condition[0], 5, -1)) . $condition[1];
                            } else {
                                $errors []= $condition[0];
                                $partialWhere = '';
                                break; 
                            }
                        }
                        if ($partialWhere) {
                            $where .= ' OR (' . substr($partialWhere, 6) . ')';
                        }
                    }
                    if ($errors) {
                        Tools::addMessage('warning', $this->tableAdmin->translate('Wrong input parameter') . ': ' . implode(', ', $errors));
                    }
                    if ($where) {                              
                        $sql='SELECT * FROM ' . $post['database-table'] . ' WHERE ' . substr($where, 4); //@todo columns hidden in view don't get affected
                    } else {
                        Tools::addMessage('info', $this->tableAdmin->translate('No records found.'));
                    }
                }
                if ($sql) {
                    $post['database-table'] = $this->tableAdmin->escapeDbIdentifier($post['database-table']);
                    $output = $this->MyCMS->fetchSingle('SHOW CREATE TABLE ' . $post['database-table']);
                    $output = "-- " . date('Y-m-d H:i:s') . "\n\n"
                        . "SET NAMES utf8;\n"
                        . "SET time_zone = '+00:00';\n"
                        . "SET foreign_key_checks = 0;\n"
                        . "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n"
                        . "DROP TABLE {$post['database-table']} IF EXISTS;\n{$output['Create Table']};\n\n"; //@todo specific to MySQL/MariaDb
                    $query = $this->MyCMS->dbms->query($sql);
                    $duplicateKey = '';
                    for ($i = 0; $row = $query->fetch_assoc(); $i++) {
                        if ($i % 5 == 0) {
                            $output = ($i ? substr($output, 0, -2) . ($duplicateKey = "\nON DUPLICATE KEY UPDATE " . $this->MyCMS->dbms->values($row, '%column% = VALUES(%column%)')) . ";\n" : $output) 
                                . "INSERT INTO " . $post['database-table'] . '(' . $this->MyCMS->dbms->values($row, 'fields') . ") VALUES\n";
                        }
                        $output .= '(' . $this->MyCMS->dbms->values($row, 'values') . "),\n";

                    }
                    $output = substr($output, 0, -2) . ($i ? $duplicateKey : '') . ";\n";
                    // we got output
                    if (Tools::set($post['download'])) {
                        header('Content-Disposition: attachment; filename=' . $post['database-table'] . '.sql;');
                        header('Content-Transfer-Encoding: binary');
                        header('Content-type: text/plain; charset=utf-8');
                    }
                    exit($output);
                } else {
                    Tools::addMessage('info', $this->tableAdmin->translate('No records selected.'));
                }
            } else {
                Tools::addMessage('info', $this->tableAdmin->translate('Wrong input parameters.'));
            }
        }
    }

    /**
     * Process the "file delete" action
     *
     * @param array &$post $_POST
     * @return void and output array JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFileDelete(&$post)
    {
        if (isset($post['subfolder'], $post['delete-files'])) {
            $result = [
                'processed-files' => 0,
                'success' => false,
                'messages' => ''
            ];
            if (is_dir(DIR_ASSETS . $post['subfolder']) && is_array($post['delete-files'])) {
                foreach ($post['delete-files'] as $value) {
                    if (unlink(DIR_ASSETS . $post['subfolder'] . "/$value")) {
                        $result['processed-files'] ++;
                    }
                }
                Tools::addMessage('info', $result['message'] = $this->tableAdmin->translate('Total of deleted files: ') . $result['processed-files'] . '.');
                $result['success'] = $result['processed-files'] > 0;
            }
            $this->exitJson($result);
        }
    }

    /**
     * Process the "file pack" action
     * Files are added into the archive from the current directory and stored without directory.
     * The ZipArchive->addFile() method is used. Standard file/error handling is used.
     *
     * @param array &$post $_POST
     * @return void and output array JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFilePack(&$post)
    {
        if (isset($post['subfolder'], $post['pack-files'], $post['archive'])) {
            $result = [
                'processed-files' => 0,
                'success' => false,
                'messages' => ''
            ];
            if (!$post['archive'] || !preg_match('~[a-z0-9-]\.zip~six', $post['archive'])) {
                $result['errors'] = $this->tableAdmin->translate('Please, fill up a valid file name.');
            } elseif (is_dir(DIR_ASSETS . $post['subfolder']) && is_array($post['pack-files']) && count($post['pack-files'])) {
                $path = DIR_ASSETS . $post['subfolder'] . '/';
                $ZipArchive = new \ZipArchive;
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
     * Process the "file rename" action
     *
     * @param array &$post
     * @return void and output array JSON array containing indexes: "success" (bool), "messages" (string) and "data" (string) of renamed file, if successful
     */
    public function processFileRename(&$post)
    {
        if (isset($post['file_rename'], $post['old_name'], $post['subfolder'], $post['new_folder'])) {
            $result = [
                'data' => $post['old_name'],
                'success' => false,
                'messages' => ''
            ];
            $post['file_rename'] = pathinfo($post['file_rename'], PATHINFO_BASENAME);
            $path = DIR_ASSETS . $post['subfolder'] . '/';
            $newpath = DIR_ASSETS . $post['new_folder'] . '/';
            if (!is_file($path . $post['old_name']) // @todo safety
                || !is_dir($path)
                || !is_dir($newpath)
                || !preg_match('/^([-\.\w]+)$/', $post['file_rename']) // apply some basic regex pattern
                || pathinfo($post['old_name'], PATHINFO_EXTENSION) != pathinfo($post['file_rename'], PATHINFO_EXTENSION) // old and new extension must be the same
                ) {
                $result['messages'] = $this->tableAdmin->translate('Error occured renaming the file.');
            } elseif (file_exists($newpath . $post['file_rename'])) {
                $result['messages'] = $this->tableAdmin->translate('File already exists.');
            } elseif (!rename($path . $post['old_name'], $newpath . $post['file_rename'])) {
                $result['messages'] = $this->tableAdmin->translate('Error occured renaming the file.');
            } else {
                Tools::addMessage('success', $tmp = $this->tableAdmin->translate('File renamed.') . ' (<a href="' . $newpath . $post['file_rename'] . '" target="_blank"><tt>' . Tools::h($newpath . $post['file_rename']) . '</tt></a>)');
                $result['data'] = $post['file_rename'];
                $result['messages'] = $tmp;
                $result['success'] = true;
            }
            if (!$result['success']) {
                $this->MyCMS->logger->warning('Error occured renaming the file. ' . $path . $post['old_name'] . ' --> ' . $newpath . $post['file_rename']);
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
    }

    /**
     * Process the "files unpack" action. Currently, only the zip files without password are supported.
     * The ZipArchive->extractTo() method is used. Subfolders will be created, preexisting files overwritten.
     * Security issues:
     * 1) white list of file extentions
     * 2) file size limitation
     *
     * @param array &$post $_POST
     * @return void and output array JSON array containing indexes: "success" (bool), "messages" (string), "processed-files" (int)
     */
    public function processFileUnpack(&$post)
    {
        if (isset($post['file_unpack'], $post['subfolder'], $post['new_folder'])) {
            $result = [
                'success' => false,
                'messages' => '',
                'processed-files' => 0
            ];
            $post['file_unpack'] = pathinfo($post['file_unpack'], PATHINFO_BASENAME);
            $path = DIR_ASSETS . $post['subfolder'] . '/';
            $ZipArchive = new \ZipArchive;
            if ($ZipArchive->open($path . $post['file_unpack']) === true) {
                // extract it to the path we determined above
                $result['success'] = $ZipArchive->extractTo(DIR_ASSETS . $post['new_folder'] . '/');
                $result['processed-files'] = $ZipArchive->numFiles;
                $result['messages'] = $result['success'] ? $this->tableAdmin->translate('Archive unpacked.') . ' ' . $this->tableAdmin->translate('Affected files: ') . $ZipArchive->numFiles . '.'
                    : $this->tableAdmin->translate('Error occured unpacking the archive.');
                Tools::addMessage($result['success'], $result['message']);
                $ZipArchive->close();
            } else {
                $result['messages'] = $this->tableAdmin->translate('Error occured unpacking the archive.');
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
    }

    /**
     * Process the "files upload" action
     *
     * @param array &$post $_POST
     * @return void and on success reload the page
     * @todo change to return bool success. Or add $post[redir] as an option
     */
    public function processFileUpload(&$post)
    {
        if (isset($post['upload-media'], $post['subfolder'])) {
            Debugger::barDump($_FILES, '_FILES');
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
            if (!$i) {
                Tools::addMessage('info', $this->tableAdmin->translate('No files selected.') . ' ' . $this->tableAdmin->translate("Aren't they too big?"));
            } else {
                Tools::addMessage('info', $this->tableAdmin->translate('Total of processed files: ') . $i . '.');
            }
            $this->redir();
        }
    }

    /**
     * Process the "login" action
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processLogin(&$post)
    {
        if (isset($post['user'], $post['password'], $post['login'])) {
            if (!isset($post['token']) || !$this->MyCMS->csrfCheck($post['token'])) {
                // let it fall into 'Error occured logging You in.'
            } elseif ($row = $this->MyCMS->fetchSingle('SELECT * FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($post['user']) . '"')) {
                if ($row['active'] == '1' && $row['password_hashed'] == sha1($post['password'] . $row['salt'])) {
                    $_SESSION['user'] = $post['user'];
                    $_SESSION['rights'] = $row['rights'];
                    $this->MyCMS->logger->info("Admin {$_SESSION['user']} logged in.");
                    Tools::addMessage('success', $this->tableAdmin->translate('You are logged in.'));
                    session_regenerate_id();
                    $this->redir();
                }
                $this->MyCMS->logger->warning('Admin not logged in - wrong password.');
            } else {
                $this->MyCMS->logger->warning('Admin not logged in - wrong name.');
            }
            Tools::addMessage('error', $this->tableAdmin->translate('Error occured logging You in.'));
            $this->redir();
        }
    }

    /**
     * Process the "logout" action
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processLogout(&$post)
    {
        if (isset($post['logout'])) {
            unset($_SESSION['user'], $_SESSION['rights'], $_SESSION['token']);
            Tools::addMessage('info', $this->tableAdmin->translate('You are logged out.'));
            $this->tableAdmin->script .= "localStorage.clear();\n";
            $this->redir();
        }
    }

    /**
     * Return files in /assets or its subfolder
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processSubfolder(&$post)
    {
        static $IMAGE_EXTENSIONS = ['jpg', 'gif', 'png', 'jpeg', 'bmp', 'wbmp', 'webp', 'xbm', 'xpm', 'swf', 'tif', 'tiff', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff', 'ico']; //file extensions the getimagesize() or exif_read_data() can read
        static $IMAGE_TYPE = ['unknown', 'GIF', 'JPEG', 'PNG', 'SWF', 'PSD', 'BMP', 'TIFF_II', 'TIFF_MM', 'JPC', 'JP2', 'JPX', 'JB2', 'SWC', 'IFF', 'WBMP', 'XBM', 'ICO', 'COUNT'];
        if (isset($post['media-files'], $post['subfolder'])) {
            $result = [
                'subfolder' => DIR_ASSETS . $post['subfolder'],
                'data' => [],
                'success' => true
            ];
            if (is_dir(DIR_ASSETS . $post['subfolder'])) {
                $_SESSION['assetsSubfolder'] = $post['subfolder'];
                Tools::setifnotset($post['info'], null);
                if ($post['info']) {
                    $ZipArchive = new \ZipArchive();
                }
                foreach (glob(DIR_ASSETS . $post['subfolder'] . '/' . (isset($post['wildcard']) ? $post['wildcard'] : '*.*'), isset($post['wildcard']) ? GLOB_BRACE : 0) as $file) {
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
                            if (in_array($pathinfo['extension'], $IMAGE_EXTENSIONS)) {
                                if ($size = getimagesize($file)) {
                                    $entry['info'] .= $size ? Tools::wrap(Tools::set($IMAGE_TYPE[$size[2]], ''), '', ' ') . $size[0] . '×' . $size[1] : '';
                                } elseif ($exif = exif_read_data($file)) {
                                    $entry['info'] .= Tools::wrap(Tools::set($IMAGE_TYPE[$exif['FILE']['FileType']]), ' ') . Tools::set($exif['COMPUTED']['Width']) . '×' . Tools::set($exif['COMPUTED']['Height']);
                                }
                            } elseif (substr($file, -4) == '.zip') {
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
            $this->exitJson($result);
        }
    }

    /**
     * Process the "user change activation" action
     *
     * @param array &$post $_POST
     * @return void and output array JSON array containing indexes: "success" (bool), "data" (string) admin name
     */
    public function processUserActivation(&$post)
    {
        if (isset($post['activate-user'], $post['active']) && is_numeric($post['activate-user'])) {
            $result = [
                'data' => ['id' => $post['activate-user']],
                'success' => $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin SET active="' . ($post['active'] ? 1 : 0) . '" WHERE id=' . +$post['activate-user'] . ' AND admin<>"' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"') && $this->MyCMS->dbms->affected_rows,
            ];
            $result['messages'] = $result['success'] ? ($post['active'] ? 'User activated.' : 'User deactivated.') : ($post['active'] ? 'Error activating the user.' : 'Error deactivating the user.');
            $result['messages'] = $this->tableAdmin->translate($result['messages']);
            $this->exitJson($result);
        }
    }

    /**
     * Process the "user change password" action
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processUserChangePassword(&$post)
    {
        if (isset($post['change-password'], $post['old-password'], $post['new-password'], $post['retype-password'])) {
            if ($post['new-password'] != $post['retype-password']) {
                Tools::addMessage('error', $this->tableAdmin->translate('The new password was not retyped correctly.'));
            } else {
                if ($row = $this->MyCMS->fetchSingle('SELECT * FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"')) {
                    if ($row['active'] == '1' && $row['password_hashed'] == sha1($post['old-password'] . $row['salt'])) {
                        Tools::resolve($this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin
                    SET password_hashed="' . $this->MyCMS->escapeSQL(sha1($post['new-password'] . $row['salt'])) . '"
                    WHERE admin="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"'), $this->tableAdmin->translate('Password was changed.'), $this->tableAdmin->translate('Error occured changing password.')
                        );
                        $this->redir();
                    }
                }
                Tools::addMessage('error', $this->tableAdmin->translate('Error occured changing password.'));
                $this->redir();
            }
        }
    }

    /**
     * Process the "user create" action
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processUserCreate(&$post)
    {
        if (isset($post['create-user'], $post['user'], $post['password'], $post['retype-password']) && $post['user'] && $post['password'] && $post['retype-password']) {
            $salt = mt_rand(1e8, 1e9);
            Tools::resolve(
                $this->MyCMS->dbms->query('INSERT INTO ' . TAB_PREFIX . 'admin SET admin="' . $this->MyCMS->escapeSQL($post['user']) . '", password_hashed="' . $this->MyCMS->escapeSQL(sha1($post['password'] . $salt)) . '", salt=' . $salt . ', rights=2'),
                $this->tableAdmin->translate('User added.'),
                $this->tableAdmin->translate($this->MyCMS->dbms->errorDuplicateEntry() ? 'User already exists.' : 'Error occured adding the user.')
            );
            $this->redir();
        }
    }

    /**
     * Process the "user delete" action
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processUserDelete(&$post)
    {
        if (isset($post['delete-user'])) {
            Tools::resolve($this->MyCMS->dbms->query('DELETE FROM ' . TAB_PREFIX . 'admin WHERE admin="' . $this->MyCMS->escapeSQL($post['delete-user']) . '" LIMIT 1'),
                $this->tableAdmin->translate('User deleted.'),
                $this->tableAdmin->translate('Error occured deleting the user.')
            );
            $this->redir();
        }
    }

    /**
     * Tracy wrapper of Tools::redir
     *
     * @param string $url OPTIONAL
     * @return void
     */
    protected function redir($url = '')
    {
        $this->endAdmin();
        Tools::redir($url);
    }

}