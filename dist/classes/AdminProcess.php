<?php

/**
 * process for TableAdmin agendas
 * dependencies:
 * * TableAdmin.php
 */

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\MyAdminProcess;
use WorkOfStan\mycmsprojectnamespace\TableAdmin;

define('PROCESS_LIMIT', 100); // used in self::getAgenda

/**
 * AJAX and form handling for Admin UI
 * (Last MyCMS/dist revision: 2021-05-28, v0.4.2)
 */
class AdminProcess extends MyAdminProcess
{
    use \Nette\SmartObject;

    /** @var TableAdmin */
    protected $tableAdmin;

    /**
     * accepted attributes:
     */

    /** @var array<array<mixed>> */
    protected $agendas;

    /**
     * Process admin commands. Most commands require admin logged in and/or CSRF check.
     * Commands with all required variables cause page redirection.
     * $_SESSION is manipulated by this function - mainly [messages] get added
     *
     * @param array<string|array<mixed>> $post $_POST by reference
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
            $this->exitJson($result); // terminates
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
            $this->exitJson($result); // terminates
        }
        // further commands require token
        if (!isset($post['token']) || !$this->MyCMS->csrfCheck($post['token'])) {
            Debugger::barDump($post, 'POST - admin CSRF token mismatch');
            $this->MyCMS->logger->warning("admin CSRF token mismatch ");
            //@todo nepotvrdit uložení nějak jinak, než že prostě potichu nenapíše Záznam uložen?
            usleep(mt_rand(1000, 2000));
            return;
        }

        /* TODO explore F project code
        // rename a file in DIR_ASSETS/*
        // Note: file name is checked against a basic RegEx; file extension must not be changed; overwriting existing files not allowed
        if (isset($post['file_rename'], $post['old_name'], $post['subfolder'], $post['new_folder'])) {
            $result = array('data' => $post['old_name'], 'success' => false);
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
                $this->MyCMS->logger->warning("Neuspesne prejmenovani souboru $path{$post['old_name']} --> $newpath{$post['file_name']}.");
            }
            header('Content-type: application/json');
            exit(json_encode($result));
        }

         *
         */
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
            $edits = $this->MyCMS->fetchAndReindex(
                'SELECT id,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . strlen($path) . ') IN ("'
                . $this->MyCMS->escapeSQL($neighbour) . '")'
            );
            if (is_array($edits) && $edits) {
                $editsPath = $this->MyCMS->fetchAndReindex(
                    'SELECT id,path FROM ' . TAB_PREFIX . 'category WHERE LEFT(path, ' . strlen($path) . ') IN ("'
                    . $this->MyCMS->escapeSQL($path) . '")'
                );
                Assert::isArray($editsPath);
                $edits += (array) $editsPath;
                $this->MyCMS->dbms->query('LOCK TABLES ' . TAB_PREFIX . 'category WRITE');
                $this->MyCMS->dbms->query('UPDATE ' . TAB_PREFIX . 'category SET path=NULL WHERE id IN ('
                    . Tools::arrayListed(array_keys($edits), 8) . ')');
                $i = 0;
                foreach ($edits as $key => $value) {
                    Assert::string($value);
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
            // Note: F code uses also $post['product-delta']
            isset($post['product-switch'], $post['id']) &&
            ($product = $this->MyCMS->dbms->queryStrictObject(
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
            $this->redir(); // terminates
        }
        // loggin admin out
        $this->processLogout($post);

        // generate translations. Note: this rewrites the translation files language-xx.inc.php
        if (isset($post['translations'])) {
            foreach (array_keys($this->MyCMS->TRANSLATIONS) as $code) {
                $fp = fopen("language-$code.inc.php", 'w+');
                Assert::resource($fp);
                fwrite($fp, "<?php\n\n// MyCMS->getSessionLanguage expects \$translation=\n\$translation = [\n");
                if ($post['new'][0]) {
                    $post['tr'][$code][$post['new'][0]] = $post['new'][$code];
                }
                foreach ($post['tr'][$code] as $key => $value) {
                    if ($key == $post['old_name']) {
                        $key = $post['new_name'];
                        $value = Tools::set($post['delete']) ? false : $value;
                    }
                    if ($value) {
                        fwrite($fp, "    '" . strtr($key, array('&apos;' => "\\'", "'" => "\\'", '&amp;' => '&'))
                            . "' => '" . strtr($value, array('&appos;' => "\\'", "'" => "\\'", '&amp;' => '&')) . "',\n");
                    }
                }
                fwrite($fp, "];\n");
                fclose($fp);
            }
            Tools::addMessage('info', $this->tableAdmin->translate('Processed.'));
            $this->redir();
        }

        // export table rows
        $this->processExport($post, $_GET);

        /**
         * TODO to CSV (as in F project)
         *
        // table: export selected rows TO CSV
        if (isset($post['table-export'], $post['database-table'])) {
            $output = '';
            Tools::setifnull($post['delimiter'], ';');
            if (Tools::set($post['total-rows'])) {
                if ($query = $this->MyCMS->dbms->query('SELECT * FROM ' . $this->MyCMS->dbms->escapeDbIdentifier($post['database-table']))) {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment;filename="export.csv"');
                    header('Content-Transfer-Encoding: binary');
                    $row = [];
                    foreach ($query->fetch_fields() as $value) {
                        $row [] = $value->name;
                    }
                    $output .= Tools::str_putcsv($row, $post['delimiter']);
                    while ($row = $query->fetch_row()) {
                        $output .= Tools::str_putcsv($row, $post['delimiter']);
                    }
                    header('Cache-Control: max-age=0');
                    header('Connection: Close', true, 200);
                    die($output);
                } else {
                    Tools::addMessage('error', $this->tableAdmin->translate('No records found.'));
                }
            } elseif (isset($post['check']) && is_array($post['check'])) {
                //
            } else {
                Tools::addMessage('error', $this->tableAdmin->translate('No records found.'));
            }
        }
         *
         */

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
                    $this->redir('?' . Tools::urlChange(['where' => [$where => $id]]));
                } else {
                    Debugger::barDump($post['fields']['id'], 'record-save update only');
//                  // project-specific: products 13 and 67 SHOULD have identical description
//                  if (in_array($post['fields']['id'], ['13', '67'])) {
//                      if ($post['fields']['id'] === '13') {
//                          $post['fields'] = ['id' => '67', 'description_en' => $post['fields']['description_en'],
//                              'description_cs' => $post['fields']['description_cs']];
//                          $post['original'] = ['id' => '67', 'description_en' => $post['original']['description_en'],
//                              'description_cs' => $post['original']['description_cs']];
//                      } else {
//                          $post['fields'] = ['id' => '13', 'description_en' => $post['fields']['description_en'],
//                              'description_cs' => $post['fields']['description_cs']];
//                          $post['original'] = ['id' => '13', 'description_en' => $post['original']['description_en'],
//                              'description_cs' => $post['original']['description_cs']];
//                      }
//                      $this->tableAdmin->recordSave();
//                 }
                }
                /* F code to be explored
                if (Tools::set($post['table'], '') == TAB_PREFIX . 'content'
                    && Tools::among(Tools::set($post['fields']['type'], ''), 'event', 'news')
                    && isset($_FILES['picture']) && is_array($_FILES['picture'])
                    && is_uploaded_file($_FILES['picture']['tmp_name'])
                    && $_FILES['picture']['type'] == 'image/jpeg'
                    && $_FILES['picture']['error'] === 0) {
                    Tools::resolve(
                        move_uploaded_file($_FILES['picture']['tmp_name'], $file = DIR_ASSETS . ($post['fields']['type'] == 'event' ? 'events' : 'news') . "/$id.jpg"),
                        $this->tableAdmin->translate('File processed.') . ' (<a href="' . $file . '" target="_blank">' . $file . '</a>)',
                        $this->tableAdmin->translate('Error processing the file.'));
                }
                 *
                 */
            }
            if (isset($post['after'], $post['referer']) && $post['after']) {
                $post['referer'] = Tools::xorDecipher(base64_decode($post['referer']), $post['token']);
                $this->redir($post['referer']); // terminates
            }
            $this->redir(); // redir() method terminates
        }
        // delete a table record
        if (isset($post['record-delete'])) {
            if (!$this->tableAdmin->customDelete()) {
                $this->tableAdmin->recordDelete();
            }
            if (!isset($_SESSION['error']) || !$_SESSION['error']) {
                $this->redir('?' . Tools::urlChange(['where' => null]));
            }
        }
        // urls - Friendly URL - batch save
        if (isset($post['urls-save'])) {
            $stat = [0 /*successful*/, 0 /*unsuccessful*/];
            foreach ($post as $key => $value) {
                $url = explode('-', $key);
                if (isset($url[0], $url[1], $url[2], $url[3]) && $url[0] == 'url') {
                    $sql = 'UPDATE ' . $this->tableAdmin->escapeDbIdentifier(TAB_PREFIX . $url[1]) . '
                        SET ' . $this->tableAdmin->escapeDbIdentifier('url_' . $url[3]) . '="' . $this->tableAdmin->escapeSQL($value) . '"
                        WHERE id=' . (int)$url[2];
                    $stat[$this->MyCMS->dbms->query($sql) ? 0 : 1]++;
                }
            }
            Tools::addMessage('info', $this->tableAdmin->translate('Processed.') . ' '
                . $this->tableAdmin->translate('successful') . ': ' . $stat[0] . ', '
                . $this->tableAdmin->translate('unsuccessful') . ': ' . $stat[1] . '.');
        }
        /* F code to be explored
        // save selected records
        if (isset($post['save-selected'], $post['fields'])) do {
            $where = $update = array();
            if (!Tools::set($post['total-rows']) && !Tools::setarray($post['check'])) {
                Tools::addMessage('warning', $this->tableAdmin->translate('Nothing to save.'));
            } elseif (Tools::set($post['total-rows'])) {
                $post['check'] = array();
            } else {
                foreach ($post['check'] as &$value) {
                    parse_str($value, $value);
                    $whereItem = array();
                    foreach ((Tools::setarray($value['where']) ? $value['where'] : array()) as $whereKey => $whereValue) {
                        $whereItem []= $this->tableAdmin->escapeDbIdentifier($whereKey) . '="' . $this->tableAdmin->escapeSQL($whereValue) . '"';
                    }
                    $where []= '(' . implode(' AND ', $whereItem) . ')';
                }
            }
            foreach ($post['fields'] as $key => $value) {
                if (!isset($post['op'][$key]) || !Tools::setarray($this->tableAdmin->fields[$key]) || $post['op'][$key] == 'original') {
                    continue;
                }
                $updateItem = $this->tableAdmin->escapeDbIdentifier($key);
                switch ($post['op'][$key]) {
                    case 'value':
                        if (Tools::set($this->tableAdmin->fields[$key]['type'], '') == 'set') {
                            $tmp = 0;
                            if (is_array($value)) {
                                foreach ($value as $v) {
                                    $tmp |= $v;
                                }
                            }
                        }
                        $updateItem .= ' = ' . (Tools::among($this->tableAdmin->fields[$key]['basictype'], 'integer', 'choice') ? (int)$value : '"' . $this->tableAdmin->escapeSQL($value) . '"');
                        break;
                    case '-': case '+':
                        $updateItem .= ' = ' . $updateItem . ' ' . $post['op'][$key] . ' ' . (float)$value;
                        break;
                    case '+interval': case '-interval':
                        if ($this->tableAdmin->checkIntervalFormat($value)) {
                            $updateItem .= ' = ' . (substr($post['op'][$key], 0, 1) == '+' ? 'DATE_ADD(' : 'DATE_SUB(') . $updateItem . ', INTERVAL ' . $value . ')';
                        } else {
                            continue(2);
                        }
                        break;
                    case 'addtime': case 'subtime':
                        $updateItem .= ' = ' . strtoupper($post['op'][$key]) . '(' . $updateItem . ', "' . $this->tableAdmin->escapeSQL($value) . '")';
                        break;
                    case 'null':
                        $updateItem .= ' = NULL';
                        break;
                    case 'now': case 'uuid':
                        $updateItem .= ' = ' . strtoupper($post['op'][$key]) . '()';
                        break;
                    case 'md5': case 'sha1': case 'password': case 'encrypt':
                        $updateItem .= ' = ' . strtoupper($post['op'][$key]) . '("' . $this->tableAdmin->escapeSQL($value) . '")';
                        break;
                    case 'random':
                        $updateItem .= ' = RAND() * ' . (int)$this->tableAdmin->escapeSQL($value);
                        break;
                    case 'append':
                        $updateItem .= ' = CONCAT(' . $updateItem . ', "' . $this->tableAdmin->escapeSQL($value) . '")';
                        break;
                    case 'prepend':
                        $updateItem .= ' = CONCAT("' . $this->tableAdmin->escapeSQL($value) . '", ' . $updateItem . ')';
                        break;
                    case 'add': case 'remove':
                        $tmp = 0;
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                $tmp |= $v;
                            }
                        }
                        if (!$tmp) {
                            continue(2);
                        }
                        $updateItem .= ' = ' . $updateItem . ($post['op'][$key] == 'add' ? ' | ' : ' & ~') . $tmp;
                        break;
                    default:
                        // warning?
                }
                $update []= $updateItem;
            }
            $where = implode(' OR ', $where);
            $update = implode(', ', $update);
            if ($update) {
                $sql = 'UPDATE ' . $this->tableAdmin->escapeDbIdentifier($post['database-table']) . ' SET ' . $update . Tools::wrap($where, ' WHERE ');
                //Tools::dump($sql,$post);exit;
                Tools::resolve($this->MyCMS->dbms->query($sql),
                    $this->tableAdmin->translate('Selected records saved.'),
                    $this->tableAdmin->translate('Could not save selected records.')
                );
            } else {
                Tools::addMessage('warning', $this->tableAdmin->translate('Nothing to save.'));
            }
        } while (false);
*/
        //unset($_SESSION['token'][array_search($post['token'], $_SESSION['token'])]);
    }

    /**
     * It is public for PHPUnit test
     *
     * @param string $agenda
     * @return array<array<string|array<mixed>>>
     */
    public function getAgenda($agenda)
    {
        if (!isset($this->agendas[$agenda])) {
            return [];
        }
        $result = $correctOrder = [];
        /** @var array<string|array<mixed>> $options array of agenda set in admin.php in $AGENDAS */
        $options = $this->agendas[$agenda];
        $optionsTable = (isset($options['table']) && is_string($options['table'])) ?
            ($options['table'] ?: $agenda) : $agenda;
        Tools::setifempty($options['sort']);
        Tools::setifempty($options['path']);
        // Note: F code uses also ['join']
        $selectExpression = (isset($options['path']) && is_string($options['path'])) ?
            ('CONCAT(REPEAT("… ",LENGTH(' . $this->MyCMS->dbms->escapeDbIdentifier($options['path']) . ') / '
            . PATH_MODULE . ' - 1),' . $optionsTable . '_' . DEFAULT_LANGUAGE . ')') :
            (
                isset($options['column']) ? (is_array($options['column']) ? (
                    'CONCAT(' . implode(
                        ",'|',",
                        array_map([$this->MyCMS->dbms, 'escapeDbIdentifier'], $options['column'])
                    ) . ')'
                ) : $this->MyCMS->dbms->escapeDbIdentifier($options['column'])) :
            $this->MyCMS->dbms->escapeDbIdentifier($optionsTable . '_' . DEFAULT_LANGUAGE)
            );
        Assert::nullOrString($options['sort']);
        Assert::nullOrString($options['path']);
        $sql = 'SELECT id,' . $selectExpression . ' AS name'
            . Tools::wrap($options['sort'], ',', ' AS sort') . Tools::wrap($options['path'], ',', ' AS path')
            . ' FROM ' . $this->MyCMS->dbms->escapeDbIdentifier(TAB_PREFIX . $optionsTable)
            . Tools::wrap(isset($options['where']) ? $options['where'] : '', ' WHERE ')
            . Tools::wrap(
                $options['sort'] . ($options['sort'] && $options['path'] ? ',' : '') . $options['path'],
                ' ORDER BY '
            )
            . ' LIMIT ' . $this::PROCESS_LIMIT;
        $query = $this->MyCMS->dbms->queryStrictObject($sql);
        for ($i = 1; $row = $query->fetch_assoc(); $i++) {
            $row['name'] = Tools::shortify(strip_tags($row['name']), 100);
            $result [] = $row;
            if ($agenda === 'product' && isset($row['sort']) && $row['sort'] != $i) {
                $correctOrder[$row['id']] = $i;
            }
        }
        // TODO does the next foreach have any impact on the returned value?
        foreach ($correctOrder as $key => $value) {
            $this->MyCMS->dbms->query('UPDATE `' . $this->MyCMS->dbms->escapeDbIdentifier(
                TAB_PREFIX . $optionsTable
            ) . '` SET sort=' . (int) $value . ' WHERE id=' . (int) $key . ' LIMIT 1');
        }
        return $result;
    }
}
