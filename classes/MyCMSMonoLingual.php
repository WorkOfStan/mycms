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
     * Add a new CSRF token in $_SESSION['token']
     * @param bool $checkOnly - add new token only if $_SESSION['token'] is empty
     * @todo - test fully
     */
    public function csrfStart($checkOnly = false)
    {
        if (!isset($_SESSION['token']) || !is_array($_SESSION['token'])) {
            $_SESSION['token'] = array();
        }
        if (!$checkOnly || !count($_SESSION['token'])) {
            $_SESSION['token'] []= rand(1e8, 1e9);
        }
    }

    /**
     * Check for CSRF
     * @param int $token
     * @return bool
     */
    public function csrfCheck($token)
    {
        return isset($token, $_SESSION['token']) && is_array($_SESSION['token']) && in_array($token, $_SESSION['token']);
    }

    /**
     * Shortcut for mysqli::real_escape_string($link, $str)
     *
     * @param string $string
     * @return string
     */
    public function escapeSQL($string)
    {
        return $this->dbms->escapeSQL($string);
    }
    
    public function fetchSingle($sql)
    {
        return $this->dbms->fetchSingle($sql);
    }

    public function fetchAll($sql)
    {
        return $this->dbms->fetchAll($sql);
    }

    public function fetchAndReindex($sql)
    {
        return $this->dbms->fetchAndReindex($sql);
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
