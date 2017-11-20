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
        $output = (isset($options['exclude-form']) ? '' : '<form method="post" enctype="multipart/form-data"><fieldset>') . PHP_EOL
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
                . 'class="btn btn-default btn-primary"><span class="glyphicon glyphicon-floppy-save fa fa-floppy-o"></span> ' . $this->translate('Save') . '</button> ';
            if (is_array($record)) {
                $output .= '<button type="submit" name="record-delete" class="btn btn-default" value="1" onclick="return confirm(\'' . $this->translate('Really delete?') . '\');">'
                    . '<span class="glyphicon glyphicon-floppy-remove fa fa-trash-o"></span> ' . $this->translate('Delete') . '</button>';
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
            . Tools::htmlInput(($field['type'] == 'enum' ? $key : "fields-null[$key]"), ($field['type'] == 'enum' && $field['null'] ? 'null' : ''), 1,
                array(
                    'type' => ($field['type'] == 'enum' ? 'radio' : 'checkbox'),
                    'title' => ($field['null'] ? $this->translate('Insert NULL') : null),
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
        if (!is_null($field['type']) && $comment['display'] == 'option') {
            $query = $this->dbms->query($sql = 'SELECT DISTINCT ' . Tools::escapeDbIdentifier($key)
                . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' ORDER BY ' . Tools::escapeDbIdentifier($key) . ' LIMIT 1000');
            $input = '<select name="fields[' . Tools::h($key) . ']" id="' . Tools::h($key . $this->rand) . '" class="form-control d-inline-block w-initial"'
                . (isset($comment['display-own']) ? ' onchange="$(\'#' . Tools::h($key . $this->rand) . '_\').val(null)"' : '') . '>';
            while ($row = $query->fetch_row()) {
                $input .= Tools::htmlOption($row[0], $row[0], $value);
            }
            $input .= '</select>';
            if (isset($comment['display-own']) && $comment['display-own']) {
                $input .= ' ' . Tools::htmlInput("fields-own[$key]", $this->translate('Own value:'), '',
                    array('id' => $key . $this->rand . '_', 'class' => 'form-control d-inline-block w-initial', 'onchange' => "$('#$key$this->rand').val(null);"));
            }
            $field['type'] = null;
        }
        if (!is_null($field['type']) && isset($comment['edit']) && $comment['edit'] == 'json') {
            $json = json_decode($value, true);
            $output .= '<div class="input-expanded">' . Tools::htmlInput($key . EXPAND_INFIX, '', 1, 'hidden');
            if (!is_array($json) && isset($comment['subfields']) && is_array($comment['subfields'])) {
                foreach ($comment['subfields'] as $v) {
                    Tools::setifnull($json[$v], null);
                }
            }
            if (is_array($json)) {
                $output .= '<table class="w-100 json-expanded">';
                foreach ($json + array('' => '') as $k => $v) {
                    $output .= '<tr><td class="first w-25">' . Tools::htmlInput(EXPAND_INFIX . $key . '[]', '', $k, array('class' => 'form-control form-control-sm')) . '</td>'
                        . '<td class="second w-75">' . Tools::htmlInput(EXPAND_INFIX . EXPAND_INFIX . $key . '[]', '', $v, array('class' => 'form-control form-control-sm')) . '</td></tr>' . PHP_EOL;
                }
                $output .= '</table>';
            }
            if (!$json) {
                $output .= '<textarea name="fields[' . Tools::h($key) . ']" class="w-100"></textarea>';
            }
            $output .= '</div>';
            $input = false;
            $field['type'] = null;
        }
        if (!is_null($field['type']) && isset($comment['foreign-table'], $comment['foreign-column']) && $comment['foreign-table'] && $comment['foreign-column']) {
            $output .= $this->outputForeignId(
                "fields[$key]", 
                'SELECT id,' . Tools::escapeDbIdentifier($comment['foreign-column']) . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $comment['foreign-table']),
                $value, array('class' => 'form-control'));
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
                    . '" target="_blank" >' . $this->translate('Download') . '</a>' . PHP_EOL;
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
            . Tools::htmlOption('', $this->translate('--choose--'));
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

    /** Output HTML <select name=$field> with $values as its items
     * @param string $field name of the select element
     * @param mixed $values either array of values for the <select>
     *        or string with the SQL SELECT statement
     * @param scalar $default original value
     * @param array $options additional options for the element rendition; plus
     *        [exclude] => value to exclude from select's options
     * @return string result
     * note: $values as an array can have scalar values (then they're used as each <option>'s text/label)
     *       or it can be an array of arrays (then first element is used as label and second as a group (for <optgroup>)).
     *       Similarly, $values as string can select 2 columns (same as first case)
     *        or 3+ columns (then first will be <option>'s value, second its label, and third <optgroup>)
     */       
    public function outputForeignId($field, $values, $default = null, $options = array())
    {
        //@todo kdy může nastat situace, že GodsDev\\MyCMS\\addHtmlOption neexistuje?
        if (!function_exists('GodsDev\\MyCMS\\addHtmlOption')) {
            function addHtmlOption($value, $text, $group, $default, $options) {
                global $lastGroup;
                $result = '';
                if ($lastGroup != $group) {
                    $result .= ($lastGroup === false ? '' : '</optgroup>') . '<optgroup label="' . Tools::h($lastGroup = $group) . '" />';
                }
                if ($value != $options['exclude']) {
                    $result .= Tools::htmlOption($value, $text, $default);
                }
                return $result;
            }
        }
        $result = '<select name="' . Tools::h($field)
            . '" class="' . Tools::h(isset($options['class']) ? $options['class'] : '')
            . '" id="' . Tools::h(isset($options['id']) ? $options['id'] : '') . '">'
            . '<option value=""></option>';
        $options['exclude'] = isset($options['exclude']) ? $options['exclude'] : '';
        $group = $lastGroup = false;
        if (is_array($values)) { // array - just output them as <option>s
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $group = next($value);
                    $value = reset($value);
                }
                $result .= addHtmlOption($key, $value, $group, $default, $options);
            }
        } elseif (is_string($values)) { // string - SELECT id,name FROM ...
            if ($query = $this->dbms->query($values)) {
                while ($row = $query->fetch_row()) {
                    $result .= addHtmlOption($row[0], $row[1], isset($row[2]) ? $row[2] : false, $default, $options);
                }
            }
        }
        $result .= ($lastGroup === false ? '' : '</optgroup>') . '</select>';
        return $result;
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
        $command = 'UPDATE ';
        if (is_array($this->fields)) {
            foreach ($_POST as $key => $value) {
                if (Tools::begins($key, EXPAND_INFIX) && !Tools::begins($key, EXPAND_INFIX . EXPAND_INFIX)) {
                    $_POST['fields'][$key = substr($key, strlen(EXPAND_INFIX))] = array_combine($_POST[EXPAND_INFIX . $key], $_POST[EXPAND_INFIX . EXPAND_INFIX . $key]);
                    unset($_POST['fields'][$key]['']);
                    $_POST['fields'][$key] = json_encode($_POST['fields'][$key]);
                    unset($_POST[$key], $_POST[EXPAND_INFIX . $key]);
                }
            }
            foreach ($this->fields as $key => $field) {
                if (isset($_POST['fields-null'][$key]) || (isset($field['foreign_table']) && $value === '')) {
                    $_POST['fields'][$key] = null;
                } elseif (isset($_POST['fields-own'][$key]) && $_POST['fields-own'][$key]) {
                    $_POST['fields'][$key] = $_POST['fields-own'][$key];
                }
                if (!isset($_POST['fields'][$key]) || !is_scalar($_POST['fields'][$key])) {
                    continue;
                }
                $value = $_POST['fields'][$key];
                if ($field['key'] == 'PRI' && ($value === '' || $value === null)) {
                    $command = 'INSERT INTO ';
                    continue;
                }
                switch ($field['basictype']) {
                    case 'integer': case 'rational':
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '='
                            . (is_null($value) ? 'NULL' : ($field['basictype'] == 'integer' ? (int)$value : (double)$value));
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
            $sql = $command . Tools::escapeDbIdentifier($this->table) . ' SET ' . mb_substr($sql, 1) . Tools::wrap(mb_substr($where, 5), ' WHERE ') . ($command == 'UPDATE ' ? ' LIMIT 1' : '');
            if ($this->resolveSQL($sql, $messageSuccess ?: $this->translate('Record saved.'), $messageError ?: $this->translate('Could not save the record.') . ' #%errno%: %error%')) {
                return true;
            } else {
                //@todo if unsuccessful, store data being saved to session
                return false;
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
        $this->resolveSQL($sql, $this->translate('Record deleted.'), $this->translate('Could not delete the record.'));
    }

    public function dashboard($options = array())
    {
        $this->contentByType($options);
    }

    public function contentByType($options = array())
    {
        Tools::setifnull($options['table'], 'content');
        Tools::setifnull($options['type'], 'type');
        $query = $this->dbms->query('SELECT SQL_CALC_FOUND_ROWS ' . Tools::escapeDbIdentifier($options['type']) . ',COUNT(' . Tools::escapeDbIdentifier($options['type']) . ')'
            . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $options['table']) 
            . ' GROUP BY ' . Tools::escapeDbIdentifier($options['type']) . ' WITH ROLLUP LIMIT 100');
        if (!$query) {
            return;
        }
        $typeIndex = 0;
        foreach (array_keys($this->fields) as $key => $value) {
            if ($value == $options['type']) {
                $typeIndex = $key + 1;
                break;
            }
        }
        $totalRows = $this->dbms->query('SELECT FOUND_ROWS()')->fetch_row()[0];
        echo '<details><summary><big>' . $this->translate('By type') . '</big></summary>' . PHP_EOL
            . '<table class="table table-striped">' . PHP_EOL
            . '<tr><th>' . $this->translate('Type') . '</th><th class="text-right">' . $this->translate('Count') . '</th></tr>' . PHP_EOL;
        while ($row = $query->fetch_row()) {
            echo '<tr><td>' . ($row[0] ? Tools::h($row[0]) : ($row[0] === '' ? '<i class="insipid">(' . $this->translate('empty') . ')</i>' : '<big>&Sum;</big>'))
                . '</td><td class="text-right"><a href="?table=' . Tools::h(TAB_PREFIX . $options['table']) . '&amp;col[0]=' . $typeIndex . '&amp;val[0]=' . Tools::h($row[0]) . '" title="' . $this->translate('Filter records') . '">' . (int)$row[1] . '</td></tr>' . PHP_EOL;
        }
        echo '</table></details>';
    }
}