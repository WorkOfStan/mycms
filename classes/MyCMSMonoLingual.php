<?php

namespace WorkOfStan\MyCMS;

use Exception;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\MyCMS\Render;

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

    /** @var LogMysqli database management system */
    public $dbms = null;
    /** @var string which Latte template to load */
    public $template;
    /** @var array<array<mixed>|false|int|null|string> variables for template rendering */
    public $context = [];
    /** @var LoggerInterface Logger SHOULD by available to the application using MyCMS */
    public $logger;

    /**
     * Constructor
     *
     * @param array<mixed> $myCmsConf
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
     * @return array<mixed>|false
     *   Result is either associative array, empty array on empty SELECT, or false on error
     *   Error for this function is also an SQL statement that returns true.
     */
    public function fetchAndReindex($sql)
    {
        return $this->dbms->fetchAndReindex($sql);
    }

    /**
     * Assert array<string|null>
     *
     * @param array<mixed> $arr
     * @return array<string|null>
     */
    /*
    private function assertArrayStringNull(array $arr)
    {
        $result = [];
        foreach ($arr as $k2 => $v2) {
            if (!is_null($v2) && !is_string($v2)) {
                throw new Exception('String or null expected, but array contains type ' . gettype($v2));
            }
            $result[$k2] = $v2;
        }
        return $result;
    }
     *
     */

    /**
     *
     * @param string $sql SQL statement to be executed
     * @return array<array<string|null>|string> Either associative array or empty array on empty SELECT
     *
     * @throws \Exception on error
     */
    public function fetchAndReindexStrictArray($sql)
    {
        return $this->dbms->fetchAndReindexStrictArray($sql);
        /*
        $result = $this->dbms->fetchAndReindex($sql); // array<array<string|null|array<string|null>>|string>|false
        if (!is_array($result)) {
            $this->dbms->showSqlBarPanel();
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
               throw new \Exception('Unexpected structure of SQL statement result. Array contains type ' . gettype($v));
            }
        }
        return $resultTwoLevelArray;
         */
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
        $render = new Render($this->template, $dirTemplateCache, $customFilters);
        $render->renderLatte($params);
        $this->dbms->showSqlBarPanel();
    }

    /**
     * Context setter that ensures the type
     *
     * @param array<array<mixed>|false|int|null|string> $arr
     * @return void
     */
    public function setContext(array $arr)
    {
        Assert::isArray($arr);
        $this->context = $arr;
    }
}
