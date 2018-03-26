<?php   

namespace GodsDev\MYCMSPROJECTNAMESPACE;

/**
 * process for TableAdmin agendas
 * dependencies: 
 * * TableAdmin.php
 * * user-defined.php
 */
use GodsDev\Tools\Tools;
use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyCommon;
use Tracy\Debugger;

define('PROCESS_LIMIT', 100);

class AdminProcess extends MyCommon
{
    /**
     * accepted attributes:
     */

    /** @var array */
    protected $agendas;
    
    /** @var \GodsDev\MyCMS\TableAdmin */
    protected $tableAdmin;

    /**
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options overrides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
    }

    /**
     * $_SESSION is manipulated by this function
     * 
     * @return type
     */
    public function adminProcess()
    {
        //@todo inject $_POST as $options['post']
        if (!isset($_POST) || !is_array($_POST)) {
            return;
        }

        if (!isset($_POST['record-save']) && !isset($_POST['record-delete'])) {
            //@todo - rewrite according to current CSRF handling
            if (!isset($_POST['token'], $_SESSION['token']) || $_POST['token'] != $_SESSION['token']) {
                $this->MyCMS->logger->warning("admin CSRF token mismatch {$_POST['token']}!={$_SESSION['token']}"); //@todo nepotvrdit uložení nějak jinak, než že prostě potichu nenapíše Záznam uložen?
                usleep(mt_rand(1000, 2000));
                return;
            }
        }

        if (isset($_POST['user'], $_POST['password'], $_POST['login'])) {
            $row = $this->MyCMS->fetchSingle('SELECT * FROM ' . TAB_PREFIX . 'admin WHERE name="' . $this->MyCMS->escapeSQL($_POST['user']) . '"');
            if ($row) {
                if ($row['active'] == '1' && $row['password_hashed'] == sha1($_POST['password'] . $row['salt'])) {
                    $_SESSION['user'] = $_POST['user'];
                    $_SESSION['rights'] = $row['rights'];
                    $this->MyCMS->logger->info("Admin {$_SESSION['user']} přihlášen.");
                    Tools::addMessage('success', $this->tableAdmin->translate('You are logged in.'));
                    Tools::redir();
                }
                $this->MyCMS->logger->warning('Admin nepřihlášen - špatné jméno nebo heslo.');
            } else {
                $this->MyCMS->logger->error('Admin nepřihlášen - žádné záznamy.');
            }
            Tools::addMessage('error', $this->tableAdmin->translate('Error occured logging You in.'));
            Tools::redir();
        }
        // further commands require user to be logged in
        if (!isset($_SESSION['user']) || !isset($_SESSION['rights']) || !$_SESSION['rights']) {
            return;
        }
        if (isset($_POST['logout'])) {
            unset($_SESSION['user'], $_SESSION['rights']);
            Tools::addMessage('info', $this->tableAdmin->translate('You are logged out.'));
            Tools::redir();
        }
        if (isset($_POST['change-password'], $_POST['old-password'], $_POST['new-password'], $_POST['retype-password'])) {
            if ($_POST['new-password'] != $_POST['retype-password']) {
                Tools::addMessage('error', $this->tableAdmin->translate('The new password was not retyped correctly.'));
            } else {
                if ($row = $this->MyCMS->fetchSingle('SELECT * FROM ' . TAB_PREFIX . 'admin WHERE name="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"')) {
                    if ($row['active'] == '1' && $row['password_hashed'] == sha1($_POST['old-password'] . $row['salt'])) {
                        Tools::resolve($this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'admin 
                    SET password_hashed="' . $this->MyCMS->escapeSQL(sha1($_POST['new-password'] . $row['salt'])) . '" 
                    WHERE name="' . $this->MyCMS->escapeSQL($_SESSION['user']) . '"'), $this->tableAdmin->translate('Password was changed.'), $this->tableAdmin->translate('Error occured changing password.')
                        );
                        Tools::redir();
                    }
                }
                Tools::addMessage('error', $this->tableAdmin->translate('Error occured changing password.'));
                Tools::redir();
            }
        }
        if (isset($_POST['upload-media'], $_POST['subfolder'])) {
            if (!file_exists(DIR_ASSETS . $_POST['subfolder'])) {
                Tools::addMessage('error', $this->tableAdmin->translate("The subfolder doesn't exist."));
                Tools::redir();
            }
            $_SESSION['subfolder'] = $_POST['subfolder'];
            $i = 0;
            foreach ($_FILES['files']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $name = basename($_FILES['files']['name'][$key]);
                    $result = move_uploaded_file($_FILES['files']['tmp_name'][$key], DIR_ASSETS . $_POST['subfolder'] . ($_POST['subfolder'] ? '/' : '') . $name);
                    Tools::addMessage($result ? 'success' : 'error', '<tt>' . Tools::h($name) . '</tt> '
                            . ($result ? $this->tableAdmin->translate('File was uploaded to server.') : $this->tableAdmin->translate('Error occured uploading the file to server.')));
                    $i++;
                }
            }
            if (!$i) {
                Tools::addMessage('info', $this->tableAdmin->translate('No files selected.'));
            } else {
                Tools::addMessage('info', $this->tableAdmin->translate('Total of processed files: ') . $i . '.');
            }
            Tools::redir();
        }
        if (isset($_POST['subfolder'], $_POST['media-files'])) {
            $result = array(
                'subfolder' => DIR_ASSETS . $_POST['subfolder'],
                'data' => array(),
                'success' => true
            );
            if (is_dir(DIR_ASSETS . $_POST['subfolder'])) {
                $_SESSION['assetsSubfolder'] = $_POST['subfolder'];
                foreach (glob(DIR_ASSETS . $_POST['subfolder'] . '/' . (isset($_POST['wildcard']) ? $_POST['wildcard'] : '*.*'), isset($_POST['wildcard']) ? GLOB_BRACE : 0) as $file) {
                    if (is_file($file)) {
                        $pathinfo = pathinfo($file);
                        $result['data'] [] = array(
                            'name' => $pathinfo['filename'],
                            'extension' => isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '',
                            'size' => filesize($file),
                            'modified' => date("Y-m-d H:i:s", filemtime($file))
                        );
                    }
                }
            } else {
                $result['subfolder'] = DIR_ASSETS . ($_SESSION['mediaSubfolder'] = '');
                $result['success'] = false;
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
        if (isset($_POST['subfolder'], $_POST['delete-files'])) {
            $result = array(
                'deleted-files' => 0,
                'success' => false
            );
            if (is_dir(DIR_ASSETS . $_POST['subfolder']) && is_array($_POST['delete-files'])) {
                foreach ($_POST['delete-files'] as $value) {
                    if (unlink(DIR_ASSETS . $_POST['subfolder'] . "/$value")) {
                        $result['deleted-files'] ++;
                    }
                }
                Tools::addMessage('info', $this->tableAdmin->translate('Total of deleted files: ') . $result['deleted-files'] . '.');
                $result['success'] = $result['deleted-files'] > 0;
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }
        if (isset($_POST['agenda'], $this->agendas[$_POST['agenda']])) {
            $result = array(
                'data' => $this->getAgenda($_POST['agenda']),
                'success' => true,
                'agenda' => $_POST['agenda'],
                'subagenda' => Tools::setifnull($this->agendas[$_POST['agenda']]['join']['table'])
            );
            header('Content-type: application/json');
            exit(json_encode($result));
        }
        if (isset($_POST['create-user'], $_POST['user'], $_POST['password'], $_POST['retype-password']) && $_POST['user'] && $_POST['password'] && $_POST['retype-password']) {
            $salt = mt_rand(1e8, 1e9);
            Tools::resolve($this->MyCMS->dbms->query('INSERT INTO ' . TAB_PREFIX . 'admin SET name="' . $this->MyCMS->escapeSQL($_POST['user']) . '", password_hashed="' . $this->MyCMS->escapeSQL(sha1($_POST['user'] . $salt)) . '", salt=' . $salt . ', rights=2'), $this->tableAdmin->translate('User added.'), $this->tableAdmin->translate($this->MyCMS->dbms->errno == 1062 ? 'User already exists.' : 'Error occured adding the user.'));
            Tools::redir();
        }
        if (isset($_POST['delete-user'])) {
            Tools::resolve($this->MyCMS->dbms->query('DELETE FROM ' . TAB_PREFIX . 'admin WHERE name="' . $this->MyCMS->escapeSQL($_POST['delete-user']) . '" LIMIT 1'), $this->tableAdmin->translate('User deleted.'), $this->tableAdmin->translate('Error occured deleting the user.'));
            Tools::redir();
        }
        if (isset($_POST['category-up']) && is_numeric($_POST['category-up'])) {
            $_POST['category-switch'] = $_POST['category-up'];
            $_POST['delta'] = -1;
        } elseif (isset($_POST['category-down']) && is_numeric($_POST['category-down'])) {
            $_POST['category-switch'] = $_POST['category-down'];
            $_POST['delta'] = 1;
        } else {
            unset($_POST['category-switch']);
        }
        if (isset($_POST['category-switch'], $_POST['delta']) && abs($_POST['delta']) == 1 && ($path = $this->MyCMS->fetchSingle('SELECT path FROM ' . TAB_PREFIX . 'category WHERE id=' . $_POST['category-switch']))) {
            $strlen = strlen($path);
            $neighbour = substr($path, 0, -PATH_MODULE) . str_pad(substr($path, -PATH_MODULE) + $_POST['delta'], PATH_MODULE, '0', STR_PAD_LEFT);
            $edits = $this->MyCMS->fetchAndReindex('SELECT id,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . strlen($path) . ') IN ("' . $this->MyCMS->escapeSQL($neighbour) . '")');
            if ($edits) {
                $edits += $this->MyCMS->fetchAndReindex('SELECT id,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . strlen($path) . ') IN ("' . $this->MyCMS->escapeSQL($path) . '")');
                $this->MyCMS->dbms->query('LOCK TABLES ' . TAB_PREFIX . 'category WRITE');
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'category SET path=NULL WHERE id IN (' . Tools::arrayListed(array_keys($edits), 8) . ')');
                $i = 0;
                foreach ($edits as $key => $value) {
                    $tmp = substr($value, 0, $strlen - PATH_MODULE) . str_pad(substr($value, $strlen - PATH_MODULE, PATH_MODULE) + (Tools::begins($value, $path) ? 1 : -1) * $_POST['delta'], PATH_MODULE, '0', STR_PAD_LEFT) . substr($value, $strlen);
                    $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'category SET path="' . $this->MyCMS->escapeSQL($tmp) . '" WHERE id="' . $this->MyCMS->escapeSQL($key) . '"');
                    $i++;
                }
                $this->MyCMS->dbms->query('UNLOCK TABLES');
                Tools::addMessage('info', $this->tableAdmin->translate('Order change processed.') . ' ' . $this->tableAdmin->translate('Affected rows: ') . "$i.");
            } else {
                Tools::addMessage('info', $this->tableAdmin->translate('Nothing to change.'));
            }
            Tools::redir();
        }
        // commands requiring the TableAdmin object
        if (isset($_POST['record-save'])) {
            Debugger::barDump($_POST, 'POST with record-save');
            if (function_exists('TableAdminCustomSave')) {
                if (!TableAdminCustomSave()) {
                    $this->tableAdmin->recordSave();
                    if ($id = $this->MyCMS->dbms->insert_id) {
                        $where = 'id';
                        foreach ($this->tableAdmin->fields as $key => $value) {
                            if ($value['key'] == 'PRI') {
                                $where = $key;
                                break;
                            }
                        }
                        unset($_SESSION['csrf-' . $_POST['database-table']]);
                        Tools::redir('?' . Tools::urlChange(array('where' => array($where => $id))));
                    }
                }
            } else {
                unset($_SESSION['csrf-' . $_POST['database-table']]);
                $this->tableAdmin->recordSave();
            }
            Tools::redir();
            return;
        }
        if (isset($_POST['record-delete'])) {
            if (function_exists('TableAdminCustomBeforeDelete')) {
                if (!TableAdminCustomBeforeDelete()) {
                    if ($this->tableAdmin->recordDelete() && function_exists('TableAdminCustomAfterDelete')) {
                        TableAdminCustomAfterDelete();
                    }
                }
            } else {
                if ($this->tableAdmin->recordDelete() && function_exists('TableAdminCustomAfterDelete')) {
                    TableAdminCustomAfterDelete();
                }
            }
            if (!isset($_SESSION['error']) || !$_SESSION['error']) {
                unset($_SESSION['csrf-' . $_POST['database-table']]);
                Tools::redir('?' . Tools::urlChange(array('where' => null)));
            }
        }
    }

    //public (instead of protected) for PHPUnit test
    public function getAgenda($agenda)
    {
        $result = $correctOrder = array();
        if (!isset($this->agendas[$agenda])) {
            return $result;
        }
        $options = $this->agendas[$agenda];
        Tools::setifempty($options['table'], $agenda);
        Tools::setifempty($options['sort']);
        Tools::setifempty($options['path']);
        $sql = isset($options['path']) ? 'CONCAT(REPEAT("… ",LENGTH(' . Tools::escapeDbIdentifier($options['path']) . ') / ' . PATH_MODULE . ' - 1),' . $options['table'] . '_' . DEFAULT_LANGUAGE . ')' : (isset($options['column']) ? $options['column'] : $options['table'] . '_' . DEFAULT_LANGUAGE);
        $sql = 'SELECT id,' . (isset($options['path']) ? $sql : Tools::escapeDbIdentifier($sql)) . ' AS name'
                . Tools::wrap($options['sort'], ',', ' AS sort') . Tools::wrap($options['path'], ',', ' AS path')
                . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $options['table'])
                . Tools::wrap(isset($options['where']) ? $options['where'] : '', ' WHERE ')
                . Tools::wrap($options['sort'] . ($options['sort'] && $options['path'] ? ',' : '') . $options['path'], ' ORDER BY ')
                . ' LIMIT ' . PROCESS_LIMIT;
        $query = $this->MyCMS->dbms->query($sql);
        if ($query) {
            for ($i = 1; $row = $query->fetch_assoc(); $i++) {
                $row['name'] = Tools::shortify(strip_tags($row['name']), 100);
                $result [] = $row;
                if ($agenda == 'product' && isset($row['sort']) && $row['sort'] != $i) {
                    $correctOrder[$row['id']] = $i;
                }
            }
        }
        foreach ($correctOrder as $key => $value) {
            $this->MyCMS->dbms->query('UPDATE ' . Tools::escapeDbIdentifier(TAB_PREFIX . $options['table']) . ' SET sort=' . (int) $value . ' WHERE id=' . (int) $key . ' LIMIT 1');
        }
        return $result;
    }

}
