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

    /** @var \mysqli database management system */
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
    public $WHERE_OPS = array(
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
    );

    /** @var array factory setting defaults */
    protected $DEFAULTS = array(
        'PAGESIZE' => 100,
        'MAXPAGESIZE' => 10000,
        'MAXSELECTSIZE' => 10000,
        'TEXTSIZE' => 100,
        'PAGES_AROUND' => 2, // used in pagination
        'FOREIGNLINK' => '-link' //suffix added to POST variables for links
    );

    /** @var array Selected locale strings */
    public $TRANSLATION = array(
    );

    /** @var array Available languages for MyCMS */
    public $TRANSLATIONS = array(
        'en' => 'English'
    ); 

    /** @var array possible table settings, stored in its comment */
    public $tableContext = null;

    /**
     * Constructor - stores passed parameters to object's attributes
     * 
     * @param \mysqli database management system already connected to wanted database
     * @param string table to view
     * @param array options
     */
    function __construct(\mysqli $dbms, $table, array $options = array())
    {
        $this->dbms = $dbms;
        $this->options = $options;
        $this->database = $this->dbms->fetchSingle('SELECT DATABASE()');
        $this->getTables();
        $this->setTable($table);
        $this->rand = rand(1e5, 1e6 - 1);
    }

    /**
     * Get all tables in the database (including comments) and store them to tables
     *
     * @return void
     */
    public function getTables()
    {
        $this->tables = array();
        $query = $this->dbms->query('SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES ' //@todo database-specific
                . 'WHERE TABLE_SCHEMA = "' . $this->escapeSQL($this->database) . '"');
        while ($row = $query->fetch_row()) {
            $this->tables[$row[0]] = $row[1];
        }
    }

    /**
     * Set (or change) serviced table, get its fields.
     *
     * @param string $table table name
     * @param bool $force do force reload if the current and desired table names are the same
     * @return void
     */
    public function setTable($table, $forceReload = false)
    {
        if ($this->table && $this->table == $table && !$forceReload) {
            return;
        }
        $this->fields = array();
        if (!in_array($table, array_keys($this->tables))) {
            return;
        }
        $this->table = $table;
        if ($query = $this->dbms->query('SHOW FULL COLUMNS IN ' . $this->escapeDbIdentifier($this->table))) {
            $result = array();
            while ($row = $query->fetch_assoc()) {
                $item = array(
                    'type' => $row['Type'],
                    'null' => strtoupper($row['Null']) == 'YES',
                    'default' => $row['Default'],
                    'size' => null,
                    'key' => $row['Key'],
                    'comment' => $row['Comment'],
                    'basictype' => $row['Type']
                );
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
            throw RunTimeException('Could not get columns from table ' . $this->table . '.');
        }
        if ($query = $this->dbms->query('SELECT COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_NAME != "PRIMARY" AND CONSTRAINT_CATALOG = "def" 
            AND TABLE_SCHEMA = "' . $this->escapeSQL($this->database) . '" 
            AND TABLE_NAME = "' . $this->escapeSQL($this->table) . '"')) {
            while ($row = $query->fetch_assoc()) {
                $this->fields[$row['COLUMN_NAME']]['foreign_table'] = $row['REFERENCED_TABLE_NAME'];
                $this->fields[$row['COLUMN_NAME']]['foreign_column'] = $row['REFERENCED_COLUMN_NAME'];
            }
        }
        $tmp = $this->dbms->fetchSingle('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA="' . $this->escapeSQL($this->database) . '" AND TABLE_NAME="' . $this->escapeSQL($this->table) . '"');
        $this->tableContext = json_decode($tmp, true) or array();
    }

    /**
     * Compose a SQL statement
     *
     * @param array &$get $_GET
     * @return array with these indexes: [join], [where], [sort], [sql]
     */
    public function composeSQL($columns, &$get)
    {
        $result = array('join' => '', 'where' => '', 'order by' => '', 'sql' => '');
        $result['limit'] = isset($get['limit']) && $get['limit'] ? (int) $get['limit'] : $this->DEFAULTS['PAGESIZE'];
        if ($result['limit'] < 1 || $result['limit'] > $this->DEFAULTS['MAXPAGESIZE']) {
            $result['limit'] = $this->DEFAULTS['PAGESIZE'];
        }
        $result['offset'] = max(isset($get['offset']) ? (int) $get['offset'] : 0, 0);
        foreach ($columns as $key => $value) {
            if (isset($this->fields[$key]['foreign_table']) && $this->fields[$key]['foreign_table']) {
                $result['join'] .= ' LEFT JOIN ' . $this->fields[$key]['foreign_table']
                    . ' ON ' . $this->table . '.' . $key
                    . '=' . $this->fields[$key]['foreign_table'] . '.' . $this->fields[$key]['foreign_column'];
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
        if (isset($get['col']) && is_array($get['col'])) {
            $filterColumn = array('');
            foreach ($columns as $key => $value) {
                $filterColumn [] = $key;
            }
            unset($filterColumn[0]);
            foreach ($get['col'] as $key => $value) {
                if (isset($filterColumn[$value], $get['val'][$key])) {
                    $id = $this->escapeDbIdentifier($this->table) . '.' . $this->escapeDbIdentifier($filterColumn[$value]);
                    $val = $get['val'][$key];
                    $op = $this->WHERE_OPS[$get['op'][$key]];
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
        foreach (Tools::setifempty($get['order by'], array()) as $key => $value) {
            if (isset(array_keys($columns)[(int) $value - 1])) {
                $result['order by'] .= ',' . array_values($columns)[(int) $value - 1] . (isset($get['desc'][$key]) && $get['desc'][$key] ? ' DESC' : '');
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
     * Create array of columns for preparing the SQL statement
     *
     * @param array $options
     * @return array
     */
    public function getColumns($options)
    {
        $columns = array();
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
                unset($columns[$column]);
            }
        }
        return $columns;
    }

    /**
     * Output a customizable table to browse, search, page and pick its items for editing
     *
     * @param options configuration array
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
     * @return void or string (for $options['return-output'])
     */
    public function view(array $options = array())
    {
        foreach (array('read-only', 'no-search', 'no-sort', 'no-toggle', 'no-display-options', 'no-multi-options', 'no-selected-rows-operations') as $i) {
            Tools::setifempty($options[$i]);
        }
        // find out what columns to include/exclude
        if (!($columns = $this->getColumns($options))) {
            return;
        }
        $sql = $this->composeSQL($columns, $_GET);
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
            $output .=  '<p class="alert alert-danger"><small>' . $this->translate('No records found.') . '</small></p>';
        } else {
            $output .=  '<p class="text-info"><small>' . $this->translate('Total rows: ') . $options['total-rows'] . '.</small></p>';
        }
        if (isset($options['return-output']) && $options['return-output']) {
            return $output;
        }
        echo $output;
    }

    /**
     * Part of the view() method to output the controls.
     *
     * @param array options as in view()
     * @return void or string (for $options['return-output'])
     */
    protected function viewInputs($options)
    {
        $output = '<form action="" method="get" class="table-controls" data-rand="' . $this->rand . '">' . PHP_EOL;
        if (!Tools::set($option['no-toggle'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#toggle-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-list-alt"></span> ' . $this->translate('Columns') . '</a></legend>
                <div class="toggle-div" id="toggle-div' . $this->rand . '" data-rand="' . $this->rand . '">
                <div class="btn-group-toggle btn-group-sm" data-toggle="buttons">';
            foreach (array_keys($this->fields) as $key => $value) {
                $output .= '<label class="btn btn-light column-toggle active" title="' . $this->translateColumn($value) . '">' 
                    . Tools::htmlInput('', '', '', array('type' => 'checkbox', 'checked' => true, 'autocomplete' => 'off', 'data-column' => $key + 1)) 
                    . Tools::h($value) . '</label>' . PHP_EOL;
            }
            $output .= '</div></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($option['no-search'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#search-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-search fa fa-search"></span> ' . $this->translate('Search') . '</a></legend>
                <div class="search-div" id="search-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        if (!Tools::set($option['no-sort'])) {
            $output .= '<fieldset><legend><a href="javascript:;" onclick="$(\'#sort-div' . $this->rand . '\').toggle()">
                <span class="glyphicon glyphicon-sort fa fa-sort mx-1"></span> ' . $this->translate('Sort') . '</a></legend>
                <div class="sort-div" id="sort-div' . $this->rand . '"></div></fieldset>' . PHP_EOL;
        }
        $output .= '<fieldset><legend><span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span> ' . $this->translate('View') . '</legend>
            ' . Tools::htmlInput('table', '', $this->table, 'hidden') . '
            <label title="' . $this->translate('Text lengths') . '"><span class="glyphicon glyphicon-option-horizontal fa fa-ellipsis-h mx-1"></span>' 
                . Tools::htmlInput('textsize', '', Tools::setifnull($_GET['textsize'], $this->DEFAULTS['TEXTSIZE']), array('size' => 3, 'class' => 'text-right')) . '
            </label>
            <label title="' . $this->translate('Rows per page') . '"><span class="glyphicon glyphicon-option-vertical fa fa-ellipsis-v mx-1"></span>' 
                . Tools::htmlInput('limit', '', Tools::setifnull($_GET['limit'], $this->DEFAULTS['PAGESIZE']), array('size' => 3, 'class' => 'text-right')) . '
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
        Tools::setifnull($_GET['sort'], array());
        Tools::setifnull($_GET['desc'], array());
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
     * @param object mysqli query
     * @param array columns selected columns
     * @param array options as in view()
     * @return void or string (for $options['return-output'])
     */
    protected function viewTable($query, array $columns, array $options)
    {
        Tools::setifnull($_GET['sort']);
        $output = '<form action="" method="post" enctype="multipart/form-data">' . PHP_EOL
            . '<table class="table table-bordered table-striped table-admin" data-order="0" id="table-admin' . $this->rand . '">' . PHP_EOL 
            . '<thead><tr>' . ($options['no-multi-options'] ? '' : '<th>' . Tools::htmlInput('', '', '', array('type' => 'checkbox', 'class' => 'check-all', 'title' => $this->translate('Check all'))) . '</th>');
        $i = 1;
        foreach ($columns as $key => $value) {
            $output .= '<th' . (count($_GET['sort']) == 1 && $_GET['sort'][0] == $i ? ' class="active"' : '') . '>'
                . '<div class="column-menu"><a href="?' . Tools::urlChange(array('sort%5B0%5D' => null)) . '&amp;sort%5B0%5D=' . ($i * ($_GET['sort'] == $i ? -1 : 1)) . '" title="' . $this->translateColumn($key) . '">' . Tools::h($key) . '</a>'
                . '<span class="op"><a href="?' . Tools::urlChange(array('sort%5B0%5D' => null)) . '&amp;sort%5B0%5D=' . ($i * ($_GET['sort'] == $i ? -1 : 1)) . '&amp;desc[0]=1" class="desc ml-1 px-1"><i class="fas fa-long-arrow-alt-down"></i></a>'
                . '<a href="javascript:addSearchRow($(\'#search-div' . $this->rand . '\'), ' . $i . ', 0, \'\')" class="filter px-1">=</a></span></div>'
                . '</th>' . PHP_EOL;
            $i++;
        }
        $output .= '</tr></thead><tbody>';
        if (is_object($query)) {
            for ($i = 0; $row = $query->fetch_assoc(); $i++) {
                $output .= '<tr><td' . ($options['no-multi-options'] ? '' : ' class="multi-options"') . '>';
                $url = $this->rowLink($row);
                if (!$options['no-multi-options']) {
                    $value = '';
                    $output .= Tools::htmlInput('check[]', '', implode('&', $url), array('type' => 'checkbox', 'data-order' => $i));
                }
                $output .= '<a href="?table=' . urlencode($this->table) . '&amp;' . implode('&', $url) . '" title="' . $this->translate('Edit') . '">'
                . '<small class="glyphicon glyphicon-edit fa fa-pencil fa-edit" aria-hidden="true"></small></a>';
                $output .= '</td>';
                foreach ($row as $key => $value) {
                    if (Tools::ends($key, $this->DEFAULTS['FOREIGNLINK'])) {
                        continue;
                    }
                    $field = (array) $this->fields[$key];
                    $class = array();
                    if (isset($field['foreign_table'])) {
                        $tmp = '<a href="?' . Tools::urlChange(array('table' => $field['foreign_table'], 'where[id]' => $value)) . '" '
                                . 'title="' . Tools::h(mb_substr($row[$key . $this->DEFAULTS['FOREIGNLINK']], 0, $this->DEFAULTS['TEXTSIZE']) . (mb_strlen($row[$key . $this->DEFAULTS['FOREIGNLINK']]) > $this->DEFAULTS['TEXTSIZE'] ? '&hellip;' : '')) . '">'
                                . Tools::h($row[$key]) . '</a>';
                    } else {
                        switch ($field['basictype']) {
                            case 'integer':
                            case 'rational':
                                $class [] = 'text-right';
                            case 'text':
                            default:
                                $tmp = Tools::h(mb_substr($value, 0, $this->DEFAULTS['TEXTSIZE']));
                                break;
                        }
                    }
                    $output .= '<td' . Tools::wrap(implode(' ', $class), ' class="', '"') . '>'
                        . $tmp . '</td>' . PHP_EOL;
                }
                $output .= '</tr>' . PHP_EOL;
            }
        }
        $output .= '</tbody></table>' . PHP_EOL;
        if (!isset($options['no-selected-rows-operations'])) {
            $output .= '<div class="selected-rows mb-2"><i class="fa fa-check-square"></i>=<span class="listed">0</span> 
                <label class="btn btn-sm btn-light mx-1 mt-2">' . Tools::htmlInput('total-rows', '', $options['total-rows'], array('type' => 'checkbox', 'class' => 'total-rows')) . ' ' . $this->translate('Whole resultset') . '</label>
                <button name="table-export" value="1" class="btn btn-sm ml-1"><i class="fa fa-download"></i> ' . $this->translate('Export') . '</button>
                <button name="edit-selected" value="1" class="btn btn-sm ml-1"><i class="fa fa-edit"></i> ' . $this->translate('Edit') . '</button>
                <button name="table-clone" value="1" class="btn btn-sm ml-1"><i class="far fa-clone"></i> ' . $this->translate('Clone') . '</button>
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
            . '<a href="?' . Tools::urlChange(array('offset' => ($page - 1) * $rowsPerPage)) . '" class="page-link" ' . Tools::wrap($title, ' title="', '"') . '>'
            . Tools::ifnull($label, $page) . '</a></li>' . PHP_EOL;
    }

    /**
     * Output pagination for a table
     *
     * @param int $rowsPerPage
     * @param int $totalRows
     * @param int $offset
     * @param array $options as in view()
     * @return void or string (for $options['return-output'])
     */
    public function pagination($rowsPerPage, $totalRows, $offset = null, $options = array())
    {
        $title = $this->translate('Go to page');
        if (is_null($offset)) {
            $offset = max(isset($_GET['offset']) ? (int) $_GET['offset'] : 0, 0);
        }
        $rowsPerPage = max($rowsPerPage, 1);
        $pages = ceil($totalRows / $rowsPerPage);
        $currentPage = floor($offset / $rowsPerPage) + 1;
        if ($pages <= 1) {
            return;
        }
        $output = '<nav><ul class="pagination"><li class="page-item disabled"><a name="" class="page-link go-to-page non-page" data-pages="' . $pages . '" tabindex="-1">' . $this->translate('Page') . ':</a></li>';
        if ($pages <= $this->DEFAULTS['PAGES_AROUND'] * 2 + 3) { // pagination with all pages
            if ($currentPage > 1) {
                $output .= $this->addPage($currentPage - 1, $currentPage, $rowsPerPage, $this->translate('Previous'), $title);
            }
            for ($page = 1; $page <= $pages; $page++) {
                $output .= $this->addPage($page, $currentPage, $rowsPerPage, null, $this->translate('Go to page'), $title);
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
     * @param string key type, either "PRI", "MUL", "UNI" or ""
     * @return array key names
     */
    public function fieldKeys($filterType)
    {
        $filterType = strtolower($filterType);
        $result = array();
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
     * @param string SQL to execute
     * @param string message in case of success
     * @param string message in case of an error
     * @param mixed optional message in case of no affected change
     *   false = use $successMessage
     * 
     * @return mixed true for success, false for failure of the query; if the query is empty return null (with no messages)
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
            Tools::addMessage('success', strtr($message, array('%insertId%' => $insertId, '%affectedRows%' => $affectedRows)));
        } else {
            Tools::addMessage('error', strtr($errorMessage, array('%error%' => $this->dbms->error, '%errno%' => $this->dbms->errno)));
        }
        return $result;
    }

    /**
     * Return text translated according to $this->TRANSLATION[]. Return original text, if translation is not found.
     * If the text differs only by case of the first letter, return its translation and change the case of its first letter.
     * @example: TRANSLATION['List'] is defined 'Seznam'. $this->translate('List') --> "Seznam", $this->translate('list') --> "seznam"
     * note: non-multi-byte functions are used so the first letter's case changing applies only to A-Z, a-z.
     * 
     * @param string $text
     * @param bool $escape escape for HTML?
     * @return string
     */
    public function translate($text, $escape = true)
    {
        $ucfirst = strtoupper($first = substr($text, 0, 1));
        if (isset($this->TRANSLATION[$text])) {
            $text = $this->TRANSLATION[$text];
        } elseif ($ucfirst >= 'A' && $ucfirst <= 'Z' && isset($this->TRANSLATION[$altText = ($first == $ucfirst ? strtolower($first) : $ucfirst) . substr($text, 1)])) {
            $text = $this->TRANSLATION[$altText];
            $text = ($first == $ucfirst ? strtoupper(substr($text, 0, 1)) : strtolower(substr($text, 0, 1))) . substr($text, 1);
        }
        return $escape ? Tools::h($text) : $text;
    }

    public function translateColumn($column, $escape = true)
    {
        $result = $this->translate("column:$column");
        return $result == "column:$column" ? $column : $result; 
    }

    // custom methods - meant to be rewritten in the class' children
    
    /**
     * Custom HTML instead of standard field's input
     * 
     * @param string $field
     * @param mixed $value field's value
     * @param array $record
     * @return boolean - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customInput($field, $value, array $record = array())
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
    public function customInputBefore($field, $value, array $record = array())
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
    public function customInputAfter($field, $value, array $record = array())
    {
        return '';
    }

    /**
     * Custom HTML to be show after detail's edit form but before action buttons
     *
     * @param array @record
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
     * @return boolean - true = method was applied so don't proceed with the default, false = method wasn't applied
     */
    public function customSave()
    {
        return false;
    }

    /**
     * Custom event after deleting of a record
     *
     * @return boolean success
     */
    public function customAfterDelete()
    {
        return true;
    }

    /**
     * Custom operation with table records. Called after the $table listing
     * 
     * @return boolean - true = method was applied so don't proceed with the default, false = method wasn't applied
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
    }

    /**
     * Called to optionally fill conditions to WHERE clause of the SQL statement selecting given table
     * @return void
     */
    public function customCondition()
    {
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
    public function contentByType(array $options = array())
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
        $result = array();
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
        $result = array();
        if ($keys = $this->filterKeys('PRI')) {
            $result []= 'where[' . urlencode(array_keys($keys)[0]) . ']=' . urlencode(Tools::set($row[array_keys($keys)[0]]));
        } elseif ($keys = $this->filterKeys('UNI')) {
            foreach ($keys as $key => $value) {
                if (isset($row[$key]) && $row[$key] !== null) {
                    $result []= 'where[' . urlencode($key) . ']=' . urlencode($value);
                    break;
                } else {
                    $result []= 'null[' . urlencode($key) . ']=';
                }
            }
        }
        if (!$result) {
            foreach ($this->fields as $key => $field) {
                if (!isset($row[$key])) {
                    continue;
                }
                if ($row[$key] === null) {
                    $result []= 'null[' . urlencode($key) . ']=';
                } else {
                    $result []= 'where[' . urlencode($key) . ']=' . urlencode($row[$key]);
                }
            }
        }
        return $result;
    }
}
