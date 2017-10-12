<?php

namespace GodsDev\MyCMS;

use \GodsDev\Tools\Tools;

/**
 * This class facilitates administration of a database table
 */
class TableAdmin extends TableLister {

    private $csrf;

    /** Constructor
     * @param object $dbms database management system (e.g. new mysqli())
     * @param string $table table name
     * @param array $options
     */
    function __construct($dbms, $table, $options = array())
    {
        parent::__construct($dbms, $table, $options);
    }

    /** Output HTML form to edit specific row in the table
     * @param mixed $where to identify which row to fetch and offer for edit
     *     e.g. array('id' => 5) translates as "WHERE id=5" in SQL
     * @param array $options additional options
     * @return void
     */
    public function outputForm($where, $options = array())
    {
        $record = array();
        $options['include-fields'] = isset($options['include-fields']) && is_array($options['include-fields']) ? $options['include-fields'] : array_keys($this->fields);
        $options['exclude-fields'] = isset($options['exclude-fields']) && is_array($options['exclude-fields']) ? $options['exclude-fields'] : array();
        foreach ($options['exclude-fields'] as $key => $value) {
            if (in_array($value, $options['include-fields'])) {
                unset($options['include-fields'][$key]);
            }
        }
        if (!is_null($where)) { 
            if (is_scalar($where)) {
                $where = array('id' => $where);
            }
            $sql = array();
            foreach ($where as $key => $value) {
                $sql []= Tools::escapeDbIdentifier($key) . '="' . $this->escape($value) . '"'; 
            }
            $sql = 'SELECT ' . Tools::arrayListed($options['include-fields'], 64, ',', '`', '`') . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' WHERE ' . implode(' AND ', $sql) . ' LIMIT 1';
            $record = $this->dbms->query($sql);
            if (is_object($record)) {
                $record = $record->fetch_assoc();
            }
        }
        $output = (isset($options['exclude-form']) ? '' : '<form method="post"><fieldset>') . PHP_EOL
            . Tools::htmlInput('database-table', '', $this->table, 'hidden') . PHP_EOL
            . Tools::htmlInput('form-csrf', '', $_SESSION['csrf-' . $this->table] = rand(1e8, 1e9 - 1), 'hidden') . PHP_EOL
            . '<table class="database">';
        foreach ($this->fields as $key => $field) {
            if (!in_array($key, $options['include-fields']) || in_array($key, $options['exclude-fields'])) {
                continue;
            }
            $output .= $this->outputField($field, $key, $record);
        }
        $output .= '</table>' . PHP_EOL;
        if (function_exists('TableAdminCustomRecordDetail')) {
            $output .= TableAdminCustomRecordDetail($this->table, $record, $this);
        }
        if (!isset($options['exclude-actions'])) {
            $output .= '<hr /><div class="form-actions">' . PHP_EOL;
            if (function_exists('TableAdminCustomRecordAction')) {
                $output .= TableAdminCustomRecordAction($this->table, $record, $this);
            }
            $output .= '<button type="submit" name="record-save" value="1" '
                . 'class="btn btn-default btn-primary"><span class="glyphicon glyphicon-floppy-save"></span> Uložit</button> '; 
            if (is_array($record)) {
                $output .= '<button type="submit" name="record-delete" class="btn btn-default" value="1" onclick="return confirm(\'Opravdu smazat?\');">'
                    . '<span class="glyphicon glyphicon-floppy-remove"></span> Smazat</button>';
            }
            $output .= '</div>';
        }
        $output .= (isset($options['exclude-form']) ? '' : '</fieldset></form>') . PHP_EOL;
        echo $output;
    }

    protected function outputField($field, $key, $record)
    {
        $value = $record[$key];
        $output = '<tr><td><label for="' . Tools::h($key) . $this->rand . '">' . Tools::h($key) . ':</label></td>' . PHP_EOL . '<td>' 
            . Tools::htmlInput(($field['type'] == 'enum' ? $key : "nulls[$key]"), ($field['type'] == 'enum' && $field['null'] ? 'null' : ''), 1, 
                array(
                    'type' => ($field['type'] == 'enum' ? 'radio' : 'checkbox'),
                    'title' => ($field['null'] ? 'null' : null),
                    'disabled' => ($field['null'] ? null : 'disabled'),
                    'checked' => (is_null($value) ? 'checked' : null),
                    'class' => 'input-null'
                )
            ) . '</td>' . PHP_EOL .'<td>';
        $input = array('id' => $key . $this->rand);
        if (function_exists('TableAdminCustomInput')) {
            $custom = TableAdminCustomInput($this->table, $key, $value, $this);
            if ($custom !== false) {
                $input = $custom;
                $field['type'] = null;
            }
        }
        $comment = json_decode((isset($field['comment']) ? $field['comment'] : '') ?: '{}', true);
        Tools::setifnull($comment['display']); 
        if ($comment['display'] == 'option') {
            $query = $this->dbms->query($sql = 'SELECT DISTINCT ' . Tools::escapeDbIdentifier($key) 
                . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' ORDER BY ' . Tools::escapeDbIdentifier($key) . ' LIMIT 1000');
            $input = '<select name="' . Tools::h($key) . '" id="' . Tools::h($key . $this->rand) . '"'
                . ($comment['display'] == 'option+' ? ' onchange="$(\'#' . Tools::h($key . $this->rand) . '_\').val(null)"' : '') . '>';
            while ($row = $query->fetch_row()) {
                $input .= Tools::htmlOption($row[0], $row[0], $value);
            }
            $input .= '</select>';
            if (isset($comment['display-own']) && $comment['display-own']) {
                $input .= ' ' . Tools::htmlInput("own[$key]", 'vlastní:', '', 
                    array('id' => $key . $this->rand . '_', 'onchange' => "$('#$key$this->rand').val(null);"));
            }
            $field['type'] = null;
        }
        if (isset($comment['edit']) && $comment['edit'] == 'json') {
            $tmp = json_decode($value, true);
            $output .= '<fieldset class="input-expanded">' . Tools::htmlInput($key . EXPAND_INFIX, '', 1, 'hidden');
            if (!is_array($tmp) && isset($comment['subfields']) && is_array($comment['subfields'])) {
                foreach ($comment['subfields'] as $v) {
                    Tools::setifnull($tmp[$v], null);
                }
            }
            if (is_array($tmp)) {
                foreach ($tmp as $k => $v) {
                    $output .= '<label>' . Tools::h($k) . ': ' . Tools::htmlInput($key . EXPAND_INFIX . $k, '', $v, array('class' => 'form-control')) . "</label><br />\n";
                }
            }
            $output .= '</fieldset>';
            $input = false;
            $field['type'] = null;
        }
        switch ($field['type']) {
            case 'tinyint': case 'smallint': case 'int': case 'mediumint': case 'bigint': case 'year':
                $input += array('type' => 'number', 'step' => 1, 'class' => 'form-control');
                if ($field['key'] == 'PRI') {
                    $input['readonly'] = 'readonly';
                }
                break;
            case 'date':
                $input += array(/*'type' => 'date',*/ 'class' => 'form-control input-date');
                break;
            case 'time':
                $input += array(/*'type' => 'time', 'step' => 1,*/ 'class' => 'form-control input-time');
                break;
            case 'decimal': case 'float': case 'double':
                $value = +$value;
                $input += array('class' => 'form-control text-right');
                break;
            case 'datetime': case 'timestamp':
                if (isset($value[10]) && $value[10] == ' ') {
                    $value[10] = 'T';
                }
                $input += array('type' => 'datetime-local', 'step' => 1, 'class' => 'form-control input-datetime');
                break;
            case 'bit':
                $input += array('type' => 'checkbox', 'step' => 1, 'checked' => ($value ? 'checked' : null));
                break;
            case 'enum':
                eval('$choices = array(' . str_replace("''", "\\'", $field['size']) . ');'); //@todo safety
                if (is_array($choices)) {
                    foreach ($choices as $k => $v) {
                        $input[$k] = Tools::htmlInput($key, $v, 1 << $k, array(
                            'type' => 'radio', 
                            'id' => "$key-" . (1 << $k),
                            'value' => (1 << $k)
                        ));
                    }
                    $input = array_merge(array(Tools::htmlInput($key, 'prázdné', 0, 
                        array(
                            'type' => 'radio', 
                            'id' => "$key-0",
                            'value' => 0
                        ))), $input
                    );
                    $input = implode(', ', $input);
                }
                break;
            case 'set':
                eval('$choices = array(' . str_replace("''", "\\'", $field['size']) . ');'); //@todo safety
                $checked = explode(',', $value);
                if (is_array($choices)) {
                    $temp = array();
                    foreach ($choices as $k => $v) {
                        $temp[$k] = Tools::htmlInput("$key-$k", $v, 1 >> ($k + 1), array(
                            'type' => 'checkbox',
                            'checked' => in_array($v, $checked) ? 'checked' : null,
                            'id' => "$key-$k-$this->rand"
                        ));
                    }
                    $input = implode(', ', $temp);
                }
                break;
            case 'tinyblob': case 'mediumblob': case 'blob': case 'longblob': case 'binary':
                $input = '<a href="special.php?action=fetch'
                    . '&amp;table=' . urlencode($this->table)
                    . '&amp;column=' . urlencode($key);
                foreach ($where as $k => $v) {
                	$input .= '&amp;key[]=' . urlencode($k) . '&amp;value[]=' . urlencode($v);
                }
                $input .= '&amp;form-csrf=' . $_SESSION['csrf-' . $this->table]
                    . '" target="_blank" >download</a>' . PHP_EOL;
                break;
            case null:
                break;
            case 'char': case 'varchar':
                if ($field['size'] > 1024) {
                    $input = Tools::htmlTextarea("fields[$key]", $value, false, false, 
                        array('id' => $key . $this->rand, 'class' => 'form-control'));
                }
            default:
                $input = Tools::htmlTextarea("fields[$key]", $value, false, false, 
                    array('id' => $key . $this->rand, 'class' => 'form-control' . ($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : ''))
                );
        }
        if (is_array($input)) {
            $input = Tools::htmlInput("fields[$key]", false, $value, $input);
        }
        $output .= $input . '</td></tr>' . PHP_EOL;
        return $output;
    }

    /** Get all tables (and comments to them) in the database and store them to tables
     */
    public function getTables()
    {
        $this->tables = array();
        $query = $this->dbms->query('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = "' . $this->escape($this->options['database']) . '"');
        while ($row = $query->fetch_row()) {
            $this->tables[$row[0]] = $row[1];
        }
    }

    /** Output HTML select for picking a path (project-specific)
     * @param string name of the table (without prefix) and main column
     * @param int path_id reference to the path
     * @param array options
     * @return string HTML <select>
     */
    public function outputSelectPath($name, $path_id = null, $options = array())
    {
        if ($module = $this->dbms->query($sql='SHOW FULL COLUMNS FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name) . ' WHERE FIELD="' . $this->escape($name) . '"')) {
            $module = json_decode($module->fetch_assoc()['Comment'], true);
            $module = isset($module['module']) ? $module['module'] : 10;
        } else {
            $module = 10;
        }
        $output = '<select name="' . Tools::h(isset($options['name']) ? $options['name'] : 'path_id') 
            . '" class="' . Tools::h(isset($options['class']) ? $options['class'] : '') 
            . '" id="' . Tools::h(isset($options['id']) ? $options['id'] : '') . '">' 
            . Tools::htmlOption('', '--vyberte--');
        $query = $this->dbms->query($sql='SELECT id,LENGTH(path)/' . $module . '-1 AS path_length,' . Tools::escapeDbIdentifier($name) 
            . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name) . ' ORDER BY path');
        if (!$query) {
            return $output . '</select>';
        }
        $options['exclude'] = isset($options['exclude']) ? $options['exclude'] : '';
        while ($row = $query->fetch_assoc()) {
            if ($row[$name] != $options['exclude']) {
                $output .= Tools::htmlOption($row['id'], str_repeat('…', $row['path_length']) . $row['category'], $path_id);
            }
        }
        $output .= '</select>';
        return $output;
    }

    /** Is user authorized to proceed with data-changing operation?
     * @return bool
     */
    public function authorized()
    {
        return isset($_POST['database-table'], $_SESSION['csrf-' . $_POST['database-table']], $_POST['form-csrf']) 
            && $_SESSION['csrf-' . $_POST['database-table']] == $_POST['form-csrf'];
    }

    /** Perform the detault record saving command.
     * @return void
     */
    public function recordSave($messageSuccess = false, $messageError = false)
    {
        if (!$this->authorized()) {
            return false;
        }
        $sql = $where = '';
        if (is_array($this->fields)) {
            foreach ($_POST as $key => $value) {
                if (Tools::ends($key, EXPAND_INFIX)) {
                    $tmp = array();
                    foreach ($_POST as $k => $v) {
                        if (Tools::begins($k, $key) && $k != $key) {
                            $tmp[substr($k, strlen($key))] = $v;
                            unset($_POST[$k]);
                        }
                    }
                    $_POST[substr($key, 0, strlen($key) - strlen(EXPAND_INFIX))] = json_encode($tmp);
                    unset($_POST[$key]);
                }
            }
            foreach ($this->fields as $key => $field) {
                if (!isset($_POST['fields'][$key]) || !is_scalar($_POST['fields'][$key])) {
                    continue;
                } 
                $value = $_POST['fields'][$key];
                if (isset($_POST['nulls'][$key]) || (isset($field['foreign_table']) && $value === '')) {
                    $value = null;
                } elseif (isset($_POST['own'][$key]) && $_POST['own'][$key]) {
                    $value = $_POST['own'][$key];
                }
                switch ($field['basictype']) {
                    case 'integer': case 'rational':
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '=' 
                            . (is_null($value) ? 'NULL' : +$value);
                        break;
                    default:
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '=' 
                            . (is_null($value) ? 'NULL' : '"' . $this->escape($value) . '"');
                }
                if (Tools::among($field['key'], 'PRI', 'UNI')) {
                    $where .= ' AND ' . Tools::escapeDbIdentifier($key) . '=' . (is_null($value) ? 'NULL' : '"' . $this->escape($value) . '"');
                }
            }
        }
        if ($sql) {
            $sql = 'UPDATE ' . Tools::escapeDbIdentifier($this->table) . ' SET ' . mb_substr($sql, 1) . Tools::wrap(mb_substr($where, 5), ' WHERE ') . ' LIMIT 1';
            if (!$this->resolveSQL($sql, $messageSuccess ?: 'Záznam uložen.', $messageError ?: 'Záznam se nepodařilo uložit. #%errno%: %error%')) {
                //@todo if unsuccessful, store data being saved to session
            }
        }
    }

    public function recordDelete()
    {
        $sql = array();
        if ($this->authorized() && isset($_GET['where'], $_GET['table']) && $_GET['table']
            && is_array($_GET['where']) && count($_GET['where'])) {
            foreach ($_GET['where'] as $key => $value) {
                $sql []= Tools::escapeDbIdentifier($key) . '="' . $this->escape($value) . '"'; 
            }
            $sql = 'DELETE FROM ' . Tools::escapeDbIdentifier($_GET['table']) . ' WHERE ' . implode(' AND ', $sql);
        }                                                       
        $this->resolveSQL($sql, 'Záznam smazán.', 'Záznam se nepodařilo smazat.');
    }

    public function dashboard()
    {
        $query = $this->dbms->query('SELECT SQL_CALC_FOUND_ROWS type,COUNT(type) FROM ' . TAB_PREFIX . 'page GROUP BY type WITH ROLLUP LIMIT 100');
        if (!$query) {
            return;
        }
        $totalRows = $this->dbms->query('SELECT FOUND_ROWS()')->fetch_row()[0];
        echo '<h2 class="sub-header">Obsah podle typů</h2>' . PHP_EOL 
            . '<table class="table table-striped">' . PHP_EOL 
            . '<tr><th>Typ</th><th class="text-right">Počet</th></tr>' . PHP_EOL;
        while ($row = $query->fetch_row()) {
            echo '<tr><td>' . ($row[0] ? Tools::h($row[0]) : ($row[0] === '' ? '<i class="insipid">(empty)</i>' : '<big>&Sum;</big>')) 
                . '</td><td class="text-right">' . +$row[1] . '</td></tr>' . PHP_EOL;
        }
        echo '</table>';
    }
}
