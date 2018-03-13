<?php

namespace GodsDev\MyCMS;

use GodsDev\Backyard\BackyardMysqli;

/**
 * class with logging specific to this application
 * i.e. log changes of database
 */
class LogMysqli extends BackyardMysqli
{

    use \Nette\SmartObject;

    /** @var array */
    protected $sqlStatementsArray = array();

    /**
     * Logs SQL statement not starting with SELECT or SET
     *
     * @param string $sql SQL to execute
     * @param bool $ERROR_LOG_OUTPUT
     * @return \mysqli_result Object|false
     * @throws DBQueryException
     */
    public function query($sql, $ERROR_LOG_OUTPUT = true)
    {
        if (!preg_match('/^SELECT |^SET |^SHOW /i', $sql)) {
            //mb_eregi_replace does not destroy e.g. character Š
            error_log(trim(mb_eregi_replace('/\s+/', ' ', $sql)) . '; -- [' . date("d-M-Y H:i:s") . ']' . (isset($_SESSION['user']) ? " by ({$_SESSION['user']})" : '') . PHP_EOL, 3, 'log/sql' . date("Y-m-d") . '.log');
        }
        $this->sqlStatementsArray[] = $sql;
        return parent::query($sql, $ERROR_LOG_OUTPUT);
    }

    public function getStatementsArray()
    {
        return $this->sqlStatementsArray;
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
     * @return string
     */
    public function escapeDbIdentifier($string)
    {
        $string = '`' . str_replace('`', '``', $string) . '`';
        if (preg_match('/[^a-z0-9_]+/i', $string)) {
            $string = substr($string, 1, -1);
        }
        return $string;
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
     * @param string $sql SQL to be executed
     * @return mixed first selected row (or its first column if only one column is selected), null on empty SELECT, or false on error
     */
    public function fetchSingle($sql)
    {
        if ($query = $this->query($sql)) {
            $row = $query->fetch_assoc();
            if (count($row) > 1) {
                return $row;
            } elseif (is_array($row)) {
                return reset($row);
            } else {
                return null;
            }
        }
        return false;
    }

    /**
     * Execute an SQL, fetch and return all resulting rows
     *
     * @param string $sql
     * @return mixed array of associative arrays for each result row or empty array on error or no results
     */
    public function fetchAll($sql)
    {
        $result = array();
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
     * Example: 'SELECT division_id,name,surname FROM employees' --> [1=>[[name=>'John',surname=>'Doe'], [name=>'Mary',surname=>'Saint']], 2=>[...]]
     *
     * @param string $sql SQL to be executed
     * @return mixed - either associative array, empty array on empty SELECT, or false on error
     */
    public function fetchAndReindex($sql)
    {
        $query = $this->query($sql);
        if (!$query) {
            return false;
        }
        $result = array();
        while ($row = $query->fetch_assoc()) {
            $key = reset($row);
            $value = count($row) == 2 ? next($row) : $row;
            if (count($row) > 2) {
                array_shift($value);
            }
            if (isset($result[$key])) {
                if (is_array($value)) {
                    if (!is_array(reset($result[$key]))) {
                        $result[$key] = array($result[$key]);
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

}