<?php

namespace WorkOfStan\MyCMS;

use Exception;
use Tracy\Debugger;
use Webmozart\Assert\Assert;
use WorkOfStan\Backyard\BackyardMysqli;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;

use function WorkOfStan\MyCMS\ThrowableFunctions\mb_eregi_replace;

/**
 * class with logging specific to this application
 * i.e. log changes of database
 */
class LogMysqli extends BackyardMysqli
{
    use \Nette\SmartObject;

    /** @var array<string> keywords in current DBMS */
    protected $KEYWORDS = [
        'ACCESSIBLE', 'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'ASENSITIVE',
        'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE',
        'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION',
        'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE',
        'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE',
        'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND',
        'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE',
        'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'DUAL',
        'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN',
        'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN',
        'FROM', 'FULLTEXT', 'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND',
        'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER',
        'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4',
        'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS',
        'KILL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES',
        'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT',
        'LOOP', 'LOW_PRIORITY', 'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MEDIUMBLOB',
        'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND',
        'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC',
        'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER',
        'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RANGE', 'READ',
        'READS', 'READ_ONLY', 'READ_WRITE', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE',
        'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE',
        'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE',
        'SEPARATOR', 'SET', 'SHOW', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION',
        'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',
        'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN', 'TINYBLOB',
        'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION',
        'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE',
        'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER',
        'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'XOR', 'YEAR_MONTH',
        'ZEROFILL'
    ];

    /** @var array<string> */
    protected $sqlStatementsArray = [];

    /** @var bool true if any of the SQL statements ended in an error state */
    protected $sqlError = false;

    /**
     * Logs SQL statement not starting with SELECT or SET
     *
     * @param string $sql SQL to execute
     * @param int $errorLogOutput optional default=1 turn-off=0
     *   It is int in order to be compatible with
     *   parameter $resultmode (int) of method mysqli::query()
     * @param bool $logQuery optional default logging of database changing statement can be (for security reasons)
     *     turned off by value false
     * @return bool|\mysqli_result<object>
     */
    public function query($sql, $errorLogOutput = 1, $logQuery = true)
    {
        if ($logQuery && !preg_match('/^SELECT |^SET |^SHOW /i', $sql)) {
            //mb_eregi_replace does not destroy multi-byte characters such as character Š
            error_log(
                trim(mb_eregi_replace('/\s+/', ' ', $sql)) . '; -- [' . date("d-M-Y H:i:s") . ']'
                . (isset($_SESSION['user']) ? " by ({$_SESSION['user']})" : '') . PHP_EOL,
                3,
                'log/sql' . date("Y-m-d") . '.log.sql'
            );
        }
        $result = parent::query($sql, $errorLogOutput);
        $this->sqlStatementsArray[] = ($result === false ? 'fail => ' : '') . $sql;
        if ($result === false) {
            $this->sqlError = true;
        }
        return $result;
    }

    /**
     * Make a MySQL query and if the result is non empty,
     * transforms the query result into a two dimensional array.
     * If it is empty or database call fails, throws Exception.
     *
     * @param string $sql
     * @return array<mixed>
     * @throws Exception
     */
    public function queryStrictNonEmptyArray($sql)
    {
        $result = $this->queryArray($sql, false); // returns two dimensional array
        if ($result === false) {
            $this->showSqlBarPanel();
            throw new Exception('Empty result or database error');
        }
        return $result;
    }

    /**
     * Logs SQL statement not starting with SELECT or SET. Throws exception in case response isn't \mysqli_result
     *
     * @param string $sql SQL to execute
     * @param int $errorLogOutput optional default=1 turn-off=0
     *   It is int in order to be compatible with
     *   parameter $resultmode (int) of method mysqli::query()
     * @param bool $logQuery optional default logging of database changing statement can be (for security reasons)
     *     turned off by value false
     * @return true
     * @throws Exception if error indicated by `false` result or if result is \mysqli_result object
     */
    public function queryStrictBool($sql, $errorLogOutput = 1, $logQuery = true)
    {
        $result = $this->query($sql, $errorLogOutput, $logQuery);

        if ($result === false) {
            $this->showSqlBarPanel();
            throw new Exception($this->errno . ': ' . $this->error);
        }
        if ($result === true) {
            return true;
        }
        $this->showSqlBarPanel();
        throw new Exception('Object answer on SQL statement (should have been true');
    }

    /**
     * Logs SQL statement not starting with SELECT or SET. Throws exception in case response isn't `\mysqli_result`
     *
     * @param string $sql SQL to execute
     * @param int $errorLogOutput optional default=1 turn-off=0
     *   It is int in order to be compatible with
     *   parameter $resultmode (int) of method mysqli::query()
     * @param bool $logQuery optional default logging of database changing statement can be (for security reasons)
     *     turned off by value false
     * @return \mysqli_result<object>
     * @throws Exception if error indicated by `false` result or if result is `true`
     */
    public function queryStrictObject($sql, $errorLogOutput = 1, $logQuery = true)
    {
        $result = $this->query($sql, $errorLogOutput, $logQuery);

        if ($result === false) {
            $this->showSqlBarPanel();
            throw new Exception($this->errno . ': ' . $this->error);
        }
        if ($result === true) {
            $this->showSqlBarPanel();
            throw new Exception('Non-object answer on SQL statement (unexpectedly equals true)');
        }
        return $result;
    }

    /**
     * Escape a string constant - specific to MySQL/MariaDb and current collation
     *
     * @param string $string to escape
     * @return string
     */
    public function escapeSQL($string)
    {
        return $this->real_escape_string($string);
    }

    /**
     * Escape a database identifier (table/column name, etc.) - specific to MySQL/MariaDb
     *
     * @param string $string to escape
     * @return string escaped identifier
     */
    public function escapeDbIdentifier($string)
    {
        $string = str_replace('`', '``', $string);
        if (preg_match('/[^a-z0-9_]+/i', $string) || in_array(strtoupper($string), $this->KEYWORDS)) {
            $string = "`$string`";
        }
        return $string;
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
        //e.g. value: '0','a''b','c"d','e\\f','','g`h' should be ['0', "a'b", 'c"d', 'e\f', '', 'g`h'
        if (($result = substr($list, 0, 5) == 'enum(') || substr($list, 0, 4) == 'set(') {
            $list = substr($list, $result ? 5 : 4, -1);
        }
        $list = substr($list, 0, 1) == "'" ? $list : '';
        preg_match_all("~'((''|[^'])*',)*~i", "$list,", $result);
        $result = isset($result[1]) ? $result[1] : [];
        foreach ($result as &$value) {
            $value = strtr(substr($value, 0, -2), ["''" => "'", "\\\\" => "\\"]);
        }
        return $result;
    }

    /**
     * Decode options in 'set' columns - specific to MySQL/MariaDb
     *
     * @param string $list list of options (e.g. ""
     * @return array<string>
     */
    public function decodeSetOptions($list)
    {
        if (substr($list, 0, 4) == 'set(') {
            $list = substr($list, 4, -1);
        }
        $result = explode(',', $list);
        foreach ($result as &$value) {
            $value = strtr(substr($value, 1, -1), ["''" => "'", "\\\\" => "\\"]);
        }
        return $result;
    }

    /**
     * Check wheter given interval matches the format for expression used after MySQL's keyword 'INTERVAL'
     * - specific to MySQL/MariaDb
     *
     * @param string $interval
     * @return int|false 1=yes, 0=no, false=error
     */
    public function checkIntervalFormat($interval)
    {
        $first = '\s*\-?\d+\s*';
        $int = '\s*\d+\s*';
        // phpcs:ignore
        return preg_match("~^\s*((?:\-?\s*(?:\d*\.?\d+|\d+\.?\d*)(?:e[\+\-]?\d+)?)\s+(MICROSECOND|SECOND|MINUTE|HOUR|DAY|WEEK|MONTH|QUARTER|YEAR)"
            . "|\'$first.$int\'\s*(SECOND|MINUTE|HOUR|DAY)_MICROSECOND"
            . "|\'$first:$int\'\s*(MINUTE_SECOND|HOUR_SECOND)"
            . "|\'$first $first\'\s*DAY_HOUR"
            . "|\'$int-$int\'\s*YEAR_MONTH"
            . "|\'$int:$int:$int\'\s*HOUR_SECOND"
            . "|\'$first $int:$int\'\s*DAY_MINUTE"
            . "|\'$first $int:$int:$int\'\s*DAY_SECOND"
            . ")\s*\$~i", $interval);
    }

    /**
     * Return list of columns for use in an SQL statement
     *
     * @param array<string> $columns
     * @param array<array> $fields info about the columns like in MyTableLister->fields (optional)
     * @return string
     */
    public function listColumns(array $columns, array $fields = [])
    {
        $result = '';
        foreach ($columns as $column) {
            $result .= ',';
            $escColumn = $this->escapeDbIdentifier($column);
            if (
                isset($fields[$column]['type']) &&
                ($fields[$column]['type'] == 'set' || $fields[$column]['type'] == 'enum')
            ) {
                $result .= "$escColumn - 0 AS $escColumn"; // NULLs will persist
            } else {
                $result .= $escColumn;
            }
        }
        return substr($result, 1);
    }

    /**
     * Return if last error is a "duplicate entry"
     *
     * @return bool
     */
    public function errorDuplicateEntry()
    {
        return $this->errno == 1062;
    }

    /**
     * Execute an SQL and fetch the first row of a resultset.
     * If only one column is selected, return it, otherwise return whole row.
     *
     * @example: fetchSingle('SELECT name, age FROM employees WHERE id = 5') --> [name => "John", age => 45]
     * @example: fetchSingle('SELECT age FROM employees WHERE id = 5') --> 45
     *
     * @param string $sql SQL to be executed
     * @return null|string|array<null|string> first selected row (or its first column if only one column is selected),
     *     null on empty SELECT
     * @throws Exception when a database error occurs or when an SQL statement returns true.
     */
    public function fetchSingle($sql)
    {
        $query = $this->query($sql);
        if ($query === true) {
            $this->showSqlBarPanel();
            throw new Exception('SQL statement resulting in \mysqli_result<object> expected. True received.');
        }
        if (!$query) {
            $this->showSqlBarPanel();
            throw new Exception($this->errno . ': ' . $this->error);
        }
        $row = $query->fetch_assoc();
        if (!is_array($row) || !count($row)) {
            return null;
        }
        return count($row) == 1 ? reset($row) : $row;
    }

    /**
     * Execute an SQL and fetch the string content of the one one column of the one row of a resultset.
     *
     * @param string $sql SQL to be executed
     * @return string first column of the first selected row
     */
    public function fetchSingleString($sql)
    {
        $query = $this->fetchSingle($sql);
        if (!is_string($query)) {
            $this->showSqlBarPanel();
        }
        Assert::string($query);
        return $query;
    }

    /**
     * Execute an SQL and fetch the first row of a resultset,
     * if it is an array of strings. (NULL is replaced by empty string.)
     *
     * @param string $sql SQL to be executed
     * @return array<string>|null
     * @throws Exception when a database error occurs or when an SQL statement returns true or string.
     */
    public function fetchStringArray($sql)
    {
        $arr = $this->fetchSingle($sql);
        if (is_null($arr)) {
            return null;
        }
        if (!is_array($arr)) {
            $this->showSqlBarPanel();
            throw new Exception('SQL statement resulting in non array.');
        }
        foreach ($arr as $name => $str) {
            if (is_null($str)) {
                //throw new Exception('Some non string. Null in the field: ' . $name . ' for SQL: ' . $sql);
                $arr[$name] = '';
            }
        }
        /**
         * @phpstan-ignore-next-line FALSE POSITIVE: should return array<string>|null but returns array<string|null>.
         */
        return $arr;
    }

    /**
     * Execute an SQL, fetch and return all resulting rows
     *
     * @param string $sql
     * @return array<array<null|string>> array of associative arrays for each result row
     *     or empty array on error or no results
     */
    public function fetchAll($sql)
    {
        $result = [];
        $query = $this->query($sql);
        if (is_object($query) && is_a($query, '\mysqli_result')) {
            while ($row = $query->fetch_assoc()) {
                $result [] = $row;
            }
        }
        return $result;
    }

    /**
     * Execute an SQL, fetch resultset into an array reindexed by first field.
     * If the query selects only two fields, the first one is a key and the second one a value of the result array
     * Example: 'SELECT id,name FROM employees' --> [3=>"John", 4=>"Mary", 5=>"Joe"]
     * If the result set has more than two fields, whole resultset is fetched into each array item
     * Example: 'SELECT id,name,surname FROM employees' --> [3=>[name=>"John", surname=>"Smith"], [...]]
     * If the first column is non-unique, results are joined into an array.
     * Example: 'SELECT department_id,name FROM employees' --> [1=>['John', 'Mary'], 2=>['Joe','Pete','Sally']]
     * Example: 'SELECT division_id,name,surname FROM employees' -->
     *     [1=>[[name=>'John',surname=>'Doe'], [name=>'Mary',surname=>'Saint']], 2=>[...]]
     *
     * @param string $sql SQL statement to be executed
     * @return array<mixed>|false
     *   Result is either associative array, empty array on empty SELECT, or false on error
     *   Error for this function is also an SQL statement that returns true.
     *
     * Note: as fetch_assoc claims to an associative array of strings representing the fetched row in the result set
     * but PHPStan doesn't reflect this, the @return statement is vastly simplified to fix this error:
     * Method WorkOfStan\MyCMS\LogMysqli::fetchAndReindex()
     * should return array<array<array<string|null>|string|null>|string>|false
     * but returns array<string, array<int|string, array<int|string, array<int|string,
     *   array<string, string>|string|false>|string|false>|string|false>|string|false>.
     */
    public function fetchAndReindex($sql)
    {
        $query = $this->query($sql);
        if (is_bool($query)) {
            return false;
        }
        $result = [];
        while ($row = $query->fetch_assoc()) {
            $key = reset($row);
            $value = count($row) == 2 ? next($row) : $row;
            if (count($row) > 2) {
                if (!is_array($value)) {
                    // show SQL bar panel in case of error
                    $this->showSqlBarPanel();
                }
                Assert::isArray($value);
                array_shift($value);
            }
            if (isset($result[$key])) {
                Assert::isArray($result[$key]);
                if (is_array($value)) {
                    if (!is_array(reset($result[$key]))) {
                        $result[$key] = [$result[$key]];
                    }
                    $result[$key] [] = $value;
                } else {
                    $result[$key] = array_merge((array) $result[$key], (array) $value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Assert array<string|null>
     *
     * @param array<mixed> $arr
     * @return array<string|null>
     *
     * @throws \Exception on other than string or null values
     */
    private function assertArrayStringNull(array $arr)
    {
        $result = [];
        foreach ($arr as $k2 => $v2) {
            if (!is_null($v2) && !is_string($v2)) {
                throw new \Exception('String or null expected, but array contains type ' . gettype($v2));
            }
            $result[$k2] = $v2;
        }
        return $result;
    }

    /**
     *
     * @param string $sql SQL statement to be executed
     * @return array<array<string|null>|string> Either associative array or empty array on empty SELECT
     *
     * @throws \Exception on error
     */
    public function fetchAndReindexStrictArray($sql)
    {
        $result = $this->fetchAndReindex($sql); // array<array<string|null|array<string|null>>|string>|false
        if (!is_array($result)) {
            $this->showSqlBarPanel();
        }
        Assert::isArray($result);
        if (empty($result)) {
            return [];
        }
        if ((count($result) === 1) && array_key_exists(1, $result) && is_array($result[1])) {
            $result = $result[1]; // TODO explore why sometimes records are in the array, and sometimes in the key=1
        }
        $resultTwoLevelArray = [];
        foreach ($result as $k => $v) {
            if (is_string($v)) {
                $resultTwoLevelArray[$k] = $v;
            } elseif (is_array($v)) {
                $resultTwoLevelArray[$k] = $this->assertArrayStringNull($v);
            } else {
                throw new \Exception('SQL statement result unexpected structure: Array contains type ' . gettype($v));
            }
        }
        return $resultTwoLevelArray;
    }

    /**
     *
     * @return bool
     */
    public function getStatementsError()
    {
        return $this->sqlError;
    }

    /**
     *
     * @return array<string>
     */
    public function getStatementsArray()
    {
        return $this->sqlStatementsArray;
    }

    /**
     * @return void
     */
    public function showSqlBarPanel()
    {
        if (!empty($this->sqlStatementsArray)) {
            $sqlBarPanel = new BarPanelTemplate('SQL: ' . count($this->sqlStatementsArray), $this->sqlStatementsArray);
            if ($this->sqlError) {
                $sqlBarPanel->setError();
            }
            Debugger::getBar()->addPanel($sqlBarPanel);
        }
    }

    /**
     * Extract data from an array and present it as values, field names, or pairs.
     * @example: $data = ['id'=>5, 'name'=>'John', 'surname'=>'Doe'];
     * $sql = 'INSERT INTO employees (' . $this->values($data, 'fields')
     *     . ') VALUES (' . $this->values($data, 'values') . ')';
     * $sql = 'UPDATE employees SET ' . $this->values($data, 'pairs') . ' WHERE id=5';
     *
     * @param array<mixed> $data
     * @param string $format either "values" (default), "fields" or "pairs"
     *      or anything containing %value% for value and %column% for column name that gets replaced
     * @return string
     */
    public function values($data, $format)
    {
        $result = '';
        $replace = (strpos($format, '%value%') !== false) || (strpos($format, '%column%') !== false);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $value = is_null($value) ? 'NULL' : (is_int($value) ? $value : '"' . $this->escapeSQL($value) . '"');
                $key = $this->escapeDbIdentifier($key);
                if ($format == 'fields') {
                    $result .= ", $key";
                } else {
                    if ($format == 'pairs') {
                        $result .= ", $key = $value";
                    } elseif ($replace) {
                        $result .= ", " . strtr($format, ['%value%' => $value, '%column%' => $key]);
                    } else {
                        $result .= ",\t$value";
                    }
                }
            }
        }
        return substr($result, 2 /* length of the initial ", " */);
    }
}
