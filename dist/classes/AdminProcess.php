<?php

/**
 * process for TableAdmin agendas
 * dependencies:
 * * TableAdmin.php
 */

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyAdminProcess;
use GodsDev\Tools\Tools;
use Tracy\Debugger;

define('PROCESS_LIMIT', 100);

class AdminProcess extends MyAdminProcess
{
    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var array */
    protected $agendas;

    /**
     * Process admin commands. Most commands require admin logged in and/or CSRF check.
     * Commands with all required variables cause page redirection.
     * $_SESSION is manipulated by this function - mainly [messages] get added
     *
     * @param array $post $_POST variable by reference
     * @todo refactor, so that $this->endAdmin(); is called automatically
     *
     * @return void
     */
    public function adminProcess(&$post)
    {
        // commands are saved in $post[] array - if it's empty, don't continue
        if (!is_array($post) || !$post) {
            return;
        }
        // commands for unsigned users:
        // log admin in
        $this->processLogin($post);
        // further commands require user to be logged in, but not the token
        if (!isset($_SESSION['user']) || !isset($_SESSION['rights']) || !$_SESSION['rights']) {
            return;
        }
        // return given agenda
        if (isset($post['agenda'], $this->agendas[$post['agenda']])) {
            $result = [
                'data' => $this->getAgenda($post['agenda']),
                'success' => true,
                'agenda' => $post['agenda'],
                'subagenda' => Tools::setifnull($this->agendas[$post['agenda']]['join']['table'])
            ];
            $this->exitJson($result);
        }
        // return files in /assets (sub)folder
        $this->processSubfolder($post);
        // return a webalized string
        if (isset($post['webalize']) && is_string($post['webalize'])) {
            // @todo $post['table'] and $post['id'] can also be sent - check uniqueness then
            $result = [
                'data' => Tools::webalize($post['webalize']),
                'success' => true
            ];
            $this->exitJson($result);
        }
        // further commands require token
        if (!isset($post['token']) || !$this->MyCMS->csrfCheck($post['token'])) {
            Debugger::barDump($post, 'POST - admin CSRF token mismatch');
            $this->MyCMS->logger->warning("admin CSRF token mismatch ");
            //@todo nepotvrdit uložení nějak jinak, než že prostě potichu nenapíše Záznam uložen?
            usleep(mt_rand(1000, 2000));
            return;
        }
        // change current admin's password
        $this->processUserChangePassword($post);
        // upload a file to assets/
        $this->processFileUpload($post);
        // delete file(s) from assets/
        $this->processFileDelete($post);
        // create a admin
        $this->processUserCreate($post);
        // delete admin
        $this->processUserDelete($post);
        // activate/deactivate admin
        $this->processUserActivation($post);
        // file rename
        $this->processFileRename($post);
        // file pack
        $this->processFilePack($post);
        // file unpack
        $this->processFileUnpack($post);
        // users' activity
        $this->processActivity($post);
        // move category up/down
        if (
            isset($post['category-switch'], $post['id']) && ($path = $this->MyCMS->fetchSingle(
                'SELECT path FROM ' . TAB_PREFIX . 'category WHERE id=' . $post['id']
            ))
        ) {
            $strlen = strlen($path);
            $neighbour = substr($path, 0, -PATH_MODULE)
                . str_pad(substr($path, -PATH_MODULE) + $post['category-switch'], PATH_MODULE, '0', STR_PAD_LEFT);
            $edits = $this->MyCMS->fetchAndReindex('SELECT id,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, '
                . strlen($path) . ') IN ("' . $this->MyCMS->escapeSQL($neighbour) . '")');
            if ($edits) {
                $edits += $this->MyCMS->fetchAndReindex('SELECT id,path FROM ' . TAB_PREFIX
                    . 'category WHERE LEFT(path, ' . strlen($path) . ') IN ("' . $this->MyCMS->escapeSQL($path) . '")');
                $this->MyCMS->dbms->query('LOCK TABLES ' . TAB_PREFIX . 'category WRITE');
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'category SET path=NULL WHERE id IN ('
                    . Tools::arrayListed(array_keys($edits), 8) . ')');
                $i = 0;
                foreach ($edits as $key => $value) {
                    $tmp = substr($value, 0, $strlen - PATH_MODULE)
                        . str_pad((string) (intval(substr($value, $strlen - PATH_MODULE, PATH_MODULE)) + (Tools::begins(
                            $value,
                            $path
                        ) ? 1 : -1) * $post['category-switch']), PATH_MODULE, '0', STR_PAD_LEFT)
                        . substr($value, $strlen);
                    $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'category SET path="'
                        . $this->MyCMS->escapeSQL($tmp) . '" WHERE id="' . $this->MyCMS->escapeSQL($key) . '"');
                    $i++;
                }
                $this->MyCMS->dbms->query('UNLOCK TABLES');
                Tools::addMessage('info', $this->tableAdmin->translate('Order change processed.') . ' '
                    . $this->tableAdmin->translate('Affected rows: ') . "$i.");
            } else {
                Tools::addMessage('info', $this->tableAdmin->translate('Nothing to change.'));
            }
            die(json_encode(['success' => 1, 'errors' => '']));
        }
        // move product up/down
        if (
            isset($post['product-switch'], $post['id']) &&
            ($product = $this->MyCMS->dbms->query(
                'SELECT category_id,sort FROM ' . TAB_PREFIX . 'product WHERE id=' . (int) $post['id']
            )->fetch_assoc())
        ) {
            $id = $this->MyCMS->fetchSingle('SELECT id FROM ' . TAB_PREFIX . 'product WHERE category_id='
                . (int) $product['category_id'] . ' AND sort' . ($post['product-switch'] == 1 ? '>' : '<')
                . $product['sort'] . ' ORDER BY sort' . ($post['product-switch'] == 1 ? '' : ' DESC') . ' LIMIT 1');
            if ($id) {
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'product SET sort='
                    . $product['sort'] . ' WHERE id=' . $id);
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'product SET sort='
                    . ($product['sort'] + $post['product-switch']) . ' WHERE id=' . (int) $post['id']);
                Tools::addMessage('info', $this->tableAdmin->translate('Order change processed.'));
                die(json_encode(['success' => 1, 'errors' => '']));
            } else {
                //Tools::addMessage('info', $this->tableAdmin->translate('Nothing to change.'));
            }
            $this->redir();
            return;
        }
        // loggin admin out
        $this->processLogout($post);
        // export table rows
        $this->processExport($post, $_GET);
        // clone table rows
        $this->processClone($post);
        // save a table record
        if (isset($post['record-save'])) {
            Debugger::barDump($post, 'POST with record-save');
            if (!$this->tableAdmin->customSave()) {
                $this->tableAdmin->recordSave();
                if ($id = $this->MyCMS->dbms->insert_id) {
                    Debugger::barDump($id, 'record-save insert_id');
                    $where = 'id';
                    foreach ($this->tableAdmin->fields as $key => $value) {
                        if ($value['key'] == 'PRI') {
                            $where = $key;
                            break;
                        }
                    }
                    $this->redir('?' . Tools::urlChange(array('where' => array($where => $id))));
                } else {
                    Debugger::barDump($post['fields']['id'], 'record-save update only');
                    // project-specific: products 13 and 67 SHOULD have identical description
                    if (in_array($post['fields']['id'], ['13', '67'])) {
                        if ($post['fields']['id'] === '13') {
                            $post['fields'] = ['id' => '67', 'description_en' => $post['fields']['description_en'],
                                'description_cs' => $post['fields']['description_cs']];
                            $post['original'] = ['id' => '67', 'description_en' => $post['original']['description_en'],
                                'description_cs' => $post['original']['description_cs']];
                        } else {
                            $post['fields'] = ['id' => '13', 'description_en' => $post['fields']['description_en'],
                                'description_cs' => $post['fields']['description_cs']];
                            $post['original'] = ['id' => '13', 'description_en' => $post['original']['description_en'],
                                'description_cs' => $post['original']['description_cs']];
                        }
                        $this->tableAdmin->recordSave();
                    }
                }
            }
            if (isset($post['after'], $post['referer']) && $post['after']) {
                $post['referer'] = Tools::xorDecipher(base64_decode($post['referer']), $post['token']);
                $this->redir($post['referer']);
            }
            $this->redir();
            return;
        }
        // delete a table record
        if (isset($post['record-delete'])) {
            // TODO nemá náhodou být ->customAfterDelete namísto customDelete? Ask crs2
            if (!$this->tableAdmin->customDelete()) {
                $this->tableAdmin->recordDelete();
            }
            if (!isset($_SESSION['error']) || !$_SESSION['error']) {
                $this->redir('?' . Tools::urlChange(array('where' => null)));
            }
        }
        //unset($_SESSION['token'][array_search($post['token'], $_SESSION['token'])]);
    }

    /**
     * It is public for PHPUnit test
     *
     * @param string $agenda
     * @return array
     */
    public function getAgenda($agenda)
    {
        $result = $correctOrder = [];
        if (!isset($this->agendas[$agenda])) {
            return $result;
        }
        /** @var array $options array of agenda set in admin.php in $AGENDAS */
        $options = $this->agendas[$agenda];
        Tools::setifempty($options['table'], $agenda);
        Tools::setifempty($options['sort']);
        Tools::setifempty($options['path']);
        $selectExpression = isset($options['path']) ?
            'CONCAT(REPEAT("… ",LENGTH(' . $this->MyCMS->dbms->escapeDbIdentifier($options['path']) . ') / '
            . PATH_MODULE . ' - 1),' . $options['table'] . '_' . DEFAULT_LANGUAGE . ')' :
            (isset($options['column']) ? (
                is_array($options['column']) ? ('CONCAT(' .
                implode(
                    ",'|',",
                    array_map([$this->MyCMS->dbms, 'escapeDbIdentifier'], $options['column'])
                ) . ')') :
            $this->MyCMS->dbms->escapeDbIdentifier($options['column'])
            ) : $this->MyCMS->dbms->escapeDbIdentifier($options['table'] . '_' . DEFAULT_LANGUAGE));
        $sql = 'SELECT id,' . $selectExpression . ' AS name'
            . Tools::wrap($options['sort'], ',', ' AS sort') . Tools::wrap($options['path'], ',', ' AS path')
            . ' FROM ' . $this->MyCMS->dbms->escapeDbIdentifier(TAB_PREFIX . $options['table'])
            . Tools::wrap(isset($options['where']) ? $options['where'] : '', ' WHERE ')
            . Tools::wrap(
                $options['sort'] . ($options['sort'] && $options['path'] ? ',' : '') . $options['path'],
                ' ORDER BY '
            )
            . ' LIMIT ' . $this::PROCESS_LIMIT;
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
            $this->MyCMS->dbms->query('UPDATE `' . $this->MyCMS->dbms->escapeDbIdentifier(
                TAB_PREFIX . $options['table']
            ) . '` SET sort=' . (int) $value . ' WHERE id=' . (int) $key . ' LIMIT 1');
        }
        return $result;
    }
}
