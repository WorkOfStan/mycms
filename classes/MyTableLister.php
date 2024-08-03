<?php

namespace WorkOfStan\MyCMS;

use Exception;
use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\L10n;

/**
 * Class that can list rows of a database table, with editable search/filter
 * functionality, links to edit each particular row, multi-row action, etc.
 * dependencies: GodsDev\Tools, MySQL/MariaDB (it uses INFORMATION_SCHEMA)
 */
class MyTableLister
{
    use \Nette\SmartObject;

    /** @var string current database */
    protected $database;
    /** @var LogMysqli database management system */
    protected $dbms;
    /** @var array<int|string> factory setting defaults */
    protected $DEFAULTS = [
        'PAGESIZE' => 100,
        'MAXPAGESIZE' => 10000,
        'MAXSELECTSIZE' => 10000,
        'TEXTSIZE' => 100,
        'PAGES_AROUND' => 2, // used in pagination
        'FOREIGNLINK' => '-link' //suffix added to POST variables for links
    ];
    /** @var array<array> all fields in the table */
    public $fields;
    /** @var array<array<mixed>|string> TODO is $_GET really just recursive array with string values??? */
    protected $get;
    /** @var L10n */
    protected $localisation;
    /** @var array<string|array<string>> display options */
    protected $options;
    /**
     * Folder and name prefix of localisation yml
     *
     * @var string
     */
    protected $prefixL10n;
    /** @var int random item used in HTML */
    public $rand;
    /** @var string JavaScript code gathered to show the listing */
    public $script;
    /** @var string table to list */
    protected $table;
    /** @var array<array> possible table settings, stored in its comment */
    public $tableContext = null;
    /** @var array<array> all tables in the database */
    public $tables;
    /**
     * @var array<string> Selected locale strings
     * DEPRECATED keep this declaration just for backward compatibility
     */
    public $TRANSLATION = [];
    /** @var array<string> Available languages for MyCMS */
    public $TRANSLATIONS = [];
    /** @var array<string> arithmetical and logical operations for searching */
    public $WHERE_OPS = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '!=',
        'LIKE',
        'LIKE %%',
        'REGEXP',
        'IN',
        'FIND_IN_SET',
        'IS NULL',
        'BIT',
        'NOT LIKE',
        'NOT REGEXP',
        'NOT IN',
        'IS NOT NULL',
        'NOT BIT'
    ];

    /**
     * Constructor - stores passed parameters to object's attributes
     *
     * @param LogMysqli $dbms database management system already connected to wanted database
     * @param string $table to view
     * @param array<string|array<string>> $options display options
     */
    public function __construct(LogMysqli $dbms, $table, array $options = [])
    {
        $this->dbms = $dbms;
        $this->options = $options;
        $this->database = $this->dbms->fetchSingleString('SELECT DATABASE()');
        $this->getTables();
        $this->setTable($table);
        $this->rand = rand((int) 1e5, (int) (1e6 - 1));
        if (array_key_exists('prefixL10n', $options)) { // the condition is due to a deprecated backward compatibility
            Assert::string($options['prefixL10n']); // only this line is relevant
        } else {
            $options['prefixL10n'] = '';
        }
        Assert::isArray($options['TRANSLATIONS']);
        $this->TRANSLATIONS = $options['TRANSLATIONS'];
        $this->localisation = new L10n($options['prefixL10n'], $this->TRANSLATIONS);
        Assert::string($options['language']);
        $this->localisation->loadLocalisation($options['language']);
        $this->get = $_GET; // TODO inject GET in _construct arguments
        DEBUG_VERBOSE && Debugger::barDump($this->get, '$_GET');
    }

    /**
     * Get all tables in the database (including comments) and store them to tables
     *
     * @return void
     * @throws Exception on SQL statement not returning array
     */
    public function getTables()
    {
        $this->tables = [];
        $query = $this->dbms->queryStrictObject('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = "' . $this->escapeSQL($this->database) . '"');
        while ($row = $query->fetch_row()) {
            if ($row[0] === TAB_PREFIX . 'admin') {
                continue; // admin table (or its rows) MUST NOT be accessed through admin.php
            }
            $this->tables[$row[0]] = $row[1];
        }
    }

    /**
     * Set (or change) serviced table, get its fields.
     *
     * @param string $table table name
     * @param bool $forceReload do force reload if the current and desired table names are the same
     * @return void
     * @throws \RunTimeException
     */
    public function setTable($table, $forceReload = false)
    {
        if ($this->table && $this->table == $table && !$forceReload) {
            return;
        }
        $this->fields = [];
        if (!in_array($table, array_keys($this->tables))) {
            return;
        }
        $this->table = $table;
        try {
            $query = $this->dbms->queryStrictObject('SHOW FULL COLUMNS IN ' . $this->escapeDbIdentifier($this->table));
        } catch (Exception $e) {
            throw new \RunTimeException('Could not get columns from table ' . $this->table . '.');
        }
        $result = [];
        while ($row = $query->fetch_assoc()) {
            $item = [
                'type' => $row['Type'],
                'null' => strtoupper($row['Null']) == 'YES',
                'default' => $row['Default'],
                'size' => null,
                'key' => $row['Key'],
                'comment' => $row['Comment'],
                'basictype' => $row['Type']
            ];
            if ($pos = strpos($row['Type'], '(')) {
                $item['basictype'] = $item['type'] = substr($row['Type'], 0, $pos);
                $item['size'] = rtrim(substr($row['Type'], $pos + 1), ')');
            }
            switch ($item['basictype']) {
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                case 'year':
                case 'bit':
                    $item['basictype'] = 'integer';
                    break;
                case 'float':
                case 'double':
                case 'decimal':
                    $item['basictype'] = 'rational';
                    break;
                case 'enum':
                case 'set':
                    $item['basictype'] = 'choice';
                    break;
                case 'binary':
                case 'varbinary':
                case 'tinyblob':
                case 'mediumblob':
                case 'longblob':
                    $item['basictype'] = 'binary';
                    break;
                default:
                    // this includes date/time, geometry and other scarcely used types
                    $item['basictype'] = 'text';
            }
            $result[$row['Field']] = $item;
        }
        $this->fields = $result;
        $query2 = $this->dbms->queryStrictObject(
            'SELECT COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME '
            . 'FROM information_schema.KEY_COLUMN_USAGE '
            . 'WHERE CONSTRAINT_NAME != "PRIMARY" AND CONSTRAINT_CATALOG = "def" AND TABLE_SCHEMA = "'
            . $this->escapeSQL($this->database) . '" AND TABLE_NAME = "' . $this->escapeSQL($this->table) . '"'
        );
        while ($row = $query2->fetch_assoc()) {
            $this->fields[$row['COLUMN_NAME']]['foreign_table'] = $row['REFERENCED_TABLE_NAME'];
            $this->fields[$row['COLUMN_NAME']]['foreign_column'] = $row['REFERENCED_COLUMN_NAME'];
        }
        $tmp = $this->dbms->fetchSingle('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA="'
            . $this->escapeSQL($this->database) . '" AND TABLE_NAME="' . $this->escapeSQL($this->table) . '"');
        Assert::string($tmp);
        $tempJsonDecode = json_decode($tmp, true);
        $this->tableContext = is_array($tempJsonDecode) ? $tempJsonDecode : [];
    }

    /**
     * Compose a SELECT SQL statement with given columns and _GET variables to display within Admin UI
     *
     * @param array<string> $columns
     * @param array<mixed> $vars &$vars variables used to filter records
     * @return array<string|int> with these indexes: [join], [where], [sort], [sql], int[limit]
     * @throws \InvalidArgumentException
     */
    public function selectSQL($columns, &$vars)
    {
        $result = [
            'join' => '',
            'where' => '',
            'order by' => '',
            'sql' => ''
        ];
        if (isset($vars['limit'])) {
            Assert::scalar($vars['limit']); // number passed as string or int TODO make sure it's just one of those
            $result['limit'] = $vars['limit'] ? (int) $vars['limit'] : $this->DEFAULTS['PAGESIZE'];
        } else {
            $result['limit'] = $this->DEFAULTS['PAGESIZE'];
        }
        if ($result['limit'] < 1 || $result['limit'] > $this->DEFAULTS['MAXPAGESIZE']) {
            $result['limit'] = $this->DEFAULTS['PAGESIZE'];
        }
        if (isset($vars['offset'])) {
            Assert::scalar($vars['offset']); // number passed as string or int TODO make sure it's just one of those
            $result['offset'] = max((int) $vars['offset'], 0);
        } else {
            $result['offset'] = 0;
        }
        foreach ($columns as $key => $value) {
            if (isset($this->fields[$key]['foreign_table']) && $this->fields[$key]['foreign_table']) {
                $result['join'] .= ' LEFT JOIN ' . $this->fields[$key]['foreign_table']
                    . ' ON ' . $this->escapeDbIdentifier($this->table) . '.' . $this->escapeDbIdentifier($key)
                    . '=' . $this->escapeDbIdentifier($this->fields[$key]['foreign_table']) . '.'
                    . $this->escapeDbIdentifier($this->fields[$key]['foreign_column']);
                // try if column of the same name as the table exists (as a replacement for foreign table);
                // use the first field in the table if it doesn't exist
                $tmp = $this->dbms->queryStrictObject(
                    'SHOW FIELDS FROM ' . $this->escapeDbIdentifier($this->fields[$key]['foreign_table'])
                )->fetch_all();
                foreach ($tmp as $k => $v) {
                    $tmp[$v[0]] = $v[0];
                    unset($tmp[$k]);
                }
                $foreign_link = mb_substr($this->fields[$key]['foreign_table'], mb_strlen(TAB_PREFIX));
                $foreign_link = isset($tmp[$foreign_link]) && $foreign_link ? $foreign_link : reset($tmp);
                $columns[$key] = $this->escapeDbIdentifier($this->table) . '.' . $value . ','
                    . $this->escapeDbIdentifier($this->fields[$key]['foreign_table']) . '.'
                    . $this->escapeDbIdentifier($foreign_link) . ' AS '
                    . $this->escapeDbIdentifier($key . $this->DEFAULTS['FOREIGNLINK']);
            }
        }
        if ($result['join']) {
            foreach ($columns as $key => $value) {
                if ($value == $this->escapeDbIdentifier($key)) {
                    $columns[$key] = $this->escapeDbIdentifier($this->table) . '.' . $value;
                }
            }
        }
        if (isset($vars['col']) && is_array($vars['col'])) {
            $filterColumn = [''];
            foreach ($columns as $key => $value) {
                $filterColumn [] = $key;
            }
            unset($filterColumn[0]);
            foreach ($vars['col'] as $key => $value) {
                Assert::isArray($vars['val']);
                if (isset($filterColumn[$value], $vars['val'][$key])) {
                    $id = $this->escapeDbIdentifier($this->table) . '.'
                        . $this->escapeDbIdentifier((string) $filterColumn[$value]);
                    Assert::string($vars['val'][$key]); // escape $val below expect string
                    $val = $vars['val'][$key];
                    Assert::isArray($vars['op']);
                    $op = $this->WHERE_OPS[$vars['op'][$key]];
                    $result['where'] .= ' AND ';
                    if (substr($op, 0, 4) == 'NOT ') {
                        $result['where'] .= 'NOT ';
                        $op = substr($op, 4);
                    }
                    switch ($op) {
                        case 'LIKE %%':
                            $result['where'] .= $id . ' LIKE "%' . $this->escapeSQL($val) . '%"';
                            break;
                        case 'IN':
                            $result['where'] .= $id . ' IN (' . Tools::escapeIn($val) . ')';
                            break;
                        case 'IS NULL':
                            $result['where'] .= $id . ' IS NULL';
                            break;
                        case 'IS NOT NULL':
                            $result['where'] .= $id . ' IS NOT NULL';
                            break;
                        case 'BIT':
                            $result['where'] .= $id . ' & ' . intval($val) . ' != 0';
                            break;
                        case 'IN SET':
                            $result['where'] .= 'FIND_IN_SET("' . $this->escapeSQL($val) . '", ' . $id . ')';
                            break;
                        default:
                            $result['where'] .= $id . $op . '"' . $this->escapeSQL($val) . '"';
                    }
                    unset($id, $val, $op);
                }
            }
        }
        $tempArr = Tools::setifempty($vars['sort'], []);
        Assert::isIterable($tempArr);
        foreach ($tempArr as $key => $value) {
            Assert::string($value); // number represented as string
            if (isset(array_keys($columns)[(int) $value - 1])) {
                if (!array_key_exists('desc', $vars) || !is_array($vars['desc'])) {
                    $vars['desc'] = [];
                }
                // fix foreign-link columns for sorting
                $columnIdentifier = array_values($columns)[(int) $value - 1];
                if (strpos($columnIdentifier, ' AS ') !== false) {
                    $tempColumnIdentifier = explode(' AS ', $columnIdentifier);
                    $columnIdentifier = $tempColumnIdentifier[1];
                }
                $result['order by'] .= ', ' . $columnIdentifier
                    . (isset($vars['desc'][$key]) && $vars['desc'][$key] ? ' DESC' : '');
            }
        }
        $result['select'] = 'SELECT SQL_CALC_FOUND_ROWS ' . implode(',', $columns) . PHP_EOL . 'FROM '
            . $this->escapeDbIdentifier($this->table) . $result['join'] . PHP_EOL
            . Tools::wrap(substr($result['where'], 4), PHP_EOL . 'WHERE ')
            . Tools::wrap(substr($result['order by'], 1), PHP_EOL . 'ORDER BY ');
        $result['sql'] = $result['select'] . PHP_EOL . 'LIMIT ' . $result['offset'] . ', ' . $result['limit'];
        DEBUG_VERBOSE && Debugger::barDump($result['sql'], 'selectSQL result[sql]');
        return $result;
    }

    /**
     * Compose a part of an UPDATE SQL statement for selected records with given columns and _GET variables
     * Method bulkUpdateSQL() creates part of SQL statement UPDATE for bulk editing of columns.
     * Input is an array, where each column can have an operation and an operand (e.g. add or substract from a column).
     * Operation `original` means "leave the column as is" (i.e. don't use it in this SQL statement)
     * And for any other (=unknown) operation is the column ignored, i.e. is not used in this SQL statement.
     *
     * @param array<string,array> $vars variables used to filter records
     * @return string
     */
    public function bulkUpdateSQL($vars)
    {
        $result = '';
        foreach ($vars['fields'] as $field => $value) {
            switch ((isset($vars['op'][$field]) && $vars['op'][$field] ? $vars['op'][$field] : false)) {
                case 'value':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = "' . $this->escapeSQL($value) . '"';
                    break;
                case '+':
                case '-':
                case '*':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $this->escapeDbIdentifier($field)
                    . $vars['op'][$field] . ' ' . ($vars['op'][$field] == '*' ? (float) $value : (int) $value);
                    break;
                case 'random':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = RAND() * '
                        . ($value == 0 ? 1 : (float) $value);
                    break;
                case 'now':
                case 'uuid':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $vars['op'][$field] . '()';
                    break;
                case 'append':
                    $result .= ', CONCAT(' . $this->escapeDbIdentifier($field) . ', "' . $this->escapeSQL($value)
                        . '")';
                    break;
                case 'prepend':
                    $result .= ', CONCAT("' . $this->escapeSQL($value) . '", ' . $this->escapeDbIdentifier($field)
                        . ')';
                    break;
                case 'addtime':
                case 'subtime':
                    $result .= ', ' . $vars['op'][$field] . '(' . $this->escapeDbIdentifier($field) . ', "'
                        . $this->escapeSQL($value) . '")';
                    break;
//                case 'original':
//                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $this->escapeDbIdentifier($field);
//                    break;
                default:
                    error_log('bulkUpdateSQL unknown operator ' .
                        (string) (isset($vars['op'][$field]) && $vars['op'][$field] ? $vars['op'][$field] : false));
                    break;
            }
        }
        // TODO: explore how and where this method is used. Seems unused.
        return $result;
    }

    /**
     * Create array of columns for preparing the SQL statement
     *
     * @param array<array> $options
     * @return array<string>
     */
    public function getColumns($options)
    {
        $columns = [];
        if (isset($options['include']) && is_array($options['include'])) {
            foreach ($options['include'] as $column) {
                if (in_array($column, array_keys($this->fields))) {
                    $columns[$column] = $this->escapeDbIdentifier($column);
                }
            }
        }
        if (!$columns) {
            foreach ($this->fields as $key => $value) {
                $columns[$key] = $this->escapeDbIdentifier($key);
            }
        }
        if (isset($options['exclude']) && is_array($options['exclude'])) {
            foreach ($options['exclude'] as $column) {
                if (isset($columns[$column])) {
                    unset($columns[$column]);
                }
            }
        }
        return $columns;
    }

    /**
     * Output a customizable table to browse, search, page and pick its items for editing
     *
     * @param array<mixed> $options configuration array
     *   $options['form-action']=send.php - instead of <form action="">
     *   $options['read-only']=non-zero - no links to admin
     *   $options['no-sort']=non-zero - don't offer 'sorting' option
     *   $options['no-search']=non-zero - don't offer 'search' option
     *   $options['no-display-options']=non-zero - don't offer 'display' option
     *   $options['no-multi-options']=non-zero - disallow to change values via so called quick column
     *   $options['no-selected-rows-operations'] - disallow to change selected rows in bulk
     *   $options['include']=array - columns to include
     *   $options['exclude']=array - columns to exclude
     *   $options['columns']=array - special treatment of columns
     * @return string
     */
    public function view(array $options = [])
    {
        foreach (
            [
                'read-only',
                'no-search',
                'no-sort',
                'no-toggle',
                'no-display-options',
                'no-multi-options',
                'no-selected-rows-operations'
            ] as $i
        ) {
            Tools::setifempty($options[$i]);
        }
        // find out what columns to include/exclude
        $tempGetColumnsOptions = [];
        foreach (['include', 'exclude'] as $fieldName) {
            if (array_key_exists($fieldName, $options)) {
                $tempGetColumnsOptions[$fieldName] = (array) $options[$fieldName];
            }
        }
        if (!($columns = $this->getColumns($tempGetColumnsOptions))) {
            return '';
        }
        $sql = $this->selectSQL($columns, $this->get);
        Assert::string($sql['sql']);
        $query = $this->dbms->queryStrictObject($sql['sql']);
        $options['total-rows'] = $this->dbms->fetchSingle('SELECT FOUND_ROWS()');
        $output = Tools::htmlInput('total-rows', '', $options['total-rows'], 'hidden');
        if (!$options['read-only']) {
            $output .= '<a href="?table=' . urlencode($this->table)
                . '&amp;where[]="><span class="glyphicon glyphicon-plus fa fa-plus-circle" /></span> '
                . $this->translate('New row') . '</a>' . PHP_EOL;
        }
        $output .= $this->viewInputs($options);
        if ($options['total-rows']) {
            //$options['total-rows'] = (int) $options['total-rows'];
            //Assert::integer($options['total-rows']);
            Assert::integer($sql['limit']);
            $output .= $this->viewTable($query, $columns, $options)
                . $this->pagination($sql['limit'], (int) $options['total-rows'], null, $options);
        }
        Assert::string($options['total-rows']); // TODO: if it throws exception just type-cast to string
        return $output . ((!$options['total-rows'] && isset($this->get['col'])) ?
            ('<p class="alert alert-danger"><small>' . $this->translate('No records found.') . '</small></p>') :
            ('<p class="text-info"><small>' . $this->translate('Total rows: ') . $options['total-rows']
            . '.</small></p>'));
    }

    /**
     * Part of the view() method to output the controls.
     *
     * @param array<mixed> $options as in view()
     * @return string
     */
    protected function viewInputs($options)
    {
        $output = '<form action="" method="get" class="table-controls" data-rand="' . $this->rand . '">' . PHP_EOL;
        if (!Tools::set($options['no-toggle'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#toggle-div'
                . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-list-alt"></span> ' . $this->translate('Columns')
                . '</a></legend>
                <div class="toggle-div" id="toggle-div' . $this->rand . '" data-rand="' . $this->rand . '">
                <div class="btn-group-toggle btn-group-sm" data-toggle="buttons">';
            foreach (array_keys($this->fields) as $key => $value) {
                $output .= '<label class="btn btn-light column-toggle active" title="'
                    . $this->translateColumn($value) . '">'
                    . Tools::htmlInput(
                        '',
                        '',
                        '',
                        ['type' => 'checkbox', 'checked' => true, 'autocomplete' => 'off', 'data-column' => $key + 1]
                    )
                    . Tools::h($value) . '</label>' . PHP_EOL;
            }
            $output .= '</div></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($options['no-search'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#search-div' . $this->rand
                . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-search"></span> ' . $this->translate('Search')
                . '</a></legend>
                <div class="search-div" id="search-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($options['no-sort'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#sort-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-sort fa fa-sort mx-1"></span> ' . $this->translate('Sort')
                . '</a></legend>
                <div class="sort-div" id="sort-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        $output .= '<fieldset><legend><span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span> '
            . $this->translate('View') . '</legend>
            ' . Tools::htmlInput('table', '', $this->table, 'hidden') . '
            <label title="' . $this->translate('Text lengths')
            . '"><span class="glyphicon glyphicon-option-horizontal fa fa-ellipsis-h mx-1"></span>'
            . Tools::htmlInput(
                'textsize',
                '',
                Tools::setifnull($this->get['textsize'], $this->DEFAULTS['TEXTSIZE']),
                ['size' => 3, 'class' => 'text-right']
            )
            . '
            </label>
            <label title="' . $this->translate('Rows per page')
            . '"><span class="glyphicon glyphicon-option-vertical fa fa-ellipsis-v mx-1"></span>'
            . Tools::htmlInput(
                'limit',
                '',
                Tools::setifnull($this->get['limit'], $this->DEFAULTS['PAGESIZE']),
                ['size' => 3, 'class' => 'text-right']
            ) . '
            </label>'
            . Tools::htmlInput('offset', '', Tools::setifnull($this->get['offset'], 0), 'hidden') . '
            <button type="submit" class="btn btn-sm ml-1" title="' . $this->translate('View') . '"/>
                <span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span>
            </button>
            </fieldset></form>
            <script type="text/javascript">
            LISTED_FIELDS=[' . Tools::arrayListed(array_keys($this->fields), 4, ',', '"', '"') . '];' . PHP_EOL;
        if (isset($this->get['col'], $this->get['op']) && is_array($this->get['col'])) {
            foreach ($this->get['col'] as $key => $value) {
                Assert::isArray($this->get['val']);
                Assert::isArray($this->get['col']);
                Assert::isArray($this->get['op']);
                if ($value) {
                    Assert::string($value);
                    // adding search rows in the table view done by invoked JavaScript function in dist/scripts/admin.js
                    $this->script .= 'addSearchRow($(\'#search-div' . $this->rand . '\'), "'
                        . Tools::escapeJs($value) . '",' . Tools::setifnull($this->get['op'][$key], 0) . ', "'
                        . addslashes(
                            (string) ((isset($this->get['val'][$key]) && (is_scalar($this->get['val'][$key])
                            || is_null($this->get['val'][$key]) ) )
                            ? ( is_null($this->get['val'][$key]) ? '' : $this->get['val'][$key])
                            : '')
                        ) . '");' . PHP_EOL;
                } else {
                    unset($this->get['col'][$key], $this->get['op'][$key], $this->get['val'][$key]);
                }
            }
        }
        Tools::setifnull($this->get['sort'], []);
        Tools::setifnull($this->get['desc'], []);
        Assert::isArray($this->get['sort']);
        if (count($this->get['sort'])) {
            foreach ($this->get['sort'] as $key => $value) {
                if ($value) {
                    Assert::string($value);
                    $this->script .= 'addSortRow($(\'#sort-div' . $this->rand . '\'), "' . Tools::escapeJs($value)
                        . '",' . (isset($this->get['desc']) && $this->get['desc'] ? 'true' : 'false') . ');' . PHP_EOL;
                } else {
                    Assert::isArray($this->get['desc']);
                    unset($this->get['sort'][$key], $this->get['desc'][$key]);
                }
            }
        }
        if (
            //!isset($this->get['sort']) || // Offset 'sort' on array<array|string> in isset() always exists and is
            // not nullable. as `Assert::isArray($this->get['sort']);` above
            !$this->get['sort']
        ) {
            $this->script .= '$(\'#sort-div' . $this->rand . '\').hide();' . PHP_EOL;
        }
        if (!isset($this->get['col']) || !$this->get['col']) {
            $this->script .= '$(\'#search-div' . $this->rand . '\').hide();' . PHP_EOL;
        }
        $this->script .= '$(\'#toggle-div' . $this->rand . '\').hide();' . PHP_EOL
            . 'addSortRow($(\'#sort-div' . $this->rand . '\'), null, false);' . PHP_EOL
            . 'addSearchRow($(\'#search-div' . $this->rand . '\'), null, 0, "");' . PHP_EOL;
        return $output . '</script>' . PHP_EOL;
    }

    /**
     * Part of the view() method to output the content of selected table
     *
     * @param \mysqli_result<object>|bool $query
     * @param string[] $columns selected columns
     * @param array<mixed> $options as in view()
     * @return string
     */
    protected function viewTable($query, array $columns, array $options)
    {
        Assert::keyExists($this->get, 'sort');
        Assert::isArray($this->get['sort']);
        $output = '<form action="" method="post" enctype="multipart/form-data" data-rand="' . $this->rand . '">'
            . PHP_EOL
            . '<table class="table table-bordered table-striped table-admin" data-order="0" id="table-admin'
            . $this->rand . '">' . PHP_EOL
            . '<thead><tr>' . ($options['no-multi-options'] ? '' : '<th>'
            . Tools::htmlInput(
                '',
                '',
                '',
                ['type' => 'checkbox', 'class' => 'check-all', 'title' => $this->translate('Check all')]
            ) . '</th>');
        $i = 1;
        foreach ($columns as $key => $value) {
            // sort?-1:1 toggles the sorting by this column // TODO toggle ASC/DESC/OFF
            $output .= '<th scope="col" '
                . (count($this->get['sort']) == 1 && $this->get['sort'][0] == $i ? ' class="active"' : '') . '>'
                . '<div class="column-menu"><a href="?' . Tools::urlChange(['desc' => null, 'sort' => null])
                . '&amp;sort[0]=' . ($i * (in_array($i, $this->get['sort']) ? -1 : 1)) . '" title="'
                . $this->translateColumn($key) . '">' . Tools::h($key) . '</a>'
                . '<span class="op">'
                . '<a href="?' . Tools::urlChange(['sort%5B0%5D' => null]) . '&amp;sort%5B0%5D='
                . ($i * (in_array($i, $this->get['sort']) ? -1 : 1))
                . '&amp;desc[0]=1" class="desc ml-1 px-1"><i class="fas fa-long-arrow-alt-down"></i></a>'
                . '<a href="#" data-column="' . $i . '" class="filter px-1">=</a>'
                . '</span></div>'
                . '</th>' . PHP_EOL;
            $i++;
        }
        $output .= '</tr></thead><tbody>';
        if (is_object($query)) {
            for ($i = 0; $row = $query->fetch_assoc(); $i++) {
                $output .= '<tr scope="row"><td' . ($options['no-multi-options'] ? '' : ' class="multi-options"') . '>';
                $url = $this->rowLink($row);
                if (!$options['no-multi-options']) {
                    $value = '';
                    $output .= Tools::htmlInput(
                        'check[]',
                        '',
                        implode('&', $url),
                        ['type' => 'checkbox', 'data-order' => $i, 'id' => 'ch' . $this->rand . $i]
                    );
                }
                $output .= '<a href="?table=' . urlencode($this->table) . '&amp;' . implode('&', $url) . '" title="'
                    . $this->translate('Edit') . '">'
                    . '<small class="glyphicon glyphicon-edit fa fa-pencil fa-edit" aria-hidden="true"></small></a>';
                $output .= '</td>';
                foreach ($row as $key => $value) {
                    if (Tools::ends($key, (string) $this->DEFAULTS['FOREIGNLINK'])) {
                        continue;
                    }
                    $field = (array) $this->fields[$key];
                    $class = [];
                    if (isset($field['foreign_table'])) {
                        Assert::integer($this->DEFAULTS['TEXTSIZE']);
                        $tmp = '<a href="?'
                            . Tools::urlChange(['table' => $field['foreign_table'], 'where[id]' => $value]) . '" '
                            . 'title="'
                            . Tools::h(
                                mb_substr($row[$key . $this->DEFAULTS['FOREIGNLINK']], 0, $this->DEFAULTS['TEXTSIZE'])
                                . (mb_strlen($row[$key . $this->DEFAULTS['FOREIGNLINK']]) > $this->DEFAULTS['TEXTSIZE']
                                    ? '&hellip;' : '')
                            ) . '">'
                            . Tools::h($row[$key]) . '</a>';
                    } else {
                        switch ($field['basictype']) {
                            case 'integer':
                            case 'rational':
                                $class [] = 'text-right';
                            // no break
                            case 'text':
                            default:
                                $tmp = Tools::h(mb_substr($value, 0, (int) $this->DEFAULTS['TEXTSIZE']));
                                break;
                        }
                    }
                    $output .= '<td' . Tools::wrap(implode(' ', $class), ' class="', '"') . '>'
                        . Tools::wrap($tmp, '<label for="ch' . $this->rand . $i . '">', '</label>') . '</td>' . PHP_EOL;
                }
                $output .= '</tr>' . PHP_EOL;
            }
        }
        $output .= '</tbody></table>' . PHP_EOL;
        if (!isset($options['no-selected-rows-operations'])) {
            // TODO test and describe CLONE and EDIT functionality
            $output .= '<div class="selected-rows mb-2"><i class="fa fa-check-square"></i>=<span class="listed">0</span>
                <label class="btn btn-sm btn-light mx-1 mt-2">'
                . Tools::htmlInput(
                    'total-rows',
                    '',
                    $options['total-rows'],
                    ['type' => 'checkbox', 'class' => 'total-rows']
                ) . ' ' . $this->translate('Whole resultset') . '</label>
                <button name="table-export" value="1" class="btn btn-sm ml-1" disabled="disabled">'
                . '<i class="fa fa-download"></i> ' . $this->translate('Export') . '</button>
                <button name="edit-selected" value="1" class="btn btn-sm ml-1" disabled="disabled">'
                . '<i class="fa fa-edit"></i> ' . $this->translate('Edit') . '</button>
                <button name="clone-selected" value="1" class="btn btn-sm ml-1" disabled="disabled">'
                . '<i class="far fa-clone"></i> ' . $this->translate('Clone') . '</button>
                </div>';
        }
        return $output . Tools::htmlInput('database-table', '', $this->table, 'hidden')
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
            . '</form>' . PHP_EOL;
    }

    /**
     * Output HTML link for one page. Only used in ->pagination(), thus is private
     *
     * @param int $page which page
     * @param int $currentPage current page
     * @param int $rowsPerPage rows per page
     * @param string $label used in HTML <label>
     * @param string $title used in HTML title="..."
     * @return string
     */
    private function addPage($page, $currentPage, $rowsPerPage, $label = null, $title = '')
    {
        return '<li class="page-item' . ($page == $currentPage ? ' active' : '') . '">'
            . '<a href="?' . Tools::urlChange(['offset' => ($page - 1) * $rowsPerPage])
            . '" class="page-link" ' . Tools::wrap($title, ' title="', '"') . '>'
            . Tools::ifnull($label, $page) . '</a></li>' . PHP_EOL;
    }

    /**
     * Output pagination for a table
     *
     * @param int $rowsPerPage
     * @param int $totalRows
     * @param int $offset
     * @param array<mixed> $options as in view()
     * @return string
     */
    public function pagination($rowsPerPage, $totalRows, $offset = null, $options = [])
    {
        $title = $this->translate('Go to page');
        if (is_null($offset)) {
            $offset = max(isset($this->get['offset']) ? (int) $this->get['offset'] : 0, 0);
        }
        $rowsPerPage = max($rowsPerPage, 1);
        $pages = (int) ceil($totalRows / $rowsPerPage);
        $currentPage = (int) floor($offset / $rowsPerPage) + 1;
        if ($pages <= 1) {
            return '';
        }
        $output = '<nav><ul class="pagination">' .
            '<li class="page-item disabled"><a name="" class="page-link go-to-page non-page" data-pages="' . $pages
            . '" tabindex="-1">' . $this->translate('Page') . ':</a></li>';
        if ($pages <= (int) $this->DEFAULTS['PAGES_AROUND'] * 2 + 3) { // pagination with all pages
            if ($currentPage > 1) {
                $output .= $this->addPage(
                    $currentPage - 1,
                    $currentPage,
                    $rowsPerPage,
                    $this->translate('Previous'),
                    $title
                );
            }
            for ($page = 1; $page <= $pages; $page++) {
                $output .= $this->addPage($page, $currentPage, $rowsPerPage, null, $title);
            }
            if ($currentPage < $pages) {
                $output .= $this->addPage(
                    $currentPage + 1,
                    $currentPage,
                    $rowsPerPage,
                    $this->translate('Next'),
                    $title
                );
            }
        } else { // pagination with first, current, last pages and "..."s in between
            if ($currentPage > 1) {
                $output .= $this->addPage(
                    $currentPage - 1,
                    $currentPage,
                    $rowsPerPage,
                    $this->translate('Previous'),
                    $title
                );
            }
            $output .= $this->addPage(1, $currentPage, $rowsPerPage, null, $title);
            $output .= ($currentPage - (int) $this->DEFAULTS['PAGES_AROUND'] > 2
                ? '<li><a name="" class="non-page">&hellip;</a></li>' : '');
            $page = max($currentPage - (int) $this->DEFAULTS['PAGES_AROUND'], 2);
            for ($page; $page <= min($currentPage + (int) $this->DEFAULTS['PAGES_AROUND'], $pages); $page++) {
                $output .= $this->addPage($page, $currentPage, $rowsPerPage, null, $title);
            }
            $output .= ($currentPage < $pages - (int) $this->DEFAULTS['PAGES_AROUND'] - 1
                ? '<li><a name="" class="non-page">&hellip;</a></li>' : '');
            if ($currentPage < $pages - (int) $this->DEFAULTS['PAGES_AROUND']) {
                $output .= $this->addPage($pages, $currentPage, $rowsPerPage, null, $title);
            }
            if ($currentPage < $pages) {
                $output .= $this->addPage(
                    $currentPage + 1,
                    $currentPage,
                    $rowsPerPage,
                    $this->translate('Next'),
                    $title
                );
            }
        }
        return $output . '</ul></nav>' . PHP_EOL;
    }

    /**
     * Return fields which are keys (indexes) of given type
     *
     * @param string $filterType key type, either "PRI", "MUL", "UNI" or ""
     * @return string[] key names
     */
    public function fieldKeys($filterType)
    {
        $filterType = strtolower($filterType);
        $result = [];
        if (is_array($this->fields)) {
            foreach ($this->fields as $key => $value) {
                if (isset($value['key']) && strtolower($value['key']) == $filterType) {
                    $result [] = $key;
                }
            }
        }
        return $result;
    }

    /**
     * Table name property getter
     *
     * @return string
     * @throws Exception
     */
    public function getTable()
    {
        if (empty($this->table)) {
            throw new \Exception('table is not set');
        }
        return $this->table;
    }

    /**
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Wrapper for $this->dbms->escapeSQL()
     *
     * @param string $string
     * @return string
     */
    public function escapeSQL($string)
    {
        return $this->dbms->escapeSQL($string);
    }

    /**
     * Wrapper for $this->dbms->escapeDbIdentifier()
     *
     * @param string $string to escape
     * @return string escaped identifier
     */
    public function escapeDbIdentifier($string)
    {
        return $this->dbms->escapeDbIdentifier($string);
    }

    /**
     * Wrapper for $this->dbms->errorDuplicateEntry()
     *
     * @return bool
     */
    public function errorDuplicateEntry()
    {
        return $this->dbms->errorDuplicateEntry();
    }

    /**
     * Wrapper for $this->dbms->checkIntervalFormat()
     *
     * @param string $interval
     * @return int|false 1=yes, 0=no, false=error
     */
    public function checkIntervalFormat($interval)
    {
        return $this->dbms->checkIntervalFormat($interval);
    }

    /**
     * Resolve an SQL query and add given message for success or error
     *
     * @param string $sql SQL to execute
     * @param string $successMessage message in case of success
     * @param string $errorMessage message in case of an error
     * @param false|string $noChangeMessage optional message in case of no affected change
     *   false = use $successMessage
     *
     * @return bool true for success, false for failure of the query;
     */
    public function resolveSQL($sql, $successMessage, $errorMessage, $noChangeMessage = false)
    {
        Assert::string($sql);
        if ($this->dbms->query($sql)) {
            Tools::addMessage(
                'success',
                strtr(
                    $this->dbms->affected_rows == 0 && $noChangeMessage !== false ? $noChangeMessage : $successMessage,
                    [
                        '%insertId%' => (preg_match('/^\s*INSERT\s/i', $sql)) ? $this->dbms->insert_id : '',
                        '%affectedRows%' => $this->dbms->affected_rows
                    ]
                )
            );
            return true;
        }
        Tools::addMessage(
            'error',
            strtr($errorMessage, ['%error%' => $this->dbms->error, '%errno%' => $this->dbms->errno])
        );
        return false;
    }

    /**
     * Return text translated according to $this->TRANSLATION[]. Return original text, if translation is not found.
     * If the text differs only by case of the first letter, return its translation and change the case of its first
     * letter.
     * @example: TRANSLATION['List'] = 'Seznam'; $this->translate('List') --> "Seznam",
     *     $this->translate('list') --> "seznam"
     * @example: TRANSLATION['list'] = 'seznam'; $this->translate('list') --> "seznam",
     *     $this->translate('List') --> "Seznam"
     * TODO: refactor this wrapper away
     *
     * @param string $text
     * @param bool $escape escape for HTML? true by default
     * @param int $changeCase - 0 = no change, 1 = first upper, -1 = first lower, 2 = all caps, -2 = all lower
     * @param string $encoding or null (default) for mb_internal_encoding()
     * @return string
     */
    public function translate($text, $escape = true, $changeCase = 0, $encoding = null)
    {
        return $this->localisation->translate($text, null, $encoding);
    }

    /**
     * Translate name of a column - defined in translations as "column:<name of the column>"
     * TODO: prefix public function translateColumn($column, $prefix="column")
     *
     * @param string $column column to translate
     * @param bool $escape escape for HTML? true by default
     * @param int $changeCase - 0 = no change, 1 = first upper, -1 = first lower, 2 = all caps, -2 = all lower
     * @param string $encoding or null (default) for mb_internal_encoding()
     * @return string
     */
    public function translateColumn($column, $escape = true, $changeCase = 0, $encoding = null)
    {
        $result = $this->translate("column:$column");
        return $result == "column:$column" ? $column : $result;
    }
    /** custom methods - meant to be rewritten in the class' children */

    /**
     * Custom HTML instead of standard field's input
     *
     * @param string $field
     * @param mixed $value field's value
     * @param array<string> $record
     * @return bool|string - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customInput($field, $value, array $record = [])
    {
        return false;
    }

    /**
     * Custom HTML showed before particular field (but after its label).
     *
     * @param string $field
     * @param mixed $value
     * @param array<string> $record
     * @return string HTML
     */
    public function customInputBefore($field, $value, array $record = [])
    {
        return '';
    }

    /**
     * Custom HTML showed after particular field (but still in the table row, in case of table display).
     *
     * @param string $field
     * @param mixed $value
     * @param array<string> $record
     * @return string HTML
     */
    public function customInputAfter($field, $value, array $record = [])
    {
        return '';
    }

    /**
     * Custom HTML to be show after detail's edit form but before action buttons
     *
     * @param array<string> $record
     * @return string
     */
    public function customRecordDetail(array $record)
    {
        return '';
    }

    /**
     * Custom HTML to be show after standard action buttons of the detail's form
     *
     * @param array<string> $record
     * @return string
     */
    public function customRecordActions(array $record)
    {
        return '';
    }

    /**
     * Custom saving of a record
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customSave()
    {
        return false;
    }

    /**
     * Custom event after deleting of a record
     *
     * @return bool success
     */
    public function customAfterDelete()
    {
        return true;
    }

    /**
     * Custom operation with table records. Called after the $table listing
     *
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customOperation()
    {
        return false;
    }

    /**
     * Custom search. Called to optionally fill the search select
     *
     * @return void
     */
    public function customSearch()
    {
        // no action
    }

    /**
     * Called to optionally fill conditions to WHERE clause of the SQL statement selecting given table
     *
     * @return void
     */
    public function customCondition()
    {
        // no action
    }

    /**
     * User-defined manipulating with column value of given table
     *
     * @param string $column
     * @param array<mixed> $row
     * @return mixed original or manipulated data
     */
    public function customValue($column, array $row)
    {
        return isset($row[$column]) ? $row[$column] : false;
    }

    /**
     * Display a break-down of records in given table (default "content") by given column (default "type")
     *
     * @param array<string> $options OPTIONAL
     * @return string
     */
    public function contentByType(array $options = [])
    {
        Tools::setifnull($options['table'], 'content');
        Tools::setifnull($options['type'], 'type');
        $query = $this->dbms->query('SELECT SQL_CALC_FOUND_ROWS ' . Tools::escapeDbIdentifier($options['type'])
            . ',COUNT(' . Tools::escapeDbIdentifier($options['type']) . ')'
            . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $options['table'])
            . ' GROUP BY ' . Tools::escapeDbIdentifier($options['type']) . ' WITH ROLLUP LIMIT 100');
        if (is_bool($query)) {
            return '';
        }
        $typeIndex = 0;
        foreach (array_keys($this->fields) as $key => $value) {
            if ($value == $options['type']) {
                $typeIndex = $key + 1;
                break;
            }
        }
        $totalRows = $this->dbms->fetchSingle('SELECT FOUND_ROWS()');
        $output = '<details><summary><big>' . $this->translate('By type') . '</big></summary>' . PHP_EOL
            . '<table class="table table-striped">' . PHP_EOL
            . '<tr><th>' . $this->translate('Type') . '</th><th class="text-right">' . $this->translate('Count')
            . '</th></tr>' . PHP_EOL;
        while ($row = $query->fetch_row()) {
            $output .= '<tr><td><a href="' . ($url = '?table=' . urlencode(TAB_PREFIX . $options['table'])
                . '&amp;col[0]=' . $typeIndex . '&amp;op[0]=0&amp;val[0]=' . urlencode($row[0])) . '" title="'
                . $this->translate('Filter records') . '">'
                . ($row[0] ? Tools::h($row[0]) : ($row[0] === '' ? '<i class="insipid">(' . $this->translate('empty')
                    . ')</i>' : '<big>&Sum;</big>')) . '</a>'
                . '</td><td class="text-right"><a href="' . $url . '" title="' . $this->translate('Filter records')
                . '">' . (int) $row[1] . '</td></tr>' . PHP_EOL;
        }
        return $output . '</table></details>';
    }

    /**
     * Decode options in 'set' and 'enum' columns - specific to MySQL/MariaDb
     *
     * @param string $list list of options (e.g. "enum('single','married','divorced')"
     *     or just "'single','married','divorced'")
     * @return array<string>
     */
    public function decodeChoiceOptions($list)
    {
        return $this->dbms->decodeChoiceOptions($list);
    }

    /**
     * Return keys to current table of a specified type(s)
     *
     * @param string[] $types type(s) - possible items: PRI, UNI, MUL (database specific)
     * @return string[] filtered keys, e.g. ['id'=>'PRI', 'division'=>'MUL', 'document_id'=>'UNI']
     */
    public function filterKeys(array $types)
    {
//        if (!is_array($types) || func_num_args() > 1) {
//            $types = func_get_args();
//        }
        $result = [];
        foreach ($types as $type) {
            foreach ($this->fields as $key => $field) {
                if ($field['key'] == $type) {
                    $result[$key] = $type;
                }
            }
        }
        return $result;
    }

    /**
     * Return a link (URL fragment) to a given row of the current table as an array.
     * To make a string of it, use implode("&", ...).
     *
     * @param array<string|null> $row
     * @return array<string> URL fragment identifying current row, e.g. "where[id]=5"
     */
    public function rowLink($row)
    {
        $result = [];
        if ($keys = $this->filterKeys(['PRI'])) {
            Assert::string(array_keys($keys)[0]);
            $result [] = 'where[' . urlencode(array_keys($keys)[0]) . ']='
                . urlencode((isset($row[array_keys($keys)[0]]) && $row[array_keys($keys)[0]])
                    ? $row[array_keys($keys)[0]] : '');
        } elseif ($keys = $this->filterKeys(['UNI'])) {
            foreach ($keys as $key => $value) {
                if (isset($row[$key]) && $row[$key] !== null) {
                    $result [] = 'where[' . urlencode($key) . ']=' . urlencode($value);
                    break;
                } else {
                    $result [] = 'null[' . urlencode($key) . ']=';
                }
            }
        }
        if (!$result) {
            foreach ($this->fields as $key => $field) {
                if (!isset($row[$key])) {
                    continue;
                }
                $result [] = is_null($row[$key]) ? //
                    'null[' . urlencode($key) . ']=' : 'where[' . urlencode($key) . ']=' . urlencode($row[$key]);
            }
        }
        return $result;
    }
}
