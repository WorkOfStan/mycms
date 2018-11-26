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
     * Process the "file delete" action
     *
     * @param &$post $_POST
     * @return void
     */
    public function processFileDelete(&$post)
    {
        if (isset($post['subfolder'], $post['delete-files'])) {
            $result = array(
                'deleted-files' => 0,
                'success' => false
            );
            if (is_dir(DIR_ASSETS . $post['subfolder']) && is_array($post['delete-files'])) {
                foreach ($post['delete-files'] as $value) {
                    if (unlink(DIR_ASSETS . $post['subfolder'] . "/$value")) {
                        $result['deleted-files'] ++;
                    }
                }
                Tools::addMessage('info', $this->tableAdmin->translate('Total of deleted files: ') . $result['deleted-files'] . '.');
                $result['success'] = $result['deleted-files'] > 0;
            }
            $this->exitJson($result);
        }
    }

    /**
     * Process the "file rename" action
     *
     * @param array &$post
     * @return array JSON array containing indexes: "success" (bool) and "data" (string of renamed file, if successful)
     */
    public function processFileRename(&$post)
    {
        if (isset($post['file_rename'], $post['old_name'], $post['subfolder'], $post['new_folder'])) {
            $result = array(
                'data' => $post['old_name'],
                'success' => false
            );
            $post['file_rename'] = pathinfo($post['file_rename'], PATHINFO_BASENAME);
            $path = DIR_ASSETS . $post['subfolder'] . '/';
            $newpath = DIR_ASSETS . $post['new_folder'] . '/';
            if (!is_file($path . $post['old_name']) // @todo safety
                || !is_dir($path)
                || !is_dir($newpath)
                || !preg_match('/^([-\.\w]+)$/', $post['file_rename']) // apply some basic regex pattern
                || pathinfo($post['old_name'], PATHINFO_EXTENSION) != pathinfo($post['file_rename'], PATHINFO_EXTENSION) // old and new extension must be the same
                ) {
                $result['error'] = $this->tableAdmin->translate('Error occured renaming the file.');
            } elseif (file_exists($newpath . $post['file_rename'])) {
                $result['error'] = $this->tableAdmin->translate('File already exists.');
            } elseif (!rename($path . $post['old_name'], $newpath . $post['file_rename'])) {
                $result['error'] = $this->tableAdmin->translate('Error occured renaming the file.');
            } else {
                Tools::addMessage('success', $this->tableAdmin->translate('File renamed.') . ' (<a href="' . $newpath . $post['file_rename'] . '" target="_blank"><tt>' . Tools::h($newpath . $post['file_rename']) . '</tt></a>)');
                $result = array('data' => $post['file_rename'], 'success' => true);
            }
            if (!$result['success']) {
                $this->MyCMS->logger->warning($this->tableAdmin->translate('Error occured renaming the file.') . ' ' . $path . $post['old_name'] . ' --> ' . $newpath . $post['file_rename']);
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
    }

    /**
     * Process the "files unpack" action. Currently, only the zip files without password are supported.
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processFileUnpack(&$post)
    {
        if (isset($post['file_unpack'], $post['subfolder'], $post['new_folder'])) {
            $result = array('success' => false, 'data' => '');
            $post['file_unpack'] = pathinfo($post['file_unpack'], PATHINFO_BASENAME);
            $path = DIR_ASSETS . $post['subfolder'] . '/';
            $ZipArchive = new \ZipArchive;
            if ($ZipArchive->open($path . $post['file_unpack']) === true) {
                // extract it to the path we determined above
                $result['success'] = $ZipArchive->extractTo(DIR_ASSETS . $post['new_folder'] . '/');
                $result['data'] = $result['success'] ? $this->tableAdmin->translate('Archive unpacked.') . ' ' . $this->tableAdmin->translate('Affected files: ') . $ZipArchive->numFiles . '.' 
                    : $this->tableAdmin->translate('Error occured unpacking the archive.');
                Tools::addMessage($result['success'], $result['data']);
                $ZipArchive->close();
            } else {
                $result['data'] = $this->tableAdmin->translate('Error occured unpacking the archive.');
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
    }

    /**
     * Process the "files upload" action
     *
     * @param array &$post $_POST
     * @return void
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
                    $this->MyCMS->logger->info("Admin {$_SESSION['user']} přihlášen.");
                    Tools::addMessage('success', $this->tableAdmin->translate('You are logged in.'));
                    $this->redir();
                }
                $this->MyCMS->logger->warning('Admin nepřihlášen - špatné heslo.');
            } else {
                $this->MyCMS->logger->warning('Admin nepřihlášen - špatné jméno.');
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
            $this->redir();
        }
    }

    /**
     * Return files in /assets (sub)folder
     *
     * @param array &$post $_POST
     * @return void
     */
    public function processSubfolder(&$post)
    {
        if (isset($post['subfolder'], $post['media-files'])) {
            $result = array(
                'subfolder' => DIR_ASSETS . $post['subfolder'],
                'data' => array(),
                'success' => true
            );
            if (is_dir(DIR_ASSETS . $post['subfolder'])) {
                $_SESSION['assetsSubfolder'] = $post['subfolder'];
                foreach (glob(DIR_ASSETS . $post['subfolder'] . '/' . (isset($post['wildcard']) ? $post['wildcard'] : '*.*'), isset($post['wildcard']) ? GLOB_BRACE : 0) as $file) {
                    if (is_file($file)) {
                        $pathinfo = pathinfo($file);
                        $entry = array(
                            'name' => $pathinfo['filename'],
                            'extension' => isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '',
                            'size' => filesize($file),
                            'modified' => date("Y-m-d H:i:s", filemtime($file)),
                            'info' => ''
                        );
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
     * @return void
     */
    public function processUserActivation(&$post)
    {
        if (isset($post['activate-user'], $post['active']) && is_numeric($post['activate-user'])) {
            $result = array(
                'data' => array('id' => $post['activate-user']),
                'success' => $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin SET active="' . ($post['active'] ? 1 : 0) . '" WHERE id=' . +$post['activate-user']) && $this->MyCMS->dbms->affected_rows
            );
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
     */
    protected function exitJson($result)
    {
        header('Content-type: application/json');
        $this->endAdmin();
        exit(json_encode($result));
    }

    /**
     * Tracy wrapper of Tools::redir
     *
     * @param string $url OPTIONAL
     */
    protected function redir($url = '')
    {
        $this->endAdmin();
        Tools::redir($url);
    }

}