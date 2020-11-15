<?php

namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;

/**
 * This class facilitates administration of a database table
 */
class MyTableAdmin extends MyTableLister
{
    use \Nette\SmartObject;

    /**
     * Output HTML form to edit specific row in the table
     *
     * @param mixed $where to identify which row to fetch and offer for edit
     *      e.g. ['id' => 5] translates as "WHERE id=5" in SQL
     *      scalar value translates as ['id' => value]
     * @param array $options additional options
     *      [include-fields] - array of fields to include only
     *      [exclude-fields] - array of fields to exclude
     *      [exclude-form] - exclude the <form> element
     *      [exclude-actions] - exclude form actions (save, delete)
     *      [layout-row] - non-zero: divide labels and input elements by <br />, by default they're in <table>
     *      [prefill] - assoc. array with initial field values (only when inserting new record)
     *      [original] - keep original values (to update only changed fields)
     *      [tabs] - divide fields into Bootstrap tabs, e.g. [null, 'English'=>'/^.+_en$/i', 'Chinese'=>'/^.+_cn$/i']
     *      [return-output] - non-zero: return output (instead of echo $output)
     * @return mixed void or string if $option[return-output] is non-zero
     *      TODO can't be void|string, as it wouldn't be possible to make string operations on void result
     */
    public function outputForm($where, array $options = [])
    {
        $options['include-fields'] = isset($options['include-fields']) && is_array($options['include-fields']) ? $options['include-fields'] : array_keys($this->fields);
        $options['exclude-fields'] = isset($options['exclude-fields']) && is_array($options['exclude-fields']) ? $options['exclude-fields'] : [];
        foreach ($options['exclude-fields'] as $key => $value) {
            if (in_array($value, $options['include-fields'])) {
                unset($options['include-fields'][$key]);
            }
        }
        if (!is_null($where) && $where != ['']) {
            if (is_scalar($where)) {
                $where = ['id' => $where];
            }
            $sql = [];
            foreach ($where as $key => $value) {
                $sql [] = Tools::escapeDbIdentifier($key) . '="' . $this->escapeSQL($value) . '"';
            }
            $sql = 'SELECT ' . $this->dbms->listColumns($options['include-fields'], $this->fields) . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' WHERE ' . implode(' AND ', $sql) . ' LIMIT 1';
            $record = $this->dbms->query($sql);
            if (is_object($record)) {
                $record = $record->fetch_assoc();
            }
        }
        $record = isset($record) && is_array($record) ? $record : [];
        $tmp = isset($record[substr($this->table, strlen(TAB_PREFIX))]) ? $record[substr($this->table, strlen(TAB_PREFIX))] : null;
        $tmp = is_null($tmp) && isset($record[substr($this->table, strlen(TAB_PREFIX)) . '_' . DEFAULT_LANGUAGE]) ? $record[substr($this->table, strlen(TAB_PREFIX)) . '_' . DEFAULT_LANGUAGE] : '';
        $this->script .= 'AdminRecordName = ' . json_encode($tmp) . ';' . PHP_EOL;
        Tools::setifempty($options['layout-row'], true);
        $output = (isset($options['exclude-form']) && $options['exclude-form'] ? '' : '<form method="post" enctype="multipart/form-data" class="record-form"><fieldset>') . PHP_EOL
            . Tools::htmlInput('table', '', $this->table, 'hidden') . PHP_EOL
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden') . PHP_EOL;
        $tabs = [$this->fields];
        if (isset($options['tabs']) && is_array($options['tabs'])) {
            foreach ($options['tabs'] as $key => $value) {
                foreach ($this->fields as $k => $field) {
                    if ($value && preg_match($value, $k)) {
                        $tabs[$key][$k] = $field;
                        unset($tabs[0][$k]);
                    }
                }
            }
        }
        if (count($tabs) > 1) {
            $output .= '<nav class="nav nav-tabs" role="tablist">';
            foreach ($tabs as $tabKey => $tab) {
                $tmp = Tools::webalize($this->table . '-' . $tabKey);
                $output .= '<a class="nav-item nav-link' . ($tabKey === 0 ? ' active' : '') . '" id="nav-' . $tmp . '" data-toggle="tab" href="#tab-' . $tmp . '" role="tab" aria-controls="nav-profile" aria-selected="' . ($tabKey === 0 ? 'true' : 'false') . '">'
                    . ($tabKey === 0 ? '<span class="glyphicon glyphicon-list fa fa-list" aria-hidden="true"></span>' : Tools::h($tabKey)) . '</a>' . PHP_EOL;
            }
            $output .= '</nav>' . PHP_EOL . '<div class="tab-content">';
        }
        foreach ($tabs as $tabKey => $tab) {
            $output .= (count($tabs) > 1 ? '<div class="tab-pane fade' . ($tabKey === 0 ? ' show active' : '') . '" id="tab-' . ($tmp = Tools::webalize($this->table . '-' . $tabKey)) . '" role="tabpanel" aria-labelledby="nav-' . $tmp . '">' : '')
                . ($options['layout-row'] ? '<div class="database">' : '<table class="database">');
            foreach ($tab as $key => $field) {
                if (!in_array($key, $options['include-fields']) || in_array($key, $options['exclude-fields'])) {
                    continue;
                }
                $output .= $this->outputField($field, $key, $record, $options);
            }
            $output .= ($options['layout-row'] ? '</div>' : '</table>') . PHP_EOL
                . (count($tabs) > 1 ? '</div>' : '');
        }
        $output .= (count($tabs) > 1 ? '</div>' : '') . $this->customRecordDetail($record);
        if (!isset($options['exclude-actions']) || !$options['exclude-actions']) {
            $output .= '<hr /><div class="form-actions">' . PHP_EOL
                . $this->customRecordActions($record)
                . '<button type="submit" name="record-save" value="1" class="btn btn-default btn-primary">'
                . '<span class="glyphicon glyphicon-floppy-save fa fa-floppy-o fa-save" aria-hidden="true"></span> ' . $this->translate('Save') . '</button> ';
            if ($record) {
                $output .= '<button type="submit" name="record-delete" class="btn btn-default" value="1" onclick="return confirm(\'' . $this->translate('Really delete?') . '\');">' . PHP_EOL
                    . '<span class="glyphicon glyphicon-floppy-remove fa fa-trash-o fa-trash" aria-hidden="true"></span> ' . $this->translate('Delete') . '</button>' . PHP_EOL
                    . Tools::htmlInput('after', '', '', 'hidden') . PHP_EOL
                    . Tools::htmlInput('referer', '', base64_encode(Tools::xorCipher(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?table=' . TAB_PREFIX . $_GET['table'], end($_SESSION['token']))), 'hidden') . PHP_EOL;
            }
            // TODO will be fixed in next Tools version: fix Tools::htmlSelect .. default is mixed not string!
            $output .= '<label><i class="fa fa-location-arrow"></i> ' . Tools::htmlSelect('after', [$this->translate('stay here'), $this->translate('go back')], false, ['class' => 'form-control form-control-sm w-initial d-inline-block']) . '</label></div>';
        }
        $output .= (isset($options['exclude-form']) && $options['exclude-form'] ? '' : '</fieldset></form>') . PHP_EOL;
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
    }

    /**
     * Output appropriate HTML input item for given field.
     *
     * @param array $field
     * @param string $key
     * @param array $record
     * @param array $options
     * @return string
     */
    protected function outputField(array $field, $key, array $record, array $options)
    {
        $value = isset($record[$key]) ? $record[$key] : false;
        if (Tools::among($record, false, [])) {
            if (isset($options['prefill'][$key]) && is_scalar($options['prefill'][$key])) {
                $value = $options['prefill'][$key];
                if (Tools::among($field['type'], 'datetime', 'timestamp') && $options['prefill'][$key] == 'now') {
                    $value = date('Y-m-d\TH:i:s');
                }
            } elseif ($field['default']) {
                $value = $field['default'];
            }
        } elseif (Tools::among($field['type'], 'datetime', 'timestamp') && Tools::among($value, '0000-00-00', '0000-00-00 00:00:00', '0000-00-00T00:00:00')) {
            $value = '';
        }
        $output = (Tools::set($options['layout-row'], false) ? '' : '<tr><td>')
            . '<label for="' . Tools::h($key) . $this->rand . '">' . $this->translateColumn($key) . ':</label>'
            . ($options['layout-row'] ? ' ' : '</td><td>')
            . Tools::htmlInput(
                ($field['type'] == 'enum' ? $key : "fields-null[$key]"),
                ($field['type'] == 'enum' && $field['null'] ? 'null' : ''),
                1,
                [
                    'type' => ($field['type'] == 'enum' ? 'radio' : 'checkbox'),
                    'title' => ($field['null'] ? $this->translate('Insert NULL') : null),
                    'disabled' => ($field['null'] ? null : 'disabled'),
                    'checked' => (Tools::among($value, null, false) ? 'checked' : null),
                    'class' => 'input-null',
                    'id' => 'null-' . urlencode($key) . $this->rand
                ]
            ) . ($options['layout-row'] ? ($field['type'] == 'enum' ? '' : '<br />') : '</td><td>') . PHP_EOL
            . $this->customInputBefore($key, $value, $record) . PHP_EOL;
        // $input is either an array of options for Tools::htmlInput() or already a result string
        $input = ['id' => $key . $this->rand, 'class' => 'form-control'];
        $custom = $this->customInput($key, $value, $record);
        if ($custom !== false) {
            $input = $custom;
            $field['type'] = null;
        }
        $comment = json_decode(isset($field['comment']) ? $field['comment'] : '{}', true);
        Tools::setifnull($comment['display']);
        if (!is_null($field['type']) && $comment['display'] == 'option') {
            $query = $this->dbms->query($sql = 'SELECT DISTINCT ' . Tools::escapeDbIdentifier($key)
                . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' ORDER BY ' . Tools::escapeDbIdentifier($key) . ' LIMIT ' . $this->DEFAULTS['MAXSELECTSIZE']);
            $input = '<select name="fields[' . Tools::h($key) . ']" id="' . Tools::h($key . $this->rand) . '" class="form-control d-inline-block w-initial"'
                . (isset($comment['display-own']) && $comment['display-own'] ? ' onchange="$(\'#' . Tools::h($key . $this->rand) . '_\').val(null)"' : '') . '>'
                . '<option></option>';
            // if prefill value is not yet among the existing values, set as value for the own-value input box
            $ownValue = $value;
            while ($row = $query->fetch_row()) {
                $input .= Tools::htmlOption($row[0], $row[0], $value);
                $ownValue = ($row[0] === $value) ? '' : $ownValue;
            }
            $input .= '</select>';
            if (Tools::nonzero($comment['display-own'])) {
                $input .= ' '
                    . Tools::htmlInput(
                        "fields-own[$key]",
                        $this->translate('Own value:'),
                        $ownValue,
                        [
                            'id' => $key . $this->rand . '_',
                            'class' => 'form-control d-inline-block w-initial',
                            'label-class' => 'mx-3 font-weight-normal',
                            'onchange' => "$('#$key{$this->rand}').val(null);",
                        ]
                    ) . '<br />';
            }
            $field['type'] = null;
        }
        if (!is_null($field['type']) && Tools::set($comment['edit'], false) == 'json') {
            $json = json_decode($value, true) ?: (Tools::among($value, '', '[]', '{}') ? [] : $value);
            $output .= '<div class="input-expanded">' . Tools::htmlInput($key . EXPAND_INFIX, '', 1, 'hidden');
            if (!is_array($json) && isset($comment['subfields']) && is_array($comment['subfields'])) {
                foreach ($comment['subfields'] as $v) {
                    Tools::setifnull($json[$v], null);
                }
            }
            if (is_array($json) && is_scalar(reset($json))) {
                $output .= '<table class="w-100 json-expanded" data-field="' . Tools::h($key) . '">';
                foreach ($json + ['' => ''] as $k => $v) {
                    $output .= '<tr><td class="first w-25">' . Tools::htmlInput(EXPAND_INFIX . $key . '[]', '', $k, ['class' => 'form-control form-control-sm', 'placeholder' => $this->translate('variable')]) . '</td>'
                        . '<td class="second w-75">' . Tools::htmlInput(EXPAND_INFIX . EXPAND_INFIX . $key . '[]', '', $v, ['class' => 'form-control form-control-sm', 'placeholder' => $this->translate('value')]) . '</td></tr>' . PHP_EOL;
                }
                $output .= '</table>';
            } else {
                // TODO ask CRS2 if replacing #3 $cols and #4 $rows false,false by 60,5 as int is expected is the right correction
                $output .= Tools::htmlTextarea("fields[$key]", $value, 60, 5, [
                        'id' => $key . $this->rand, 'data-maxlength' => $field['size'],
                        'class' => 'form-control type-' . Tools::webalize($field['type']) . ($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')
                    ])
                    . '<a href="#" class="json-reset" data-field="' . Tools::h($key) . '"><i class="fa fa-th-list" aria-hidden="true"></i></a>';
            }
            $output .= '</div>';
            $input = false;
            $field['type'] = null;
        }
        if (!is_null($field['type']) && isset($comment['foreign-table'], $comment['foreign-column']) && $comment['foreign-table'] && $comment['foreign-column']) {
            $output .= $this->outputForeignId(
                "fields[$key]",
                'SELECT id,' . Tools::escapeDbIdentifier($comment['foreign-column']) . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $comment['foreign-table']),
                $value,
                ['class' => 'form-control', 'id' => $input['id'], 'exclude' => (TAB_PREFIX . $comment['foreign-table'] == $this->table ? [$value] : [])]
            );
            $input = false;
            $field['type'] = null;
        }
        switch ($field['type']) {
            case 'tinyint':
            case 'smallint':
            case 'int':
            case 'mediumint':
            case 'bigint':
            case 'year':
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += ['type' => 'number', 'step' => 1, 'class' => 'form-control'];
                if ($field['key'] == 'PRI') {
                    $input['readonly'] = 'readonly';
                    $input = '<div class="input-group">' . Tools::htmlInput("fields[$key]", false, $value, $input)
                        . '<span class="input-group-btn"><button class="btn btn-secondary btn-id-unlock" type="button" title="' . $this->translate('Unlock') . '"><i class="glyphicon glyphicon-lock fa fa-lock" aria-hidden="true"></i></button></span></div>';
                }
                break;
            case 'date':
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += [/* 'type' => 'date', */ 'class' => 'form-control input-date'];
                break;
            case 'time':
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += [/* 'type' => 'time', */ 'step' => 1, 'class' => 'form-control input-time'];
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $value = +$value;
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += ['class' => 'form-control text-right'];
                break;
            case 'datetime':
            case 'timestamp':
                if (isset($value[10]) && $value[10] == ' ') {
                    $value[10] = 'T';
                }
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += ['type' => 'datetime-local', 'step' => 1, 'class' => 'form-control input-datetime'];
                $input = '<div class="input-group">' . Tools::htmlInput("fields[$key]", false, $value, $input)
                    . '<span class="input-group-btn"><button class="btn btn-secondary btn-fill-now" type="button" title="' . $this->translate('Now') . '"><i class="glyphicon glyphicon-time fa fa-clock-o fa-clock" aria-hidden="true"></i></button></span></div>';
                break;
            case 'bit':
                // TODO fix Binary operation "+=" between arrays results in an error.
                $input += ['type' => 'checkbox', 'step' => 1, 'checked' => ($value ? 'checked' : null)];
                break;
            case 'enum':
                $choices = $this->dbms->decodeChoiceOptions($field['size']);
                $input = [];
                foreach ($choices as $k => $v) {
                    $input[$k] = Tools::htmlInput("fields[$key]", $v, $k + 1, [
                            'type' => 'radio',
                            'id' => "fields[$key-" . (1 << $k) . "]",
                            'checked' => ($value == $k + 1 ? 'checked' : null),
                            'label-class' => 'font-weight-normal'
                    ]);
                }
                $input = array_merge(
                    [Tools::htmlInput('fields[' . $key . ']', $this->translate('empty') . ' ', 0, [
                            'type' => 'radio',
                            'id' => "fields[$key-0]",
                            'value' => 0,
                            'label-class' => 'font-weight-normal'
                        ])],
                    $input
                );
                $input = ($options['layout-row'] ? '<br>' : '') . implode(', ', $input) . '<br>';
                break;
            case 'set':
                $choices = $this->dbms->decodeSetOptions($field['size']);
                $tmp = [];
                $value = explode(',', $value);
                foreach ($choices as $k => $v) {
                    $tmp[$k] = Tools::htmlInput("fields[$key][$k]", $v === '' ? '<i>' . $this->translate('nothing') . '</i>' : $v, 1 << $k, [
                            'type' => 'checkbox',
                            // todo ask CRS2 is_array($value) seeems to be always true, so Else branch is unreachable because ternary operator condition is always true.
                            'checked' => ((1 << $k) & (int) (is_array($value) ? reset($value) : $value)) ? 'checked' : null,
                            'id' => "$key-$k-$this->rand",
                            'label-html' => $v === '',
                            'label-class' => 'font-weight-normal'
                    ]);
                }
                $input = implode(', ', $tmp) . '<br>';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'blob':
            case 'longblob':
            case 'binary':
                if (preg_match('~(^\pC)*~i', $value)) {
                    $input = '<tt>' . Tools::ifempty(Tools::shortify($value, 100), '<i class="insipid">' . $this->translate('empty') . '</i>') . '</tt><br />'; //@todo constant --> parameter
                } else {
                    $input = '<a href="#" class="download-blob d-block" data-table="' . urlencode($this->table) . '" data-column="' . urlencode($key) . '" '
                        . 'target="_blank" >' . $this->translate('Download') . '</a>' . PHP_EOL;
                }
                break;
            case null:
                break;
            default:
                if (Tools::among($field['type'], 'char', 'varchar') && ($field['size'] < 256 || Tools::set($comment['edit'], false) == 'input') && Tools::set($comment['edit'], false) != 'textarea') {
                    break;
                }
                $input = '<div class="TableAdminTextarea">'
                    // TODO ask CRS2 if replacing #3 $cols and #4 $rows false,false by 60,5 as int is expected is the right correction
                    . Tools::htmlTextarea(
                        "fields[$key]",
                        $value,
                        60,
                        5,
                        ['id' => $key . $this->rand, 'data-maxlength' => $field['size'],
                            'class' => 'form-control type-' . Tools::webalize($field['type']) . ($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')
                        ]
                    )
                    . '<i class="fab fa-stack-overflow input-limit" aria-hidden="true" data-fields="' . Tools::h($key) . '"></i></div>';
        }
        if (is_array($input)) {
            $input = Tools::htmlInput("fields[$key]", false, $value, $input);
        }
        if (isset($options['original']) && $options['original']) {
            if (is_null($value)) {
                $input .= Tools::htmlInput("original-null[$key]", false, 1, 'hidden');
            } else {
                $input .= Tools::htmlInput("original[$key]", false, isset($options['prefill'][$key]) && is_scalar($options['prefill'][$key]) ? '' : $value, 'hidden');
            }
        }
        $output .= $input . $this->customInputAfter($key, $value, $record) . ($options['layout-row'] ? '' : '</td></tr>') . PHP_EOL;
        return $output;
    }

    /**
     * Output HTML select for picking a path (project-specific)
     *
     * @param string $name of the table (without prefix) and main column
     * @param int $path_id reference to the path
     * @param array $options
     * @return string HTML <select>
     */
    public function outputSelectPath($name, $path_id = null, $options = [])
    {
        // TODO what does this construction mean? Call to function is_array() with string will always evaluate to false.
        if (!is_array($name)) {
            $name = ['table' => $name, 'column' => $name];
        }
        if ($module = $this->dbms->query('SHOW FULL COLUMNS FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name['table']) . ' WHERE FIELD="' . $this->escapeSQL($name['column']) . '"')) {
            $module = json_decode($module->fetch_assoc()['Comment'], true);
            $module = isset($module['module']) && $module['module'] ? $module['module'] : 10;
        } else {
            $module = 10;
        }
        $result = '<select name="' . Tools::h(isset($options['name']) ? $options['name'] : 'path_id')
            . '" class="' . Tools::h(isset($options['class']) ? $options['class'] : '')
            . '" id="' . Tools::h(isset($options['id']) ? $options['id'] : '') . '">'
            . Tools::htmlOption('', $this->translate('--choose--'));
        $query = $this->dbms->query('SELECT id,path,' . Tools::escapeDbIdentifier($name['column']) . ' AS category_
            FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name['table']) . ' ORDER BY path');
        if (!$query) {
            return $result . '</select>';
        }
        $options['exclude'] = isset($options['exclude']) ? $options['exclude'] : [];
        $options['path-value'] = isset($options['path-value']) ? $options['path-value'] : false;
        while ($row = $query->fetch_assoc()) {
            if ($row['id'] != $options['exclude']) {
                $result .= Tools::htmlOption($row['id'], str_repeat('. ', strlen($row['path']) / $module - 1) . $row['category_'], $row['path'] === $options['path-value'] ? $row['id'] : $path_id);
            }
        }
        $result .= '</select>';
        return $result;
    }

    protected function addForeignOption($value, $text, $group, &$lastGroup, $default, $options)
    {
        $result = '';
        if ($lastGroup != $group) {
            $result .= ($lastGroup === false ? '' : '</optgroup>') . '<optgroup label="' . Tools::h($lastGroup = $group) . '" />';
        }
        if (!in_array($value, $options['exclude'])) {
            $result .= Tools::htmlOption($value, $text, $default);
        }
        return $result;
    }

    /**
     * Output HTML <select name=$field> with $values as its items
     *
     * @param string $field name of the select element
     * @param mixed $values either array of values for the <select>
     *        or string with the SQL SELECT statement
     * @param scalar $default original value
     * @param array $options additional options for the element rendition; plus
     *        [exclude] => value to exclude from select's options
     *        [class]
     *        [id]
     * @return string result
     * note: $values as an array can have scalar values (then they're used as each <option>'s text/label)
     *       or it can be an array of arrays (then first element is used as label and second as a group (for <optgroup>)).
     *       Similarly, $values as string can select 2 columns (same as first case)
     *        or 3+ columns (then first will be <option>'s value, second its label, and third <optgroup>)
     */
    public function outputForeignId($field, $values, $default = null, $options = [])
    {
        $result = '<select name="' . Tools::h($field)
            . '" class="' . Tools::h(isset($options['class']) ? $options['class'] : '')
            . '" id="' . Tools::h(isset($options['id']) ? $options['id'] : '') . '">'
            . '<option value=""></option>';
        $options['exclude'] = isset($options['exclude']) ? (is_array($options['exclude']) ? $options['exclude'] : [$options['exclude']]) : [];
        $group = $lastGroup = false;
        if (is_array($values)) { // array - just output them as <option>s
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $group = next($value);
                    $value = reset($value);
                }
                $result .= $this->addForeignOption($key, $value, $group, $lastGroup, $default, $options);
            }
        } elseif (is_string($values)) { // string - SELECT id,name FROM ...
            if ($query = $this->dbms->query($values)) {
                while ($row = $query->fetch_row()) {
                    $result .= $this->addForeignOption($row[0], $row[1], isset($row[2]) ? $row[2] : false, $lastGroup, $default, $options);
                }
            }
        }
        $result .= ($lastGroup === false ? '' : '</optgroup>') . '</select>';
        return $result;
    }

    /**
     * Is user authorized to proceed with data-changing operation?
     *
     * @return bool
     */
    public function authorized()
    {
        return isset($_POST['token'], $_SESSION['token']) && is_array($_SESSION['token']) && in_array($_POST['token'], $_SESSION['token']);
    }

    /**
     * Perform the detault record saving command.
     *
     * @param bool $messageSuccess
     * @param bool $messageError
     * @return bool
     */
    public function recordSave($messageSuccess = false, $messageError = false)
    {
        if (!$this->authorized()) {
            return false;
        }
        $sql = $where = '';
        if (is_array($this->fields)) {
            foreach ($_POST as $key => $value) {
                if (Tools::begins($key, EXPAND_INFIX) && !Tools::begins($key, EXPAND_INFIX . EXPAND_INFIX)) {
                    $_POST['fields'][$key = substr($key, strlen(EXPAND_INFIX))] = array_combine($_POST[EXPAND_INFIX . $key], $_POST[EXPAND_INFIX . EXPAND_INFIX . $key]);
                    unset($_POST['fields'][$key]['']);
                    $_POST['fields'][$key] = json_encode($_POST['fields'][$key], JSON_PRETTY_PRINT);
                    unset($_POST[$key], $_POST[EXPAND_INFIX . $key]);
                }
            }
            foreach ($this->fields as $key => $field) {
                // todo ask CRS2 - Variable $value might not be defined.
                if (Tools::set($_POST['fields-null'][$key]) || (Tools::set($field['foreign_table']) && $value === '')) {
                    $_POST['fields'][$key] = null;
                } elseif (Tools::set($_POST['fields-own'][$key])) {
                    $_POST['fields'][$key] = $_POST['fields-own'][$key];
                }
                if (!isset($_POST['fields'][$key])) {
                    continue;
                }
                $value = $_POST['fields'][$key];
                if (is_array($value) && $field['type'] == 'set') {
                    $tmp = 0;
                    foreach ($value as $i) {
                        $tmp |= $i;
                    }
                    $value = $tmp;
                }
                $value = isset($_POST['fields-null'][$key]) ? null : $value;
                $original = isset($_POST['original-null'][$key]) ? null : (isset($_POST['original'][$key]) ? $_POST['original'][$key] : false);
                if ($original === $value) {
                    continue;
                }
                switch ($field['basictype']) {
                    case 'integer':
                    case 'rational':
                    case 'choice':
                        if (Tools::among($field['key'], 'PRI', 'UNI') && $original === $value && $value === '') {
                            $value = null;
                        }
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '='
                            . (is_null($value) ? 'NULL' : ($field['basictype'] == 'integer' ? (int) $value : (double) $value));
                        break;
                    default:
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '='
                            . (is_null($value) ? 'NULL' : '"' . $this->escapeSQL($value) . '"');
                }
            }
            $command = 'UPDATE';
            // todo fix Parameter #1 $types of method GodsDev\MyCMS\MyTableLister::filterKeys() expects array, string given.
            $unique = ($this->filterKeys('PRI') ?: $this->filterKeys('UNI')) ?: array_flip(array_keys($this->fields));
            foreach (array_keys($unique) as $key) {
                $field = $this->fields[$key];
                $value = $_POST['fields'][$key];
                if ($field['key'] == 'PRI' && Tools::among($value, '', null)) {
                    $command = 'INSERT INTO';
                } else {
                    // todo ask CRS2 Variable $original might not be defined.
                    $where .= ' AND ' . (is_null($original) ? Tools::escapeDbIdentifier($key) . ' IS NULL' : ($original . '' === '' ? 'IFNULL(' . Tools::escapeDbIdentifier($key) . ', "")' : Tools::escapeDbIdentifier($key)) . ' = "' . $this->escapeSQL($_POST['original'][$key]) . '"');
                }
            }
        }
        if ($sql) {
            $sql = $command . ' ' . Tools::escapeDbIdentifier($this->table) . ' SET ' . mb_substr($sql, 1) . Tools::wrap($command == 'UPDATE' ? mb_substr($where, 5) : '', ' WHERE ') . ($command == 'UPDATE' ? ' LIMIT 1' : '');
            //@todo add message when UPDATE didn't change anything
            if ($this->resolveSQL($sql, $messageSuccess ?: $this->translate('Record saved.'), $messageError ?: $this->translate('Could not save the record.') . ' #%errno%: %error%')) {
                return true;
            } else {
                //@todo if unsuccessful, store data being saved to session
                return false;
            }
        } else {
            Tools::addMessage('info', $this->translate('Nothing to save.'));
            // todo ask CRS2  Method GodsDev\MyCMS\MyTableAdmin::recordSave() should return bool but returns int.
            return 0;
        }
    }

    /**
     * Perform the detault record delete command.
     *
     * @param bool $messageSuccess
     * @param bool $messageError
     * @return bool success
     */
    public function recordDelete($messageSuccess = false, $messageError = false)
    {
        if (!$this->authorized()) {
            return false;
        }
        $sql = [];
        if ($this->authorized() && isset($_GET['where'], $_GET['table']) && $_GET['table'] && is_array($_GET['where']) && count($_GET['where'])) {
            foreach ($_GET['where'] as $key => $value) {
                $sql [] = Tools::escapeDbIdentifier($key) . '="' . $this->escapeSQL($value) . '"';
            }
            $sql = 'DELETE FROM ' . Tools::escapeDbIdentifier($_GET['table']) . ' WHERE ' . implode(' AND ', $sql);
        }
        return $this->resolveSQL($sql, $messageSuccess ?: $this->translate('Record deleted.'), $messageError ?: $this->translate('Could not delete the record.') . '#%errno%: %error%');
    }

    /**
     * Output for the dashboard (home screen of the admin)
     *
     * @param array $options OPTIONAL
     */
    public function dashboard(array $options = [])
    {
        $this->contentByType($options);
    }
}

// @todo nekde v cyklu prevest "0" a 0 na string/integer/double podle typu?
// @todo a pak vsude === misto ==
// @todo zbavit se sahani na $_POST, predavat je jako parametr byref
