<?php

namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;

/**
 * Class that can list rows of a database table, with editable search/filter 
 * functionality, links to edit each particular row, multi-row action, etc.
 * dependencies: Tools
 */
class TableLister
{  
    use \Nette\SmartObject;

    /** @var \mysqli database management system */
    protected $dbms;
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
        'LIKE',
        'REGEXP',
        'IN',
        'IS NULL',
        'BIT AND'
    );
    /** @var array factory setting defaults */
    protected $DEFAULTS = array(
        'PAGESIZE' => 10,
        'MAXPAGESIZE' => 10000,
        'TEXTSIZE' => 100,
        'PAGES_AROUND' => 2, // used in pagination
        'FOREIGNLINK' => '-link' //suffix added to POST variables for links
    );

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
        $this->table = $table;
        $this->options = (array) $options;
        if ($query = $this->dbms->query('SELECT DATABASE()')) {
            $this->options['database'] = $query->fetch_row()[0];
        }
        $this->getTables();
        $this->setTable($table);
        $this->rand = rand(1e5, 1e6 - 1);
    }

    /** 
     * Set (or change) serviced table, get its fields.
     * @param string $table table name
     * 
     * @return void
     */
    public function setTable($table)
    {
        $this->fields = array();
        if (!in_array($table, array_keys($this->tables))) {
            return;
        }
        $this->table = $table;
        if ($query = $this->dbms->query('SHOW FULL COLUMNS IN ' . Tools::escapeDbIdentifier($this->table))) {
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
            AND TABLE_SCHEMA = "' . $this->escape($this->options['database']) . '" 
            AND TABLE_NAME = "' . $this->escape($this->table) . '"')) {
            while ($row = $query->fetch_assoc()) {
                $this->fields[$row['COLUMN_NAME']]['foreign_table'] = $row['REFERENCED_TABLE_NAME'];
                $this->fields[$row['COLUMN_NAME']]['foreign_column'] = $row['REFERENCED_COLUMN_NAME'];
            }
        }
    }

    /**
     * Output a customizable table to browse, search, page and pick its items for editing
     * @param options configuration Array
     *  $options['form-action']=send.php - instead of <form action="">
     *  $options['read-only']=non-zero - no links to admin 
     *  $options['no-sort']=non-zero - don't offer 'sorting' option  
     *  $options['no-search']=non-zero - don't offer 'search' option  
     *  $options['no-display-options']=non-zero - don't offer 'display' option  
     *  $options['no-multi-options']=non-zero - allow to change values via so called quick column
     *  $options['include']=array - columns to include 
     *  $options['exclude']=array - columns to exclude
     *  $options['columns']=array - special treatment of columns
     */
    public function view(array $options = array())
    {
        $limit = isset($_GET['limit']) && $_GET['limit'] ? (int) $_GET['limit'] : $this->DEFAULTS['PAGESIZE'];
        if ($limit < 1 || $limit > $this->DEFAULTS['MAXPAGESIZE']) {
            $limit = $this->DEFAULTS['PAGESIZE'];
        }
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        if ($offset < 0) {
            $offset = 0;
        }
        foreach (array('read-only', 'no-search', 'no-sort', 'no-display-options', 'no-multi-options') as $i) {
            Tools::setifempty($options[$i]);
        }
        // find out what columns to include/exclude
        $columns = array();
        if (isset($options['include']) && is_array($options['include'])) {
            foreach ($temp as $column) {
                if (in_array($column, array_keys($this->fields))) {
                    $columns[$column] = Tools::escapeDbIdentifier($column);
                }
            }
        }
        if (!$columns) {
            foreach ($this->fields as $key => $value) {
                $columns[$key] = Tools::escapeDbIdentifier($key);
            }
        }
        if (isset($options['exclude']) && is_array($options['exclude'])) {
            foreach ($options['exclude'] as $column) {
                if (isset($columns[$column])) {
                    unset($columns[$column]);
                }
            }
        }
        if (!$columns) {
            return;
        }
        // compose the SQL
        $join = '';
        $primary = '';
        $where = '';
        $sort = '';
        foreach ($columns as $key => $value) {
            if ($this->fields[$key]['key'] == 'PRI') {
                $primary = $key;
            }
            if (isset($this->fields[$key]['foreign_table']) && $this->fields[$key]['foreign_table']) {
                $join .= ' LEFT JOIN ' . $this->fields[$key]['foreign_table']
                        . ' ON ' . $this->table . '.' . $key
                        . '=' . $this->fields[$key]['foreign_table'] . '.' . $this->fields[$key]['foreign_column'];
                // try if column of the same name as the table exists (as a replacement for foreign table); use the first field in the table if it doesn't exist 
                $tmp = $this->dbms->query('SHOW FIELDS FROM ' . Tools::escapeDbIdentifier($this->fields[$key]['foreign_table']))->fetch_all();
                foreach ($tmp as $k => $v) {
                    $tmp[$v[0]] = $v[0];
                    unset($tmp[$k]);
                }
                $foreign_link = mb_substr($this->fields[$key]['foreign_table'], mb_strlen(TAB_PREFIX));
                $foreign_link = isset($tmp[$foreign_link]) && $foreign_link ? $foreign_link : reset($tmp);
                $columns[$key] = Tools::escapeDbIdentifier($this->table) . '.' . $value . ','
                        . Tools::escapeDbIdentifier($this->fields[$key]['foreign_table']) . '.'
                        . Tools::escapeDbIdentifier($foreign_link) . ' AS ' . Tools::escapeDbIdentifier($key . $this->DEFAULTS['FOREIGNLINK']);
            }
        }
        if ($join) {
            foreach ($columns as $key => $value) {
                if ($value == Tools::escapeDbIdentifier($key)) {
                    $columns[$key] = Tools::escapeDbIdentifier($this->table) . '.' . $value;
                }
            }
        }
        if (isset($_GET['col']) && is_array($_GET['col'])) {
            $filterColumn = array('');
            foreach ($columns as $key => $value) {
                $filterColumn [] = $key;
            }
            unset($filterColumn[0]);
            foreach ($_GET['col'] as $key => $value) {
                if (isset($filterColumn[$value], $_GET['val'][$key])) {
                    $where .= ' AND ';
                    switch ($_GET['op'][$key]) {
                        default:
                            $where .= Tools::escapeDbIdentifier($this->table) . '.' . Tools::escapeDbIdentifier($filterColumn[$value])
                                    . '="' . $this->escape($_GET['val'][$key]) . '"';
                    }
                }
            }
        }
        foreach (Tools::setifempty($_GET['sort'], array()) as $key => $value) {
            if (isset(array_keys($columns)[(int) $value - 1])) {
                $sort .= ',' . array_values($columns)[(int) $value - 1] . (isset($_GET['desc'][$key]) && $_GET['desc'][$key] ? ' DESC' : '');
            }
        }
        $sql = 'SELECT SQL_CALC_FOUND_ROWS ' . implode(',', $columns) . ' FROM '
                . Tools::escapeDbIdentifier($this->table) . $join
                . Tools::wrap(substr($where, 4), ' WHERE ')
                . Tools::wrap(substr($sort, 1), ' ORDER BY ')
                . " LIMIT $offset, $limit";
        $query = $this->dbms->query($sql);
        $totalRows = $this->dbms->query('SELECT FOUND_ROWS()')->fetch_row()[0];
        if (!$options['read-only']) {
            echo '<a href="?table=' . urlencode($this->table) . '&amp;where[]="><span class="glyphicon glyphicon-plus fa fa-plus-circle" /></span> ' . $this->translate('New row') . '</a>' . PHP_EOL;
        }
        $this->viewInputs($options);
        if ($totalRows) {
            $this->viewTable($query, $columns, $options);
            $this->pagination($limit, $totalRows);
        }
        if (!$totalRows && isset($_GET['col'])) {
            echo '<p class="alert alert-danger"><small>' . $this->translate('No records found.') . '</small></p>';
        } else {
            echo '<p class="text-success"><small>' . $this->translate('Total rows: ') . $totalRows . '.</small></p>';
        }
    }

    /** 
     * Part of the view() method to output the controls
     * @param array option same as in view()
     */
    protected function viewInputs($options)
    {
        echo '<form action="" method="get" class="table-controls">' . PHP_EOL;
        if (!isset($option['no-search']) || !$option['no-search']) {
            echo '<fieldset><legend><a href="javascript:;" onclick="$(\'#search-div\').toggle()">'
            . '<span class="glyphicon glyphicon-search fa fa-search"></span> ' . $this->translate('Search') . '</a></legend>'
            . '<div id="search-div"></div></fieldset>' . PHP_EOL;
        }
        if (!isset($option['no-sort']) || !$option['no-sort']) {
            echo '<fieldset><legend><a href="javascript:;" onclick="$(\'#sort-div\').toggle()">'
            . '<span class="glyphicon glyphicon-sort fa fa-sort"></span> ' . $this->translate('Sort') . '</a></legend>'
            . '<div id="sort-div"></div></fieldset>' . PHP_EOL;
        }
        echo '<fieldset><legend><span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span> ' . $this->translate('View') . '</legend>
            <input type="hidden" name="table" value="' . Tools::h($this->table) . '" />
            <label title="' . $this->translate('Text lengths') . '">
                <span class="glyphicon glyphicon-option-horizontal fa fa-ellipsis-h"></span>
                ' . Tools::htmlInput('textsize', '', Tools::setifnull($_GET['textsize'], $this->DEFAULTS['TEXTSIZE']), array('size' => 3, 'class' => 'text-right')) . '
            </label>
            <label title="' . $this->translate('Rows per page') . '">
                <span class="glyphicon glyphicon-option-vertical fa fa-ellipsis-v"></span>
                ' . Tools::htmlInput('limit', '', Tools::setifnull($_GET['limit'], $this->DEFAULTS['PAGESIZE']), array('size' => 3, 'class' => 'text-right')) . '
            </label>
            ' . Tools::htmlInput('offset', '', Tools::setifnull($_GET['offset'], 0), 'hidden') . '
            <button type="submit" class="btn btn-sm" title="' . $this->translate('View') . '"/>
                <span class="glyphicon glyphicon-list-alt fa fa-list-alt"></span>
            </button>
            </fieldset></form>
            <script type="text/javascript"> 
            LISTED_FIELDS=[' . Tools::arrayListed(array_keys($this->fields), 4, ',', '"', '"') . '];' . PHP_EOL;
        if (isset($_GET['col'], $_GET['op']) && is_array($_GET['col'])) {
            foreach ($_GET['col'] as $key => $value) {
                if ($value) {
                    $this->script .= 'addSearchRow("' . Tools::escapeJs($value) . '",' . Tools::setifnull($_GET['op'][$key], 0) . ', "' . addslashes(Tools::setifnull($_GET['val'][$key], '')) . '");' . PHP_EOL;
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
                    $this->script .= 'addSortRow("' . Tools::escapeJs($value) . '",' . (isset($_GET['desc']) && $_GET['desc'] ? 'true' : 'false') . ');' . PHP_EOL;
                } else {
                    unset($_GET['sort'][$key], $_GET['desc'][$key]);
                }
            }
        }
        if (!isset($_GET['sort']) || !$_GET['sort']) {
            $this->script .= "$('#sort-div').hide();" . PHP_EOL;
        }
        if (!isset($_GET['col']) || !$_GET['col']) {
            $this->script .= "$('#search-div').hide();" . PHP_EOL;
        }
        $this->script .= 'addSortRow(null, false);' . PHP_EOL
                . 'addSearchRow(null, 0, "");' . PHP_EOL;
        echo '</script>' . PHP_EOL;
    }

    /** 
     * Part of the view() method to output the content of selected table
     * 
     * @param object mysqli query
     * @param array columns selected columns
     * @param array options same as in view()
     */
    protected function viewTable($query, array $columns, array $options)
    {
        Tools::setifnull($_GET['sort']);
        echo '<form action="" method="post">' . PHP_EOL
        . '<table class="table table-bordered table-striped table-admin" data-order="0">'
        . PHP_EOL . '<thead><tr>' . ($options['no-multi-options'] ? '' : '<th>' . Tools::htmlInput('', '', '', array('type' => 'checkbox', 'class' => 'check-all', 'title' => $this->translate('Check all'))) . '</th>');
        $i = 1;
        $primary = array();
        foreach ($columns as $key => $value) {
            echo '<th' . (count($_GET['sort']) == 1 && $_GET['sort'][0] == $i ? ' class="active"' : '') . '>'
            . '<a href="?' . Tools::urlchange(array('sort%5B0%5D' => null)) . '&amp;sort%5B0%5D=' . ($i * ($_GET['sort'] == $i ? -1 : 1)) . '" title="' . $this->translate('Sort') . '">' . Tools::h($key) . '</a>'
            . '</th>' . PHP_EOL;
            if ($this->fields[$key]['key'] == 'PRI') {
                $primary [] = $key;
            }
            $i++;
        }
        echo '</tr></thead><tbody>';
        if (is_object($query)) {
            for ($i = 0; $row = $query->fetch_assoc(); $i++) {
                echo '<tr><td' . ($options['no-multi-options'] ? '' : ' class="multi-options"') . '>';
                $url = '';
                foreach ($primary as $field) {
                    $url .= '&where[' . urlencode($field) . ']=' . urlencode($row[$field]);
                }
                if (!$options['no-multi-options']) {
                    $value = '';
                    echo Tools::htmlInput('check[]', '', mb_substr($url, 1), array('type' => 'checkbox', 'data-order' => $i));
                }
                if ($primary) {
                    echo '<a href="?table=' . urlencode($this->table) . Tools::h($url) . '" title="' . $this->translate('Edit') . '">'
                    . '<small class="glyphicon glyphicon-edit fa fa-pencil" aria-hidden="true"></small></a>';
                }
                echo'</td>';
                foreach ($row as $key => $value) {
                    if (Tools::ends($key, $this->DEFAULTS['FOREIGNLINK'])) {
                        continue;
                    }
                    $field = (array) $this->fields[$key];
                    $class = array();
                    if (isset($field['foreign_table'])) {
                        $output = '<a href="?' . Tools::urlChange(array('table' => $field['foreign_table'], 'where[id]' => $value)) . '" '
                                . 'title="' . Tools::h(mb_substr($row[$key . $this->DEFAULTS['FOREIGNLINK']], 0, $this->DEFAULTS['TEXTSIZE']) . (mb_strlen($row[$key . $this->DEFAULTS['FOREIGNLINK']]) > $this->DEFAULTS['TEXTSIZE'] ? '…' : '')) . '">'
                                . Tools::h($row[$key]) . '</a>';
                    } else {
                        switch ($field['basictype']) {
                            case 'integer':
                            case 'rational':
                                $class [] = 'text-right';
                            case 'text':
                            default:
                                $output = Tools::h(mb_substr($value, 0, $this->DEFAULTS['TEXTSIZE']));
                                break;
                        }
                    }
                    echo '<td' . Tools::wrap(implode(' ', $class), ' class="', '"') . '>'
                    . $output . '</td>' . PHP_EOL;
                }
                echo '</tr>' . PHP_EOL;
            }
        }
        echo '</tbody></table>' . PHP_EOL . '</form>';
    }

    private function addPage($page, $currentPage, $rowsPerPage, $label = null)
    {
        global $title;
        echo '<li' . ($page == $currentPage ? ' class="active"' : '') . '>'
        . '<a href="?' . Tools::urlChange(array('offset' => ($page - 1) * $rowsPerPage)) . '"' . Tools::wrap($title, ' title="', '"') . '>'
        . Tools::ifnull($label, $page) . '</a></li>' . PHP_EOL;
    }

    /**
     * 
     * @param int $rowsPerPage
     * @param int $totalRows
     * @param int $offset
     * 
     * @return type
     */
    public function pagination($rowsPerPage, $totalRows, $offset = null)
    {
        $title = $this->translate('Go to page');

        if (is_null($offset)) {
            $offset = max(isset($_GET['offset']) ? (int)$_GET['offset'] : 0, 0);
        }
        $rowsPerPage = max($rowsPerPage, 1);
        $pages = ceil($totalRows / $rowsPerPage);
        $currentPage = floor($offset / $rowsPerPage) + 1;
        if ($pages <= 1) {
            return;
        }
        echo '<nav><ul class="pagination"><li><a name="" class="go-to-page non-page" data-pages="' . $pages . '">' . $this->translate('Page') . ':</a></li>';
        if ($pages <= $this->DEFAULTS['PAGES_AROUND'] * 2 + 3) { // pagination with all pages
            if ($currentPage > 1) {
                $this->addPage($currentPage - 1, $currentPage, $rowsPerPage, $this->translate('Previous'));
            }
            for ($page = 1; $page <= $pages; $page++) {
                $this->addPage($page, $currentPage, $rowsPerPage, null, $this->translate('Go to page'));
            }
            if ($currentPage < $pages) {
                $this->addPage($currentPage + 1, $currentPage, $rowsPerPage, $this->translate('Next'));
            }
        } else { // pagination with first, current, last pages and "..."s in between
            if ($currentPage > 1) {
                $this->addPage($currentPage - 1, $currentPage, $rowsPerPage, $this->translate('Previous'));
            }
            $this->addPage(1, $currentPage, $rowsPerPage);
            echo $currentPage - $this->DEFAULTS['PAGES_AROUND'] > 2 ? '<li><a name="" class="non-page">…</a></li>' : '';
            for ($page = max($currentPage - $this->DEFAULTS['PAGES_AROUND'], 2); $page <= min($currentPage + $this->DEFAULTS['PAGES_AROUND'], $pages); $page++) {
                $this->addPage($page, $currentPage, $rowsPerPage);
            }
            echo $currentPage < $pages - $this->DEFAULTS['PAGES_AROUND'] - 1 ? '<li><a name="" class="non-page">…</a></li>' : '';
            if ($currentPage < $pages - $this->DEFAULTS['PAGES_AROUND']) {
                $this->addPage($pages, $currentPage, $rowsPerPage);
            }
            if ($currentPage < $pages) {
                $this->addPage($currentPage + 1, $currentPage, $rowsPerPage, $this->translate('Next'));
            }
        }
        echo '</ul></nav>' . PHP_EOL;
    }

    /** 
     * Return fields which are keys (indexes) of given type
     * @param string key type, either "PRI", "MUL", "UNI" or ""
     * 
     * @result array key names
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

    /** 
     * Shortcut for mysqli::real_escape_string($link, $str)
     * @param string string
     * 
     * @result string
     */
    public function escape($string)
    {
        return $this->dbms->real_escape_string($string);
    }

    /** 
     * Resolve an SQL query
     * @param string SQL to execute
     * @param string message in case of success
     * @param string error in case of an error
     * 
     * @return mixed true for success, false for failure of the query; if the query is empty return null (with no messages)
     */
    public function resolveSQL($sql, $success, $error)
    {
        if (!$sql) {
            return null;
        }
        if ($result = $this->dbms->query($sql)) {
            $insertId = $affectedRows = '';
            if (strtoupper(substr(trim($sql), 0, 7)) == 'INSERT ') {
                $insertId = $this->dbms->insert_id;
            }
            $affectedRows = $this->dbms->affected_rows;
            Tools::addMessage('success', strtr($success, array('%insertId%' => $insertId, '%affectedRows%' => $affectedRows)));
        } else {
            Tools::addMessage('error', strtr($error, array('%error%' => $this->dbms->error, '%errno%' => $this->dbms->errno)));
        }
        return $result;
    }

    /**  
     * Attempt to a user-defined translation
     * @param string $text
     * @param bool $escape escape for HTML (ie. tranfer ' " <… to &#39; &quot; &lt;…) ?
     * 
     * @return string
     */
    public function translate($text, $escape = true)
    {
        //@todo kdy může nastat situace, že TableListerTranslate neexistuje? Proč to nenadefinovat jako GodsDev\mycms\Utils\TableListerTranslate ?
        if (function_exists('TableListerTranslate')) {
            $result = TableListerTranslate($text);
        } else {
            $result = $text;
        }
        if ($escape) {
            $result = Tools::h($result);
        }
        return $result;
    }
}
