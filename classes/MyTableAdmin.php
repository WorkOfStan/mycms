<?php

namespace WorkOfStan\MyCMS;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;

/**
 * This class facilitates administration of a database table
 */
class MyTableAdmin extends MyTableLister
{
    use \Nette\SmartObject;

    /**
     * Output HTML form to edit specific row in the table
     *
     * @param scalar|array<scalar> $where to identify which row to fetch and offer for edit
     *      e.g. ['id' => 5] translates as "WHERE id=5" in SQL
     *      scalar value translates as ['id' => value]
     * @param array<bool|array<string|array<string>>> $options additional options
     *      [include-fields] - array of fields to include only
     *      [exclude-fields] - array of fields to exclude
     *      [exclude-form] - exclude the <form> element
     *      [exclude-actions] - exclude form actions (save, delete)
     *      [layout-row] - non-zero: divide labels and input elements by <br />, by default they're in <table>
     *      [prefill] - assoc. array with initial field values (only when inserting new record)
     *      [original] - keep original values (to update only changed fields)
     *      [tabs] - divide fields into Bootstrap tabs, e.g. [null, 'English'=>'/^.+_en$/i', 'Chinese'=>'/^.+_cn$/i']
     * @return string
     */
    public function outputForm($where, array $options = [])
    {
        $options['include-fields'] = isset($options['include-fields']) && is_array($options['include-fields']) ? $options['include-fields'] : array_keys($this->fields);
        $options['exclude-fields'] = isset($options['exclude-fields']) && is_array($options['exclude-fields']) ? $options['exclude-fields'] : [];
        Assert::isArray($options['exclude-fields']);
        foreach ($options['exclude-fields'] as $key => $value) {
            Assert::isArray($options['include-fields']);
            if (in_array($value, $options['include-fields'])) {
                unset($options['include-fields'][$key]);
            }
        }
        if (is_scalar($where)) {
            $where = ['id' => $where];
        }
        if ($where != ['']) {
            $sql = [];
            foreach ($where as $key => $value) {
                $sql [] = Tools::escapeDbIdentifier($key) . '="' . $this->escapeSQL((string) $value) . '"';
            }
            Assert::isArray($options['include-fields']);
            $tempOptionsIncludeFields = new ArrayStrict($options['include-fields']);
            $record = $this->dbms->query(
                'SELECT ' . $this->dbms->listColumns($tempOptionsIncludeFields->arrayString(), $this->fields)
                . ' FROM ' . Tools::escapeDbIdentifier($this->table) . ' WHERE ' . implode(' AND ', $sql) . ' LIMIT 1'
            );
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
                    if ($value && is_string($value) && preg_match($value, $k)) {
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
                    . ($tabKey === 0 ? '<span class="glyphicon glyphicon-list fa fa-list" aria-hidden="true"></span>' : Tools::h((string) $tabKey)) . '</a>' . PHP_EOL;
            }
            $output .= '</nav>' . PHP_EOL . '<div class="tab-content">';
        }
        foreach ($tabs as $tabKey => $tab) {
            $output .= (count($tabs) > 1 ? '<div class="tab-pane fade' . ($tabKey === 0 ? ' show active' : '') . '" id="tab-' . ($tmp = Tools::webalize($this->table . '-' . $tabKey)) . '" role="tabpanel" aria-labelledby="nav-' . $tmp . '">' : '')
                . ($options['layout-row'] ? '<div class="database">' : '<table class="database">');
            foreach ($tab as $key => $field) {
                Assert::isArray($options['include-fields']);
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
            $output .= '<label><i class="fa fa-location-arrow"></i> ' . Tools::htmlSelect(
                'after',
                [$this->translate('stay here'), $this->translate('go back')],
                false,
                ['class' => 'form-control form-control-sm w-initial d-inline-block']
            ) . '</label></div>';
        }
        return $output . (isset($options['exclude-form']) && $options['exclude-form'] ? '' : '</fieldset></form>') . PHP_EOL;
    }

    /**
     * Return value for
     * Output appropriate HTML input item for given field.
     *
     * @param array<string|bool|null> $field
     * @param string $key
     * @param array<string> $record
     * @param array<mixed> $options
     * @return bool|float|int|string
     */
    private function outputFieldValue(array $field, $key, array $record, array $options)
    {
        $value = isset($record[$key]) ? $record[$key] : false;
        if (Tools::among($record, false, [])) {
            if (is_array($options['prefill']) && isset($options['prefill'][$key]) && is_scalar($options['prefill'][$key])) {
                $value = $options['prefill'][$key];
                if (Tools::among($field['type'], 'datetime', 'timestamp') && $options['prefill'][$key] == 'now') {
                    return date('Y-m-d\TH:i:s');
                }
            } elseif ($field['default']) {
                return $field['default'];
            }
        } elseif (Tools::among($field['type'], 'datetime', 'timestamp') && Tools::among($value, '0000-00-00', '0000-00-00 00:00:00', '0000-00-00T00:00:00')) {
            return '';
        }
        return $value;
    }

    /**
     * Output appropriate HTML input item for given field.
     *
     * @param array<string|bool|null> $field
     * @param string $key
     * @param array<string> $record
     * @param array<mixed> $options
     * @return string
     */
    protected function outputField(array $field, $key, array $record, array $options)
    {
        $value = $this->outputFieldValue($field, $key, $record, $options);
        /* isset($record[$key]) ? $record[$key] : false;
        if (Tools::among($record, false, [])) {
            if (is_array($options['prefill']) && isset($options['prefill'][$key]) && is_scalar($options['prefill'][$key])) {
                $value = $options['prefill'][$key];
                if (Tools::among($field['type'], 'datetime', 'timestamp') && $options['prefill'][$key] == 'now') {
                    $value = date('Y-m-d\TH:i:s');
                }
            } elseif ($field['default']) {
                $value = $field['default'];
            }
        } elseif (Tools::among($field['type'], 'datetime', 'timestamp') && Tools::among($value, '0000-00-00', '0000-00-00 00:00:00', '0000-00-00T00:00:00')) {
            $value = '';
        } */
        $output = (Tools::set($options['layout-row'], false) ? '' : '<tr><td>')
            . '<label for="' . Tools::h($key) . $this->rand . '">' . $this->translateColumn($key) . ':</label>'
            . ($options['layout-row'] ? ' ' : '</td><td>')
            . Tools::htmlInput(
                ($field['type'] == 'enum' ? $key : "fields-null[$key]"),
                ($field['type'] == 'enum' && $field['null'] ? 'null' : ''),
                1,
                [
                    // TODO fix NULL option for ENUM to be saved in database
                    'type' => ($field['type'] == 'enum' ? 'radio' : 'checkbox'), // TODO why enum should use radio??
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
        $comment = json_decode(isset($field['comment']) ? (string) $field['comment'] : '{}', true);
        if (is_array($comment)) {
            $comment['display'] = isset($comment['display']) ? $comment['display'] : null;
        } else {
            $comment = ['display' => null];
        }
        if (!is_null($field['type']) && $comment['display'] == 'option') {
            $query = $this->dbms->queryStrictObject('SELECT DISTINCT ' . Tools::escapeDbIdentifier($key)
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
            if ($value === false) {
                // non-present value should be displayed as JSON field to be populated
                $value = '[]';
            }
            Assert::string($value);
            $json = (array) json_decode($value, true) ?: (Tools::among($value, '', '[]', '{}') ? [] : $value);
            $output .= '<div class="input-expanded">' . Tools::htmlInput($key . EXPAND_INFIX, '', 1, 'hidden');
            if (!is_array($json) && isset($comment['subfields']) && is_array($comment['subfields'])) {
                // set null to all the fields named after $comment['subfields'] values (TODO refactor?)
                foreach ($comment['subfields'] as $v) {
                    // initiates $json as an array only once and only in case when $comment['subfields'] isn't empty
                    if (!is_array($json)) {
                        $json = [];
                    }
                    $json[$v] = isset($json[$v]) ? $json[$v] : null; // TODO, maybe `$json[$v] = null;` suffices
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
                Assert::string($field['type']);
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
            Assert::isArray($input);
            Assert::keyExists($input, 'id');
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
                Assert::isArray($input);
                $input['type'] = 'number';
                $input['step'] = 1;
                $input['class'] = 'form-control';
                if ($field['key'] == 'PRI') {
                    $input['readonly'] = 'readonly';
                    $input = '<div class="input-group">' . Tools::htmlInput("fields[$key]", '', $value, $input)
                        . '<span class="input-group-btn"><button class="btn btn-secondary btn-id-unlock" type="button" title="' . $this->translate('Unlock') . '"><i class="glyphicon glyphicon-lock fa fa-lock" aria-hidden="true"></i></button></span></div>';
                }
                break;
            case 'date':
                Assert::isArray($input);
                $input['class'] = 'form-control input-date';
                break;
            case 'time':
                Assert::isArray($input);
                $input['step'] = 1;
                $input['class'] = 'form-control input-time';
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $value = (float) $value;
                Assert::isArray($input);
                $input['class'] = 'form-control text-right';
                break;
            case 'datetime':
            case 'timestamp':
                // changes '2021-03-12 22:11:59' to '2021-03-12T22:11:59' // TODO but why?
                if (is_string($value) && strlen($value) >= 10 && substr($value, 10, 1) === ' ') {
                    $value = substr($value, 0, 10) . 'T' . substr($value, 11);
                }
                Assert::isArray($input);
                $input['type'] = 'datetime-local';
                $input['step'] = 1;
                $input['class'] = 'form-control input-datetime';
                $input = '<div class="input-group">' . Tools::htmlInput("fields[$key]", '', $value, $input)
                    . '<span class="input-group-btn"><button class="btn btn-secondary btn-fill-now" type="button" title="' . $this->translate('Now') . '"><i class="glyphicon glyphicon-time fa fa-clock-o fa-clock" aria-hidden="true"></i></button></span></div>';
                break;
            case 'bit':
                Assert::isArray($input);
                $input['type'] = 'checkbox';
                $input['step'] = 1;
                $input['checked'] = ($value ? 'checked' : null);
                break;
            case 'enum':
                Assert::string($field['size']); // TODO explore if type casting to string shouldn't be rather used
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
                // Note: ENUM doesn't accept zero value. To set empty, use NULL option.
//                $input = array_merge(
//                    [Tools::htmlInput('fields[' . $key . ']', $this->translate('empty') . ' ', 0, [
//                            'type' => 'radio',
//                            'id' => "fields[$key-0]",
//                            'value' => 0,
//                            'label-class' => 'font-weight-normal'
//                        ])],
//                    $input
//                );
                $input = ($options['layout-row'] ? '<br>' : '') . implode(', ', $input) . '<br>';
                break;
            case 'set':
                Assert::string($field['size']);
                $choices = $this->dbms->decodeSetOptions($field['size']);
                $tmp = [];
                Assert::string($value);
                $value = explode(',', $value);
                foreach ($choices as $k => $v) {
                    $tmp[$k] = Tools::htmlInput(
                        "fields[$key][$k]",
                        $v === '' ? '<i>' . $this->translate('nothing') . '</i>' : $v,
                        1 << $k,
                        [
                            'type' => 'checkbox',
                            'checked' => (
                                (1 << $k) & (int) (
                                    //as $value seems to always be array, the former code
                                    //`is_array($value) ? reset($value) : $value` seems redundant
                                    reset($value)
                                )
                            ) ? 'checked' : null,
                            'id' => "$key-$k-$this->rand",
                            'label-html' => $v === '',
                            'label-class' => 'font-weight-normal'
                        ]
                    );
                }
                $input = implode(', ', $tmp) . '<br>';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'blob':
            case 'longblob':
            case 'binary':
                Assert::string($value);
                $input = preg_match('~(^\pC)*~i', $value) ? '<tt>' . Tools::ifempty(Tools::shortify($value, 100), '<i class="insipid">' . $this->translate('empty') . '</i>') . '</tt><br />' : //@todo constant --> parameter
                    '<a href="#" class="download-blob d-block" data-table="' . urlencode($this->table) . '" data-column="' . urlencode($key) . '" '
                    . 'target="_blank" >' . $this->translate('Download') . '</a>' . PHP_EOL;
                break;
            case null:
                break;
            default:
                if (Tools::among($field['type'], 'char', 'varchar') && ($field['size'] < 256 || Tools::set($comment['edit'], false) == 'input') && Tools::set($comment['edit'], false) != 'textarea') {
                    break;
                }
                Assert::string($field['type']);
                $input = '<div class="TableAdminTextarea">'
                    . Tools::htmlTextarea(
                        "fields[$key]",
                        (string) $value, // false is casted to ''
                        60,
                        5,
                        ['id' => $key . $this->rand, 'data-maxlength' => $field['size'],
                            'class' => 'form-control type-' . Tools::webalize($field['type']) . ($comment['display'] == 'html' ? ' richtext' : '') . ($comment['display'] == 'texyla' ? ' texyla' : '')
                        ]
                    )
                    . '<i class="fab fa-stack-overflow input-limit" aria-hidden="true" data-fields="' . Tools::h($key) . '"></i></div>';
        }
        if (is_array($input)) {
            $input = Tools::htmlInput("fields[$key]", '', $value, $input);
        }
        if (isset($options['original']) && $options['original']) {
            /**
             * @phpstan-ignore-next-line
             * TODO fix Call to function is_null() with (array<int, string>&nonEmpty)|bool|float|int|string will always evaluate to false.
             */
            if (is_null($value)) {
                $input .= Tools::htmlInput("original-null[$key]", '', 1, 'hidden');
            } else {
                Assert::isArray($options);
                $input .= Tools::htmlInput("original[$key]", '', isset($options['prefill'][$key]) && is_scalar($options['prefill'][$key]) ? '' : $value, 'hidden');
            }
        }
        return $output . $input . $this->customInputAfter($key, $value, $record)
            . ($options['layout-row'] ? '' : '</td></tr>') . PHP_EOL;
    }

    /**
     * Calculate module for
     * Output HTML select for picking a path (project-specific)
     * TODO: What is the point here? This method wasn't used neither in A nor in F project.
     *
     * @param array<string> $name of the table (without prefix) and main column
     * @return int
     */
    private function outputSelectPathModule(array $name)
    {
        $module = $this->dbms->query(
            'SHOW FULL COLUMNS FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name['table'])
            . ' WHERE FIELD="' . $this->escapeSQL($name['column']) . '"'
        );
        if ($module && $module !== true) {
            $tempArr = $module->fetch_assoc();
            Assert::isArray($tempArr);
            $module = json_decode($tempArr['Comment'], true);
            Assert::isArray($module);
            return isset($module['module']) && $module['module'] ? $module['module'] : 10;
        } //else {
        return 10;
    }

    /**
     * Output HTML select for picking a path (project-specific)
     * TODO: What is the point here? This method wasn't used neither in A nor in F project.
     *
     * @param string|array<string> $name of the table (without prefix) and main column
     * @param int $path_id reference to the path
     * @param array<mixed> $options
     * @return string HTML <select>
     */
    public function outputSelectPath($name, $path_id = null, $options = [])
    {
        // TODO what does this construction mean? Call to function is_array() with string will always evaluate to false.
        // TODO: compare to 'typeToTableMapping' mechanism
        if (!is_array($name)) {
            $name = ['table' => $name, 'column' => $name];
        }
        $module = $this->outputSelectPathModule($name);
        /* $this->dbms->query(
            'SHOW FULL COLUMNS FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name['table'])
            . ' WHERE FIELD="' . $this->escapeSQL($name['column']) . '"'
        );
        if ($module && $module !== true) {
            $tempArr = $module->fetch_assoc();
            Assert::isArray($tempArr);
            $module = json_decode($tempArr['Comment'], true);
            Assert::isArray($module);
            $module = isset($module['module']) && $module['module'] ? $module['module'] : 10;
        } else {
            $module = 10;
        }*/
        $result = '<select name="' . MyTools::h(isset($options['name']) ? $options['name'] : 'path_id')
            . '" class="' . MyTools::h(isset($options['class']) ? $options['class'] : '')
            . '" id="' . MyTools::h(isset($options['id']) ? $options['id'] : '') . '">'
            . Tools::htmlOption('', $this->translate('--choose--'));
        $query = $this->dbms->queryStrictObject('SELECT id,path,' . Tools::escapeDbIdentifier($name['column']) . ' AS category_
            FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $name['table']) . ' ORDER BY path');
        if (empty($query)) { // for empty result
            return $result . '</select>';
        }
        $options['exclude'] = isset($options['exclude']) ? $options['exclude'] : [];
        $options['path-value'] = isset($options['path-value']) ? $options['path-value'] : false;
        while ($row = $query->fetch_assoc()) {
            if ($row['id'] != $options['exclude']) {
                $result .= Tools::htmlOption($row['id'], str_repeat('. ', strlen($row['path']) / $module - 1) . $row['category_'], $row['path'] === $options['path-value'] ? $row['id'] : $path_id);
            }
        }
        return $result . '</select>';
    }

    /**
     *
     * @param mixed $value
     * @param string $text
     * @param string $group
     * @param string|false $lastGroup
     * @param mixed $default
     * @param array<array|int|string> $options
     * @return string HTML code
     */
    protected function addForeignOption($value, $text, $group, &$lastGroup, $default, $options)
    {
        $result = '';
        if ($lastGroup != $group) {
            $result .= ($lastGroup === false ? '' : '</optgroup>')
                . '<optgroup label="' . Tools::h($lastGroup = $group) . '" />';
        }
        Assert::isArray($options['exclude']);
        if (!in_array($value, $options['exclude'])) {
            $result .= Tools::htmlOption($value, $text, $default);
        }
        return $result;
    }

    /**
     * Output HTML <select name=$field> with $values as its items
     *
     * @param string $field name of the select element
     * @param string|array<string|array> $values either array of values for the <select>
     *        or string with the SQL SELECT statement
     * @param scalar $default original value
     * @param array<array|int|string> $options additional options for the element rendition; plus
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
        Assert::string($options['class']);
        Assert::string($options['id']);
        $result = '<select name="' . Tools::h($field)
            . '" class="' . Tools::h(isset($options['class']) ? $options['class'] : '')
            . '" id="' . Tools::h(isset($options['id']) ? $options['id'] : '') . '">'
            . '<option value=""></option>';
        $options['exclude'] = isset($options['exclude']) ? (is_array($options['exclude']) ?
            $options['exclude'] : [$options['exclude']]) : [];
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
            // TODO if there are troubles with queryStrict go back to `if ($query = $this->dbms->query($values))` syntax
            $query = $this->dbms->queryStrictObject($values);
            while ($row = $query->fetch_row()) {
                $result .= $this->addForeignOption(
                    $row[0],
                    $row[1],
                    isset($row[2]) ? $row[2] : false,
                    $lastGroup,
                    $default,
                    $options
                );
            }
        }
        return $result . ($lastGroup === false ? '' : '</optgroup>') . '</select>';
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
     * @return bool|int
     *     true = record saved sucessfully,
     *     false = error occured saving the record,
     *     0 = no records to save (e.g. in case no checkboxes checked in a form)
     *
     * TODO: pro případ, kdy SQL proběhlo v pořádku,
     * ale nic nebylo změněno (tj. byla správně uložena data tak, jak již byla v databázi).
     * tj.přidat do recordSave()
     * 3. parametr - &$affectedRows (s nějakou defaultní hodnotou, null třeba),
     * do které se uloží ::$affected_rows (od třídy mysqli nebo potomka) v případě, že dojde až na vykonávání příkazu.
     *
     */
    public function recordSave($messageSuccess = false, $messageError = false)
    {
        if (!$this->authorized()) {
            return false;
        }
        $sql = $where = '';
        if (is_array($this->fields) && count($this->fields) > 0) { // $this->fields should be an array with at least one element
            foreach ($_POST as $key => $value) {
                if (Tools::begins($key, EXPAND_INFIX) && !Tools::begins($key, EXPAND_INFIX . EXPAND_INFIX)) {
                    $_POST['fields'][$key = substr($key, strlen(EXPAND_INFIX))] = array_combine($_POST[EXPAND_INFIX . $key], $_POST[EXPAND_INFIX . EXPAND_INFIX . $key]);
                    unset($_POST['fields'][$key]['']);
                    $_POST['fields'][$key] = json_encode($_POST['fields'][$key], JSON_PRETTY_PRINT);
                    unset($_POST[$key], $_POST[EXPAND_INFIX . $key]);
                }
            }
            $original = null;
            foreach ($this->fields as $key => $field) {
                /**
                 * @phpstan-ignore-next-line
                 * TODO fix Result of && is always false.
                 * TODO Strict comparison using === between array and '' will always evaluate to false.
                 */
                if (Tools::set($_POST['fields-null'][$key]) || (Tools::set($field['foreign_table']) && $field === '')) {
                    $_POST['fields'][$key] = null;
                } elseif (Tools::set($_POST['fields-own'][$key])) {
                    $_POST['fields'][$key] = $_POST['fields-own'][$key];
                }
                if (!array_key_exists($key, $_POST['fields'])) { // !isset would trigger continue for null value
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
                            . (is_null($value) ? 'NULL' : ($field['basictype'] == 'integer' ? (int) $value : (float) $value));
                        break;
                    default:
                        $sql .= ',' . Tools::escapeDbIdentifier($key) . '='
                            . (is_null($value) ? 'NULL' : '"' . $this->escapeSQL($value) . '"');
                }
            }
            $command = 'UPDATE';
            $unique = ($this->filterKeys(['PRI']) ?: $this->filterKeys(['UNI'])) ?: array_flip(array_keys($this->fields));
            foreach (array_keys($unique) as $key) {
                $field = $this->fields[$key];
                $value = $_POST['fields'][$key];
                if ($field['key'] == 'PRI' && Tools::among($value, '', null)) {
                    $command = 'INSERT INTO';
                } else {
                    $where .= ' AND ' . (is_null($original) ? Tools::escapeDbIdentifier($key) . ' IS NULL' : ($original . '' === '' ? 'IFNULL(' . Tools::escapeDbIdentifier($key) . ', "")' : Tools::escapeDbIdentifier($key)) . ' = "' . $this->escapeSQL($_POST['original'][$key]) . '"');
                }
            }
        }
        if ($sql && isset($command)) {
            return $this->resolveSQL(
                $command . ' ' . $this->dbms->escapeDbIdentifier($this->table) . ' SET ' . mb_substr($sql, 1)
                . Tools::wrap($command == 'UPDATE' ? mb_substr($where, 5) : '', ' WHERE ')
                . ($command == 'UPDATE' ? ' LIMIT 1' : ''),
                $this->translate('Record saved.'),
                $this->translate('Could not save the record.') . ' #%errno%: %error%'
            );
        } else {
            Tools::addMessage('info', $this->translate('Nothing to save.'));
            return 0; // no records to save (e.g. in case no checkboxes checked in a form)
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
        if ($this->authorized() && isset($_GET['where'], $_GET['table']) && $_GET['table'] && is_array($_GET['where']) && count($_GET['where'])) {
            $sql = [];
            foreach ($_GET['where'] as $key => $value) {
                $sql [] = Tools::escapeDbIdentifier($key) . '="' . $this->escapeSQL($value) . '"';
            }
            return $this->resolveSQL(
                'DELETE FROM ' . Tools::escapeDbIdentifier($_GET['table']) . ' WHERE ' . implode(' AND ', $sql),
                $this->translate('Record deleted.'),
                $this->translate('Could not delete the record.') . '#%errno%: %error%'
            );
        }
        return false;
    }

    /**
     * Output for the dashboard (home screen of the admin)
     *
     * @param array<string> $options OPTIONAL
     * @return void
     */
    public function dashboard(array $options = [])
    {
        echo $this->contentByType($options);
    }
}

// @todo nekde v cyklu prevest "0" a 0 na string/integer/double podle typu?
// @todo a pak vsude === misto ==
// @todo zbavit se sahani na $_POST, predavat je jako parametr byref
