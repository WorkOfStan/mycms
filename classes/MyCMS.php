<?php

namespace GodsDev\MyCMS;

//use Psr\Log\LoggerInterface;

class MyCMS
{

    /** Class for a MyCMS object. 
     * It holds all variables needed for the used project.
     * Among others, it translates multilingual texts.
     *
     * @var \mysqli
     */
    public $dbms = null; //database management system
    public $PAGES;
    public $PAGES_SPECIAL; //special pages that are not fetched from database (e.g. sitemap etc.)
    public $PAYMENTS;
    public $SETTINGS = null;
    public $WEBSITE = null; //main info about this website
    public $CART_ITEM; //items in cart
    public $COUNTRIES;
    public $CURRENCIES;
    public $COMMISSION;
    public $ITEM_ORDER;
    public $LOG_SETTINGS; //@todo migrate to a standard logger

    /**
     * Selected locale strings
     * 
     * @var array
     */
    public $TRANSLATION;

    /**
     * Available languages
     * 
     * @var array
     */
    public $TRANSLATIONS;
    public $template; //which Latte template to load
    public $context = array(); //array of variables for template rendering

    /**
     * Logger SHOULD by available to the application using mycms
     * 
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /** Constructor
     *
     * @param array $myCmsConf
     */
    public function __construct(array $myCmsConf = array())
    {
        $this->myCmsConf = array_merge(
                array(//default values
                ), $myCmsConf);
        //@todo do not use $this->myCmsConf but set the class properties right here accordingly; and also provide means to set the values otherwise later
        $classAttributes = explode(' ', 'PAGES PAGES_SPECIAL PAYMENTS SETTINGS WEBSITE CART_ITEM COUNTRIES CURRENCIES COMMISSION ITEM_ORDER LOG_SETTINGS TRANSLATION TRANSLATIONS template context logger dbms');
        foreach ($this->myCmsConf as $myCmsVariable => $myCmsContent) {
            if (in_array($myCmsVariable, $classAttributes, true)) {
                $this->{$myCmsVariable} = $myCmsContent;
            }
        }
        // Logger is obligatory
        if (!is_object($this->logger)) {
            error_log("Error: MyCMS constructed without logger.");
            die('Fatal error - project is not configured.'); //@todo nicely formatted error page            
        }
        if (is_object($this->dbms)) {
            $this->dbms->query('SET NAMES UTF8 COLLATE "utf8_general_ci"');
        } else {
            $this->logger->info("No database connection set!");
        }
    }

    /**
     * 
     * @param array $getArray $_GET or its equivalent
     * @param array $sessionArray $_SESSION or its equivalent
     * @return bool $makeInclude for testing may be set to false as mycms itself does not contain the language-XX.inc.php files
     * @return string to be used as $_SESSION['language']
     * 
     * constant TAB_PREFIX expected
     */
    public function getSessionLanguage(array $getArray, array $sessionArray, $makeInclude = true)
    {
        $resultLanguage = (isset($getArray['language']) && isset($this->TRANSLATIONS[$getArray['language']])) ?
                $getArray['language'] :
                ((isset($sessionArray['language']) && isset($this->TRANSLATIONS[$sessionArray['language']])) ? $sessionArray['language'] : DEFAULT_LANGUAGE);
        if ($makeInclude) {
            $languageFile = './language-' . $resultLanguage . '.inc.php';
            if (file_exists($languageFile)) {
                include_once $languageFile; //MUST contain $translation = array(...);
                //@todo assert $translation is set and it is an array
                $this->TRANSLATION = $translation;
            } else {
                $this->logger->error("Missing expected language file {$languageFile}");
            }
        }

        return $resultLanguage;
    }

    /** Load specific settings from database to $this->SETTINGS and $this->WEBSITE
     * @param mixed $selectSettings array or SQL SELECT statement to get project-specific settings
     * @param mixed $selectWebsite array or SQL SELECT statement to get language-specific website settings
     * @param bool die on error (i.e. if $this->SETTINGS or $this->WEBSITE is not loaded)?
     */
    public function loadSettings($selectSettings, $selectWebsite, $die = true)
    {
        if (is_array($selectSettings)) {
            $this->SETTINGS = $selectSettings;
        } elseif ($row = $this->fetchSingle($selectSettings)) {
            $this->SETTINGS = json_decode($row, true); //If SETTINGS missing but the SQL statement returns something, then look for error within JSON.
        } //else fail in universal check
        if (is_array($selectWebsite)) {
            $this->WEBSITE = $selectSettings;
        } elseif ($row = $this->fetchSingle($selectWebsite)) {
            $this->WEBSITE = json_decode($row, true); //If WEBSITE missing but the SQL statement returns something, then look for error within JSON.
        } //else fail in universal check
        // universal check
        if (!$this->SETTINGS || !$this->WEBSITE) {
            $this->logger->emergency((!$this->SETTINGS ? "SETTINGS missing ($selectSettings).\n" : "") . (!$this->WEBSITE ? "WEBSITE missing ($selectWebsite).\n" : ""));
            if ($die) {
                die('Fatal error - project is not configured.'); //@todo nicely formatted error page
            } else {
                return false;
            }
        }
        return true;
    }

    /** Execute an SQL, fetch resultset into an array reindexed by first field.
     * If the query selects only two fields, the first one is a key and the second one a value of the result array
     * Example: 'SELECT id,name FROM employees' --> [3=>"John", 4=>"Mary", 5=>"Joe"]
     * If the result set has more than two fields, whole resultset is fetched into each array item
     * Example: 'SELECT id,name,surname FROM employees' --> [3=>[name=>"John", surname=>"Smith"], [...]]
     * If the first column is non-unique, results are joined into an array.
     * Example: 'SELECT department_id,name FROM employees' --> [1=>['John', 'Mary'], 2=>['Joe','Pete','Sally']]
     * Example: 'SELECT division_id,name,surname FROM employees' --> [1=>[[name=>'John',surname=>'Doe'], [name=>'Mary',surname=>'Saint']], 2=>[...]]
     * @param string $sql SQL to be executed
     * @result mixed - either associative array, empty array on empty select, or false on error
     */
    public function fetchAndReindex($sql)
    {
        $result = false;
        $query = $this->dbms->query($sql);
        if (is_object($query)) {
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
        }
        return $result;
    }

    /** Execute an SQL, fetch and return all resulting rows
     * @param string $sql
     * @param mixed array of associative arrays for each result row or empty array on error or no results
     */
    public function fetchAll($sql)
    {
        $result = array();
        $query = $this->dbms->query($sql);
        if (is_object($query)) {
            while ($row = $query->fetch_assoc()) {
                $result [] = $row;
            }
        }
        return $result;
    }

    /** Execute an SQL and fetch the first row of a resultset.
     * If only one column is selected, return it, otherwise return whole row.
     * @param string $sql SQL to be executed
     * @result mixed - first row (first column if only one is selected), null on empty SELECT, or false on error
     */
    public function fetchSingle($sql)
    {
        $query = $this->dbms->query($sql);
        if (is_object($query)) {
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

    /** Translate defined string to the language stored in $_SESSION['language'].
     * Returns original text if translation not found.
     * 
     * @param string $id text to translate
     * @param mixed $options case transposition - either null or one of MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE or L_UCFIRST
     * @return string
     */
    public function translate($id, $options = null)
    {
        if (!isset($this->TRANSLATION[$id]) && isset($_SESSION['test-translations']) && $_SESSION['language'] != DEFAULT_LANGUAGE) {
            $this->logger->warning('Translation does not exist - ' . $id);
        }
        $result = isset($this->TRANSLATION[$id]) ? $this->TRANSLATION[$id] : $id;
        if ($options === L_UCFIRST) {
            $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
        } elseif (is_int($options) && ($options == MB_CASE_UPPER || $options == MB_CASE_LOWER || $options == MB_CASE_TITLE)) {
            $result = mb_convert_case($result, $options);
        }
        return $result;
    }

    /**
     * In case of form processing includes either admin-process.php or process.php.
     * 
     * @todo - test fully
     * 
     */
    public function formController()
    {
        // fork for for admin and form processing
        if (isset($_POST) && is_array($_POST)) {
            require_once basename($_SERVER['PHP_SELF']) == 'admin.php' ? './admin-process.php' : './process.php';
        }
    }

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

}
