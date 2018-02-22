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
        if (!preg_match('/^select |^SET |^SHOW /i', $sql)) {
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

}
