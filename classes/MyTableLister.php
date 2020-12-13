<?php

namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;

/**
 * Class that can list rows of a database table, with editable search/filter
 * functionality, links to edit each particular row, multi-row action, etc.
 * dependencies: GodsDev\Tools, MySQL/MariaDB (it uses INFORMATION_SHEMA)
 */
class MyTableLister
{
    use \Nette\SmartObject;

    /** @var LogMysqli database management system */
    protected $dbms;

    /** @var string current database */
    protected $database;

    /** @var string table to list */
    protected $table;

    /** @var array all tables in the database */
    public $tables;

    /** @var array all fields in the table */
    public $fields;

    /** @var array display options */
    protected $options;

    /** @var string JavaScript code gathered to show the listing */
    public $script;

    /** @var int random item used in HTML */
    public $rand;

    /** @var array arithmetical and logical operations for searching */
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

    /** @var array factory setting defaults */
    protected $DEFAULTS = [
        'PAGESIZE' => 100,
        'MAXPAGESIZE' => 10000,
        'MAXSELECTSIZE' => 10000,
        'TEXTSIZE' => 100,
        'PAGES_AROUND' => 2, // used in pagination
        'FOREIGNLINK' => '-link' //suffix added to POST variables for links
    ];

    /** @var array Selected locale strings */
    public $TRANSLATION = [];

    /** @var array Available languages for MyCMS */
    public $TRANSLATIONS = [
        'en' => 'English'
    ];

    /** @var array possible table settings, stored in its comment */
    public $tableContext = null;

    /**
     * Constructor - stores passed parameters to object's attributes
     *
     * @param LogMysqli $dbms database management system already connected to wanted database
     * @param string $table to view
     * @param array $options
     */
    public function __construct(LogMysqli $dbms, $table, array $options = [])
    {
        $this->dbms = $dbms;
        $this->options = $options;
        $this->database = $this->dbms->fetchSingle('SELECT DATABASE()');
        $this->getTables();
        $this->setTable($table);
        $this->rand = rand((int) 1e5, (int) (1e6 - 1));
    }

    /**
     * Get all tables in the database (including comments) and store them to tables
     *
     * @return void
     */
    public function getTables()
    {
        $this->tables = [];
        $query = $this->dbms->query('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES '
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
        if ($query = $this->dbms->query('SHOW FULL COLUMNS IN ' . $this->escapeDbIdentifier($this->table))) {
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
        } else {
            throw new \RunTimeException('Could not get columns from table ' . $this->table . '.');
        }
        $query = $this->dbms->query(
            'SELECT COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_NAME != "PRIMARY" AND CONSTRAINT_CATALOG = "def"
            AND TABLE_SCHEMA = "' . $this->escapeSQL($this->database) . '"
            AND TABLE_NAME = "' . $this->escapeSQL($this->table) . '"'
        );
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $this->fields[$row['COLUMN_NAME']]['foreign_table'] = $row['REFERENCED_TABLE_NAME'];
                $this->fields[$row['COLUMN_NAME']]['foreign_column'] = $row['REFERENCED_COLUMN_NAME'];
            }
        }
        $tmp = $this->dbms->fetchSingle('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA="' . $this->escapeSQL($this->database) . '" AND TABLE_NAME="' . $this->escapeSQL($this->table) . '"');
        $this->tableContext = json_decode($tmp, true) or [];
    }

    /**
     * Compose a SELECT SQL statement with given columns and _GET variables
     *
     * @param array $columns
     * @param array $vars &$vars variables used to filter records
     * @return array with these indexes: [join], [where], [sort], [sql]
     */
    public function selectSQL($columns, &$vars)
    {
        $result = [
            'join' => '',
            'where' => '',
            'order by' => '',
            'sql' => ''
        ];
        $result['limit'] = isset($vars['limit']) && $vars['limit'] ? (int) $vars['limit'] : $this->DEFAULTS['PAGESIZE'];
        if ($result['limit'] < 1 || $result['limit'] > $this->DEFAULTS['MAXPAGESIZE']) {
            $result['limit'] = $this->DEFAULTS['PAGESIZE'];
        }
        $result['offset'] = max(isset($vars['offset']) ? (int) $vars['offset'] : 0, 0);
        foreach ($columns as $key => $value) {
            if (isset($this->fields[$key]['foreign_table']) && $this->fields[$key]['foreign_table']) {
                $result['join'] .= ' LEFT JOIN ' . $this->fields[$key]['foreign_table']
                    . ' ON ' . $this->escapeDbIdentifier($this->table) . '.' . $this->escapeDbIdentifier($key)
                    . '=' . $this->escapeDbIdentifier($this->fields[$key]['foreign_table']) . '.' . $this->escapeDbIdentifier($this->fields[$key]['foreign_column']);
                // try if column of the same name as the table exists (as a replacement for foreign table); use the first field in the table if it doesn't exist
                $tmp = $this->dbms->query('SHOW FIELDS FROM ' . $this->escapeDbIdentifier($this->fields[$key]['foreign_table']))->fetch_all();
                foreach ($tmp as $k => $v) {
                    $tmp[$v[0]] = $v[0];
                    unset($tmp[$k]);
                }
                $foreign_link = mb_substr($this->fields[$key]['foreign_table'], mb_strlen(TAB_PREFIX));
                $foreign_link = isset($tmp[$foreign_link]) && $foreign_link ? $foreign_link : reset($tmp);
                $columns[$key] = $this->escapeDbIdentifier($this->table) . '.' . $value . ','
                    . $this->escapeDbIdentifier($this->fields[$key]['foreign_table']) . '.'
                    . $this->escapeDbIdentifier($foreign_link) . ' AS ' . $this->escapeDbIdentifier($key . $this->DEFAULTS['FOREIGNLINK']);
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
                if (isset($filterColumn[$value], $vars['val'][$key])) {
                    $id = $this->escapeDbIdentifier($this->table) . '.' . $this->escapeDbIdentifier($filterColumn[$value]);
                    $val = $vars['val'][$key];
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
        foreach (Tools::setifempty($vars['sort'], []) as $key => $value) {
            if (isset(array_keys($columns)[(int) $value - 1])) {
                $result['order by'] .= ',' . array_values($columns)[(int) $value - 1] . (isset($vars['desc'][$key]) && $vars['desc'][$key] ? ' DESC' : '');
            }
        }
        $result['select'] = 'SELECT SQL_CALC_FOUND_ROWS ' . implode(',', $columns) . ' FROM '
            . $this->escapeDbIdentifier($this->table) . $result['join']
            . Tools::wrap(substr($result['where'], 4), ' WHERE ')
            . Tools::wrap(substr($result['order by'], 1), ' ORDER BY ');
        $result['sql'] = $result['select'] . ' LIMIT ' . $result['offset'] . ', ' . $result['limit'];
        return $result;
    }

    /**
     * Compose a part of an UPDATE SQL statement for selected records with given columns and _GET variables
     * Method bulkUpdateSQL() creates part of SQL statement UPDATE for bulk editing of columns.
     * Input is an array, where each column can have an operation and an operand (e.g. add or substract from a column).
     * Operation `original` means "leave the column as is" (i.e. don't use it in this SQL statement)
     * And for any other (=unknown) operation is the column ignored, i.e. is not used in this SQL statement.
     *
     * @param array $vars &$vars variables used to filter records
     * @return string
     */
    public function bulkUpdateSQL(&$vars)
    {
        $result = '';
        foreach ($vars['fields'] as $field => $value) {
            switch (Tools::set($vars['op'][$field])) {
                case 'value':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = "' . $this->escapeSQL($value) . '"';
                    break;
                case '+':
                case '-':
                case '*':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $this->escapeDbIdentifier($field) . $vars['op'][$field] . ' ' . ($vars['op'][$field] == '*' ? (double) $value : (int) $value);
                    break;
                case 'random':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = RAND() * ' . ($value == 0 ? 1 : (double) $value);
                    break;
                case 'now':
                case 'uuid':
                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $vars['op'][$field] . '()';
                    break;
                case 'append':
                    $result .= ', CONCAT(' . $this->escapeDbIdentifier($field) . ', "' . $this->escapeSQL($value) . '")';
                    break;
                case 'prepend':
                    $result .= ', CONCAT("' . $this->escapeSQL($value) . '", ' . $this->escapeDbIdentifier($field) . ')';
                    break;
                case 'addtime':
                case 'subtime':
                    $result .= ', ' . $vars['op'][$field] . '(' . $this->escapeDbIdentifier($field) . ', "' . $this->escapeSQL($value) . '")';
                    break;
//                case 'original':
//                    $result .= ', ' . $this->escapeDbIdentifier($field) . ' = ' . $this->escapeDbIdentifier($field);
//                    break;
                default:
                    error_log('bulkUpdateSQL unknown operator ' . (string) Tools::set($vars['op'][$field]));
                    break;
            }
        }
        // todo fix Method GodsDev\MyCMS\MyTableLister::bulkUpdateSQL() should return string but return statement is missing.
    }

    /**
     * Create array of columns for preparing the SQL statement
     *
     * @param array $options
     * @return array
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
     * @param array $options configuration array
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
     *   $options['return-output']=non-zero - return output (instead of echo)
     * @return mixed void||string (for $options['return-output'])
     */
    public function view(array $options = [])
    {
        foreach (['read-only', 'no-search', 'no-sort', 'no-toggle', 'no-display-options', 'no-multi-options', 'no-selected-rows-operations'] as $i) {
            Tools::setifempty($options[$i]);
        }
        // find out what columns to include/exclude
        if (!($columns = $this->getColumns($options))) {
            return;
        }
        $sql = $this->selectSQL($columns, $_GET);
        $query = $this->dbms->query($sql['sql']);
        $options['total-rows'] = $this->dbms->fetchSingle('SELECT FOUND_ROWS()');
        $output = Tools::htmlInput('total-rows', '', $options['total-rows'], 'hidden');
        if (!$options['read-only']) {
            $output .= '<a href="?table=' . urlencode($this->table) . '&amp;where[]="><span class="glyphicon glyphicon-plus fa fa-plus-circle" /></span> ' . $this->translate('New row') . '</a>' . PHP_EOL;
        }
        $output .= $this->viewInputs($options);
        if ($options['total-rows']) {
            $output .= $this->viewTable($query, $columns, $options)
                . $this->pagination($sql['limit'], $options['total-rows'], null, $options);
        }
        if (!$options['total-rows'] && isset($_GET['col'])) {
            $output .= '<p class="alert alert-danger"><small>' . $this->translate('No records found.') . '</small></p>';
        } else {
            $output .= '<p class="text-info"><small>' . $this->translate('Total rows: ') . $options['total-rows'] . '.</small></p>';
        }
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
    }

    /**
     * Part of the view() method to output the controls.
     *
     * @param array $options as in view()
     * @return mixed void||string (for $options['return-output'])
     */
    protected function viewInputs($options)
    {
        $output = '<form action="" method="get" class="table-controls" data-rand="' . $this->rand . '">' . PHP_EOL;
        if (!Tools::set($options['no-toggle'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#toggle-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-list-alt"></span> ' . $this->translate('Columns') . '</a></legend>
                <div class="toggle-div" id="toggle-div' . $this->rand . '" data-rand="' . $this->rand . '">
                <div class="btn-group-toggle btn-group-sm" data-toggle="buttons">';
            foreach (array_keys($this->fields) as $key => $value) {
                $output .= '<label class="btn btn-light column-toggle active" title="' . $this->translateColumn($value) . '">'
                    . Tools::htmlInput('', '', '', ['type' => 'checkbox', 'checked' => true, 'autocomplete' => 'off', 'data-column' => $key + 1])
                    . Tools::h($value) . '</label>' . PHP_EOL;
            }
            $output .= '</div></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($options['no-search'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#search-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-search"></span> ' . $this->translate('Search') . '</a></legend>
                <div class="search-div" id="search-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($options['no-sort'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#sort-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-sort fa fa-sort mx-1"></span> ' . $this->translate('Sort') . '</a></legend>
                <div class="sort-div" id="sort-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        $output .= '<fieldset><legend><span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span> ' . $this->translate('View') . '</legend>
            ' . Tools::htmlInput('table', '', $this->table, 'hidden') . '
            <label title="' . $this->translate('Text lengths') . '"><span class="glyphicon glyphicon-option-horizontal fa fa-ellipsis-h mx-1"></span>'
            . Tools::htmlInput('textsize', '', Tools::setifnull($_GET['textsize'], $this->DEFAULTS['TEXTSIZE']), ['size' => 3, 'class' => 'text-right']) . '
            </label>
            <label title="' . $this->translate('Rows per page') . '"><span class="glyphicon glyphicon-option-vertical fa fa-ellipsis-v mx-1"></span>'
            . Tools::htmlInput('limit', '', Tools::setifnull($_GET['limit'], $this->DEFAULTS['PAGESIZE']), ['size' => 3, 'class' => 'text-right']) . '
            </label>'
            . Tools::htmlInput('offset', '', Tools::setifnull($_GET['offset'], 0), 'hidden') . '
            <button type="submit" class="btn btn-sm ml-1" title="' . $this->translate('View') . '"/>
                <span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span>
            </button>
            </fieldset></form>
            <script type="text/javascript">
            LISTED_FIELDS=[' . Tools::arrayListed(array_keys($this->fields), 4, ',', '"', '"') . '];' . PHP_EOL;
        if (isset($_GET['col'], $_GET['op']) && is_array($_GET['col'])) {
            foreach ($_GET['col'] as $key => $value) {
                if ($value) {
                    $this->script .= 'addSearchRow($(\'#search-div' . $this->rand . '\'), "' . Tools::escapeJs($value) . '",' . Tools::setifnull($_GET['op'][$key], 0) . ', "' . addslashes(Tools::setifnull($_GET['val'][$key], '')) . '");' . PHP_EOL;
                } else {
                    unset($_GET['col'][$key], $_GET['op'][$key], $_GET['val'][$key]);
                }
            }
        }
        Tools::setifnull($_GET['sort'], []);
        Tools::setifnull($_GET['desc'], []);
        if (count($_GET['sort'])) {
            foreach ($_GET['sort'] as $key => $value) {
                if ($value) {
                    $this->script .= 'addSortRow($(\'#sort-div' . $this->rand . '\'), "' . Tools::escapeJs($value) . '",' . (isset($_GET['desc']) && $_GET['desc'] ? 'true' : 'false') . ');' . PHP_EOL;
                } else {
                    unset($_GET['sort'][$key], $_GET['desc'][$key]);
                }
            }
        }
        if (!isset($_GET['sort']) || !$_GET['sort']) {
            $this->script .= '$(\'#sort-div' . $this->rand . '\').hide();' . PHP_EOL;
        }
        if (!isset($_GET['col']) || !$_GET['col']) {
            $this->script .= '$(\'#search-div' . $this->rand . '\').hide();' . PHP_EOL;
        }
        $this->script .= '$(\'#toggle-div' . $this->rand . '\').hide();' . PHP_EOL
            . 'addSortRow($(\'#sort-div' . $this->rand . '\'), null, false);' . PHP_EOL
            . 'addSearchRow($(\'#search-div' . $this->rand . '\'), null, 0, "");' . PHP_EOL;
        $output .= '</script>' . PHP_EOL;
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
    }

    /**
     * Part of the view() method to output the content of selected table
     *
     * @param \mysqli_result $query
     * @param array $columns selected columns
     * @param array $options as in view()
     * @return mixed void or string (for $options['return-output'])
     */
    protected function viewTable(\mysqli_result $query, array $columns, array $options)
    {
        Tools::setifnull($_GET['sort']);
        $output = '<form action="" method="post" enctype="multipart/form-data" data-rand="' . $this->rand . '">' . PHP_EOL
            . '<table class="table table-bordered table-striped table-admin" data-order="0" id="table-admin' . $this->rand . '">' . PHP_EOL
            . '<thead><tr>' . ($options['no-multi-options'] ? '' : '<th>' . Tools::htmlInput('', '', '', ['type' => 'checkbox', 'class' => 'check-all', 'title' => $this->translate('Check all')]) . '</th>');
        $i = 1;
        foreach ($columns as $key => $value) {
            $output .= '<th scope="col" ' . (count($_GET['sort']) == 1 && $_GET['sort'][0] == $i ? ' class="active"' : '') . '>'
                . '<div class="column-menu"><a href="?' . Tools::urlChange(['desc' => null, 'sort' => null]) . '&amp;sort[0]=' . ($i * ($_GET['sort'] == $i ? -1 : 1)) . '" title="' . $this->translateColumn($key) . '">' . Tools::h($key) . '</a>'
                . '<span class="op">'
                . '<a href="?' . Tools::urlChange(['sort%5B0%5D' => null]) . '&amp;sort%5B0%5D=' . ($i * ($_GET['sort'] == $i ? -1 : 1)) . '&amp;desc[0]=1" class="desc ml-1 px-1"><i class="fas fa-long-arrow-alt-down"></i></a>'
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
                    $output .= Tools::htmlInput('check[]', '', implode('&', $url), ['type' => 'checkbox', 'data-order' => $i, 'id' => 'ch' . $this->rand . $i]);
                }
                $output .= '<a href="?table=' . urlencode($this->table) . '&amp;' . implode('&', $url) . '" title="' . $this->translate('Edit') . '">'
                    . '<small class="glyphicon glyphicon-edit fa fa-pencil fa-edit" aria-hidden="true"></small></a>';
                $output .= '</td>';
                foreach ($row as $key => $value) {
                    if (Tools::ends($key, $this->DEFAULTS['FOREIGNLINK'])) {
                        continue;
                    }
                    $field = (array) $this->fields[$key];
                    $class = [];
                    if (isset($field['foreign_table'])) {
                        $tmp = '<a href="?' . Tools::urlChange(['table' => $field['foreign_table'], 'where[id]' => $value]) . '" '
                            . 'title="' . Tools::h(mb_substr($row[$key . $this->DEFAULTS['FOREIGNLINK']], 0, $this->DEFAULTS['TEXTSIZE']) . (mb_strlen($row[$key . $this->DEFAULTS['FOREIGNLINK']]) > $this->DEFAULTS['TEXTSIZE'] ? '&hellip;' : '')) . '">'
                            . Tools::h($row[$key]) . '</a>';
                    } else {
                        switch ($field['basictype']) {
                            case 'integer':
                            case 'rational':
                                $class [] = 'text-right';
                            // no break
                            case 'text':
                            default:
                                $tmp = Tools::h(mb_substr($value, 0, $this->DEFAULTS['TEXTSIZE']));
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
            $output .= '<div class="selected-rows mb-2"><i class="fa fa-check-square"></i>=<span class="listed">0</span>
                <label class="btn btn-sm btn-light mx-1 mt-2">' . Tools::htmlInput('total-rows', '', $options['total-rows'], ['type' => 'checkbox', 'class' => 'total-rows']) . ' ' . $this->translate('Whole resultset') . '</label>
                <button name="table-export" value="1" class="btn btn-sm ml-1" disabled="disabled"><i class="fa fa-download"></i> ' . $this->translate('Export') . '</button>
                <button name="edit-selected" value="1" class="btn btn-sm ml-1" disabled="disabled"><i class="fa fa-edit"></i> ' . $this->translate('Edit') . '</button>
                <button name="clone-selected" value="1" class="btn btn-sm ml-1" disabled="disabled"><i class="far fa-clone"></i> ' . $this->translate('Clone') . '</button>
                </div>';
        }
        $output .= Tools::htmlInput('database-table', '', $this->table, 'hidden')
            . Tools::htmlInput('token', '', end($_SESSION['token']), 'hidden')
            . '</form>' . PHP_EOL;
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
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
            . '<a href="?' . Tools::urlChange(['offset' => ($page - 1) * $rowsPerPage]) . '" class="page-link" ' . Tools::wrap($title, ' title="', '"') . '>'
            . Tools::ifnull($label, $page) . '</a></li>' . PHP_EOL;
    }

    /**
     * Output pagination for a table
     *
     * @param int $rowsPerPage
     * @param int $totalRows
     * @param int $offset
     * @param array $options as in view()
     * @return mixed void or string (for $options['return-output'])
     */
    public function pagination($rowsPerPage, $totalRows, $offset = null, $options = [])
    {
        $title = $this->translate('Go to page');
        if (is_null($offset)) {
            $offset = max(isset($_GET['offset']) ? (int) $_GET['offset'] : 0, 0);
        }
        $rowsPerPage = max($rowsPerPage, 1);
        $pages = (int) ceil($totalRows / $rowsPerPage);
        $currentPage = (int) floor($offset / $rowsPerPage) + 1;
        if ($pages <= 1) {
            return;
        }
        $output = '<nav><ul class="pagination"><li class="page-item disabled"><a name="" class="page-link go-to-page non-page" data-pages="' . $pages . '" tabindex="-1">' . $this->translate('Page') . ':</a></li>';
        if ($pages <= $this->DEFAULTS['PAGES_AROUND'] * 2 + 3) { // pagination with all pages
            if ($currentPage > 1) {
                $output .= $this->addPage($currentPage - 1, $currentPage, $rowsPerPage, $this->translate('Previous'), $title);
            }
            for ($page = 1; $page <= $pages; $page++) {
                $output .= $this->addPage($page, $currentPage, $rowsPerPage, null, $title);
            }
            if ($currentPage < $pages) {
                $output .= $this->addPage($currentPage + 1, $currentPage, $rowsPerPage, $this->translate('Next'), $title);
            }
        } else { // pagination with first, current, last pages and "..."s in between
            if ($currentPage > 1) {
                $output .= $this->addPage($currentPage - 1, $currentPage, $rowsPerPage, $this->translate('Previous'), $title);
            }
            $output .= $this->addPage(1, $currentPage, $rowsPerPage, null, $title);
            $output .= ($currentPage - $this->DEFAULTS['PAGES_AROUND'] > 2 ? '<li><a name="" class="non-page">&hellip;</a></li>' : '');
            for ($page = max($currentPage - $this->DEFAULTS['PAGES_AROUND'], 2); $page <= min($currentPage + $this->DEFAULTS['PAGES_AROUND'], $pages); $page++) {
                $output .= $this->addPage($page, $currentPage, $rowsPerPage, null, $title);
            }
            $output .= ($currentPage < $pages - $this->DEFAULTS['PAGES_AROUND'] - 1 ? '<li><a name="" class="non-page">&hellip;</a></li>' : '');
            if ($currentPage < $pages - $this->DEFAULTS['PAGES_AROUND']) {
                $output .= $this->addPage($pages, $currentPage, $rowsPerPage, null, $title);
            }
            if ($currentPage < $pages) {
                $output .= $this->addPage($currentPage + 1, $currentPage, $rowsPerPage, $this->translate('Next'), $title);
            }
        }
        $output .= '</ul></nav>' . PHP_EOL;
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
    }

    /**
     * Return fields which are keys (indexes) of given type
     *
     * @param string $filterType key type, either "PRI", "MUL", "UNI" or ""
     * @return array key names
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

    public function getTable()
    {
        return $this->table;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Wrapper for $this->dbms->escapeSQL()
     */
    public function escapeSQL($string)
    {
        return $this->dbms->escapeSQL($string);
    }

    /**
     * Wrapper for $this->dbms->escapeDbIdentifier()
     */
    public function escapeDbIdentifier($string)
    {
        return $this->dbms->escapeDbIdentifier($string);
    }

    /**
     * Wrapper for $this->dbms->errorDuplicateEntry()
     */
    public function errorDuplicateEntry()
    {
        return $this->dbms->errorDuplicateEntry();
    }

    /**
     * Wrapper for $this->dbms->checkIntervalFormat()
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
     * @param mixed $noChangeMessage optional message in case of no affected change
     *   false = use $successMessage
     *
     * @return bool|null true for success, false for failure of the query;
     *   if the query is empty return null (with no messages)
     */
    public function resolveSQL($sql, $successMessage, $errorMessage, $noChangeMessage = false)
    {
        if (!$sql) {
            return null;
        }
        if ($result = $this->dbms->query($sql)) {
            $insertId = $affectedRows = '';
            if (preg_match('/^\s*INSERT\s/i', $sql)) {
                $insertId = $this->dbms->insert_id;
            }
            $affectedRows = $this->dbms->affected_rows;
            $message = $affectedRows == 0 && $noChangeMessage !== false ? $noChangeMessage : $successMessage;
            Tools::addMessage('success', strtr($message, ['%insertId%' => $insertId, '%affectedRows%' => $affectedRows]));
        } else {
            Tools::addMessage('error', strtr($errorMessage, ['%error%' => $this->dbms->error, '%errno%' => $this->dbms->errno]));
        }
        return $result;
    }

    /**
     * Return text translated according to $this->TRANSLATION[]. Return original text, if translation is not found.
     * If the text differs only by case of the first letter, return its translation and change the case of its first letter.
     * @example: TRANSLATION['List'] = 'Seznam'; $this->translate('List') --> "Seznam", $this->translate('list') --> "seznam"
     * @example: TRANSLATION['list'] = 'seznam'; $this->translate('list') --> "seznam", $this->translate('List') --> "Seznam"
     *
     * @param string $text
     * @param bool $escape escape for HTML? true by default
     * @param int $changeCase - 0 = no change, 1 = first upper, -1 = first lower, 2 = all caps, -2 = all lower
     * @param string $encoding or null (default) for mb_internal_encoding()
     * @return string
     */
    public function translate($text, $escape = true, $changeCase = 0, $encoding = null)
    {
        $encoding = $encoding ?: mb_internal_encoding();
        $first = mb_substr($text, 0, 1, $encoding);
        $rest = mb_substr($text, 1, null, $encoding);
        if (isset($this->TRANSLATION[$text])) {
            $text = $this->TRANSLATION[$text];
        } else {
            $ucfirst = mb_strtoupper($first, $encoding);
            $lcfirst = mb_strtolower($first, $encoding);
            if (isset($this->TRANSLATION[$ucfirst . $rest])) {
                $text = $this->TRANSLATION[$ucfirst . $rest];
                $changeCase = 1;
            } elseif (isset($this->TRANSLATION[$lcfirst . $rest])) {
                $text = $this->TRANSLATION[$lcfirst . $rest];
                $changeCase = -1;
            } elseif (isset($this->TRANSLATION[mb_strtoupper($text, $encoding)])) {
                $text = $this->TRANSLATION[mb_strtoupper($text, $encoding)];
                $changeCase = 2;
            } elseif (isset($this->TRANSLATION[mb_strtolower($text, $encoding)])) {
                $text = $this->TRANSLATION[mb_strtolower($text, $encoding)];
                $changeCase = -2;
            }
        }
        if ($changeCase) {
            $fn = $changeCase > 0 ? 'mb_strtoupper' : 'mb_strtolower';
            $text = $fn($first, $encoding) . (abs($changeCase) > 1 ? $fn($rest, $encoding) : $rest);
        }
        return $escape ? Tools::h($text) : $text;
    }

    /**
     * Translate name of a column - defined in translations as "column:<name of the column>"
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
     * @param array $record
     * @return bool - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customInput($field, $value, array $record = [])
    {
        return false;
    }

    /**
     * Custom HTML showed before particular field (but after its label).
     *
     * @param string $field
     * @param string $value
     * @param array $record
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
     * @param string $value
     * @param array $record
     * @return string HTML
     */
    public function customInputAfter($field, $value, array $record = [])
    {
        return '';
    }

    /**
     * Custom HTML to be show after detail's edit form but before action buttons
     *
     * @param array $record
     * @return string
     */
    public function customRecordDetail($record)
    {
        return '';
    }

    /**
     * Custom HTML to be show after standard action buttons of the detail's form
     *
     * @param array $record
     * @return string
     */
    public function customRecordActions($record)
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
     * @param array $row
     * @return mixed original or manipulated data
     */
    public function customValue($column, array $row)
    {
        return isset($row[$column]) ? $row[$column] : false;
    }

    /**
     * Display a break-down of records in given table (default "content") by given column (default "type")
     *
     * @param array $options OPTIONAL
     * @return string
     */
    public function contentByType(array $options = [])
    {
        Tools::setifnull($options['table'], 'content');
        Tools::setifnull($options['type'], 'type');
        $query = $this->dbms->query('SELECT SQL_CALC_FOUND_ROWS ' . Tools::escapeDbIdentifier($options['type']) . ',COUNT(' . Tools::escapeDbIdentifier($options['type']) . ')'
            . ' FROM ' . Tools::escapeDbIdentifier(TAB_PREFIX . $options['table'])
            . ' GROUP BY ' . Tools::escapeDbIdentifier($options['type']) . ' WITH ROLLUP LIMIT 100');
        if (!$query) {
            // TODO fix Method GodsDev\MyCMS\MyTableLister::contentByType() should return string but empty return statement found.
            return;
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
            . '<tr><th>' . $this->translate('Type') . '</th><th class="text-right">' . $this->translate('Count') . '</th></tr>' . PHP_EOL;
        while ($row = $query->fetch_row()) {
            $output .= '<tr><td><a href="' . ($url = '?table=' . urlencode(TAB_PREFIX . $options['table']) . '&amp;col[0]=' . $typeIndex . '&amp;op[0]=0&amp;val[0]=' . urlencode($row[0])) . '" title="' . $this->translate('Filter records') . '">' . ($row[0] ? Tools::h($row[0]) : ($row[0] === '' ? '<i class="insipid">(' . $this->translate('empty') . ')</i>' : '<big>&Sum;</big>')) . '</a>'
                . '</td><td class="text-right"><a href="' . $url . '" title="' . $this->translate('Filter records') . '">' . (int) $row[1] . '</td></tr>' . PHP_EOL;
        }
        $output .= '</table></details>';
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
        // TODO fix Method GodsDev\MyCMS\MyTableLister::contentByType() should return string but return statement is missing.
    }

    public function decodeChoiceOptions($list)
    {
        return $this->dbms->decodeChoiceOptions($list);
    }

    /**
     * Return keys to current table of a specified type(s)
     *
     * @param array $types type(s) - possible items: PRI, UNI, MUL (database specific)
     * @return array filtered keys, e.g. ['id'=>'PRI', 'division'=>'MUL', 'document_id'=>'UNI']
     */
    public function filterKeys($types)
    {
        if (!is_array($types) || func_num_args() > 1) {
            $types = func_get_args();
        }
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
     * @param array $row
     * @retun array URL fragment identifying current row, e.g. "where[id]=5"
     */
    public function rowLink($row)
    {
        $result = [];
        // todo fix Parameter #1 $types of method GodsDev\MyCMS\MyTableLister::filterKeys() expects array, string given.
        if ($keys = $this->filterKeys('PRI')) {
            $result [] = 'where[' . urlencode(array_keys($keys)[0]) . ']=' . urlencode(Tools::set($row[array_keys($keys)[0]]));
        // todo fix Parameter #1 $types of method GodsDev\MyCMS\MyTableLister::filterKeys() expects array, string given.
        } elseif ($keys = $this->filterKeys('UNI')) {
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
                // todo fix Strict comparison using === between mixed and null will always evaluate to false.
                if ($row[$key] === null) {
                    $result [] = 'null[' . urlencode($key) . ']=';
                } else {
                    $result [] = 'where[' . urlencode($key) . ']=' . urlencode($row[$key]);
                }
            }
        }
        return $result;
    }
}
