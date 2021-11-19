<?php

namespace WorkOfStan\MyCMS;

use Exception;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;

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
     * @var LogMysqli
     */
    public $dbms = null;

    /**
     * which Latte template to load
     * @var string
     */
    public $template;

    /**
     * variables for template rendering
     * @var array<string|array<mixed>>
     */
    public $context = [];

    /**
     * Logger SHOULD by available to the application using MyCMS
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Constructor
     *
     * @param array<array> $myCmsConf
     * @throws Exception if logger not configured
     */
    public function __construct(array $myCmsConf = [])
    {
        foreach ($myCmsConf as $myCmsVariable => $myCmsContent) {
            if (property_exists($this, $myCmsVariable)) {
                $this->{$myCmsVariable} = $myCmsContent;
            }
        }
        // Logger is obligatory
        if (!($this->logger instanceof LoggerInterface)) {
            error_log("Error: MyCMS constructed without logger. (" . get_class($this->logger) . ")");
            throw new Exception('Fatal error - project is not configured.');
        }
        // as a 2nd parameter of is_a class MUST use long form
        if (is_object($this->dbms) && is_a($this->dbms, '\WorkOfStan\MyCMS\LogMysqli')) {
            $this->dbms->query('SET NAMES UTF8 COLLATE "utf8_general_ci"');
        } else {
            $this->logger->info("No database connection set!");
        }
    }

    /**
     * Add a new CSRF token in $_SESSION['token']
     * @param bool $checkOnly - add new token only if $_SESSION['token'] is empty
     * @todo - test fully
     * @return void
     */
    public function csrfStart($checkOnly = false)
    {
        if (!isset($_SESSION['token']) || !is_array($_SESSION['token'])) {
            $_SESSION['token'] = [];
        }
        if (!$checkOnly || !count($_SESSION['token'])) {
            $_SESSION['token'] [] = rand((int) 1e8, (int) 1e9);
        }
    }

    /**
     * Check for CSRF
     * @param int $token
     * @return bool
     */
    public function csrfCheck($token)
    {
        // Variable $token always exists and is not nullable.
        return isset($_SESSION['token']) && is_array($_SESSION['token']) && in_array($token, $_SESSION['token']);
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

    /**
     *
     * @param string $sql
     * @return null|string|array<null|string> first selected row (or its first column if only one column is selected),
     *     null on empty SELECT
     */
    public function fetchSingle($sql)
    {
        return $this->dbms->fetchSingle($sql);
    }

    /**
     *
     * @param string $sql
     * @return array<array<null|string>> array of associative arrays for each result row
     *     or empty array on error or no results
     */
    public function fetchAll($sql)
    {
        return $this->dbms->fetchAll($sql);
    }

    /**
     *
     * @param string $sql SQL statement to be executed
     * @return array<array<string|null|array<string|null>>|string>|false
     * was return array<array<string|null>|string>|false (todo remove)
     *   Result is either associative array, empty array on empty SELECT, or false on error
     *   Error for this function is also an SQL statement that returns true.
     */
    public function fetchAndReindex($sql)
    {
        return $this->dbms->fetchAndReindex($sql);
    }

    /**
     *
     * @param string $sql SQL statement to be executed
     * @return array<array<string|null>|string> - either associative array, empty array on empty SELECT
     *   Exception on error
     */
    public function fetchAndReindexStrictArray($sql)
    {
        $result = $this->dbms->fetchAndReindex($sql);
        Assert::isArray($result);
        return $result;
    }

    /**
     * Latte initialization & Mark-up output
     *
     * @param string $dirTemplateCache
     * @param callable $customFilters
     * @param array<mixed> $params
     * @return void
     */
    public function renderLatte($dirTemplateCache, $customFilters, array $params)
    {
        Debugger::getBar()->addPanel(
            new BarPanelTemplate('Template: ' . $this->template, $this->context)
        );
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(
                new BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION)
            );
        }
        $Latte = new \Latte\Engine();
        $Latte->setTempDirectory($dirTemplateCache);
        $Latte->addFilter(null, $customFilters);
        Debugger::barDump($params, 'Params');
        Debugger::barDump($_SESSION, 'Session'); // mainly for $_SESSION['language']
        $Latte->render('template/' . $this->template . '.latte', $params); // @todo make it configurable
        unset($_SESSION['messages']);
        $this->dbms->showSqlBarPanel();
    }

    /**
     * Context setter that ensures the type
     *
     * @param array<string> $arr
     */
    public function setContext(array $arr)
    {
        Assert::isArray($arr);
        $this->context = $arr;
    }
}
