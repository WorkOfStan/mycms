<?php

namespace GodsDev\MyCMS;

use Psr\Log\LoggerInterface;
use Tracy\Debugger;

/**
 * Class for a MyCMS object without translations.
 * This version has variable and methods for DBMS and result retrieval
 * and a rendering to a Latte templates.
 * For multi-language version of this class, use MyCMS.
 * 
 * For a new project it is expected to make a extended class and place 
 * additional attributes needed for running, then use that class.
 * 
 */
class MyCMSMonoLingual
{

    use \Nette\SmartObject;

    /**
     * database management system
     * @var \mysqli
     */
    public $dbms = null;

    /**
     * which Latte template to load
     * @var string
     */
    public $template;

    /**
     * variables for template rendering
     * @var array
     */
    public $context = array();

    /**
     * Logger SHOULD by available to the application using mycms
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * Constructor
     *
     * @param array $myCmsConf
     */
    public function __construct(array $myCmsConf = array())
    {
        foreach ($myCmsConf as $myCmsVariable => $myCmsContent) {
            if (property_exists($this, $myCmsVariable)) {
                $this->{$myCmsVariable} = $myCmsContent;
            }
        }
        // Logger is obligatory
        if (!is_object($this->logger) || !($this->logger instanceof LoggerInterface)) {
            error_log("Error: MyCMS constructed without logger. (" . get_class($this->logger) . ")");
            die('Fatal error - project is not configured.'); //@todo nicely formatted error page
        }
        if (is_object($this->dbms) && is_a($this->dbms, '\mysqli')) {
            $this->dbms->query('SET NAMES UTF8 COLLATE "utf8_general_ci"');
        } else {
            $this->logger->info("No database connection set!");
        }
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
     * @result mixed - either associative array, empty array on empty select, or false on error
     */
    public function fetchAndReindex($sql)
    {
        $query = $this->dbms->query($sql);
        if (!is_object($query) || !is_a($query, '\mysqli_result')) {
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

    /**
     * Execute an SQL, fetch and return all resulting rows
     *
     * @param string $sql
     * @param mixed array of associative arrays for each result row or empty array on error or no results
     */
    public function fetchAll($sql)
    {
        $result = array();
        $query = $this->dbms->query($sql);
        if (is_object($query) && is_a($query, '\mysqli_result')) {
            while ($row = $query->fetch_assoc()) {
                $result [] = $row;
            }
        }
        return $result;
    }

    /**
     * Execute an SQL and fetch the first row of a resultset.
     * If only one column is selected, return it, otherwise return whole row.
     *
     * @param string $sql SQL to be executed
     * @result mixed - first row (first column if only one is selected), null on empty SELECT, or false on error
     */
    public function fetchSingle($sql)
    {
        $query = $this->dbms->query($sql);
        if (is_object($query) && is_a($query, '\mysqli_result')) {
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
     * In case of form processing includes either admin-process.php or process.php.
     *
     * @todo - test fully
     *
     */
    /* OBSOLETE METHOD - REMOVE SOON
    public function formController($databaseTable = '')
    {
        // fork for for admin and form processing
        if (isset($_POST) && is_array($_POST)) {
            if (basename($_SERVER['PHP_SELF']) == 'admin.php') {
                require_once './user-defined.php';
                $GLOBALS['TableAdmin'] = new \GodsDev\MyCMS\TableAdmin($this->dbms, $databaseTable);
                require_once './admin-process.php';
            } else {
                require_once './process.php';
            }
        }
    }
     * 
     */

    /**
     * Create a general CSRF token, keep it in session
     *
     * @todo - test fully
     */
    public function csrf()
    {
        if (!isset($_GET['keep-token'])) {
            $_SESSION['token'] = rand(1e8, 1e9);
        }
    }

    /**
     * Shortcut for mysqli::real_escape_string($link, $str)
     *
     * @param string string
     * @result string
     */
    public function escapeSQL($string)
    {
        return $this->dbms->real_escape_string($string);
    }

    /**
     * Latte initialization & Mark-up output
     *
     * @param string $dirTemplateCache
     * @param string $customFilters
     * @param array $params
     */
    public function renderLatte($dirTemplateCache, $customFilters, array $params)
    {
        Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('Template: ' . $this->template, $this->context));
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION));
        }
        $Latte = new \Latte\Engine;
        $Latte->setTempDirectory($dirTemplateCache);
        $Latte->addFilter(null, $customFilters);
        Debugger::barDump($params, 'Params');
        Debugger::barDump($_SESSION, 'Session'); //mainly for  $_SESSION['language']
        $Latte->render('template/' . $this->template . '.latte', $params); //@todo make it configurable
        unset($_SESSION['messages']);
        $sqlStatementsArray = $this->dbms->getStatementsArray();
        if (!empty($sqlStatementsArray)) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray));
        }
    }

}
