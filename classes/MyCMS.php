<?php
namespace GodsDev\MyCMS;

class MyCMS {
    
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
    public $TRANSLATION;
    public $template; //which Latte template to load
    public $context = array(); //array of variables for template rendering

    /**
     * 
     * @param array $myCmsConf
     */
    public function __construct(array $myCmsConf = array()) {
        $this->myCmsConf = array_merge(
                array(//default values
                ), $myCmsConf);
        //@todo do not use $this->myCmsConf but set the class properties right here accordingly; and also provide means to set the values otherwise later
        $classAttributes = explode(' ', 'PAGES PAGES_SPECIAL PAYMENTS SETTINGS WEBSITE CART_ITEM COUNTRIES CURRENCIES COMMISSION ITEM_ORDER LOG_SETTINGS TRANSLATION template context');
        foreach ($this->myCmsConf as $myCmsVariable => $myCmsContent) {
            if (in_array($myCmsVariable, $classAttributes, true)) {
                $this->{$myCmsVariable} = $myCmsContent;
            }
        }
        if (is_object($this->dbms)) {
            $this->dbms->query('SET NAMES UTF8 COLLATE "utf8_general_ci"');
        }
    }
    
    /**
     * 
     * @param array $getArray $_GET or its equivalent
     * @param array $sessionArray $_SESSION or its equivalent
     * @return bool $makeInclude for testing may be set to false as mycms itself does not contain the language-XX.inc.php files
     * @return string to be used as $_SESSION['language']
     * 
     * @todo perfect candidate for PHPUnit test viz testGetSessionLanguage() v Test/MyCMSTest.php
     */
    public function getSessionLanguage(array $getArray, array $sessionArray, $makeInclude = true){
        //@todo add logic
        $resultLanguage = DEFAULT_LANGUAGE;
        //@todo add logic
        
        if ($makeInclude) {
            include_once './language-' . $resultLanguage . '.inc.php';
            //@todo language-něco.inc.php nově by měl obsahovat pole s TRANSLATION a zd ena řádku níž bych měl:
            //pseudocode: $this->TRANSLATION = to co jsem includnul z language-něco.inc.php
        }
        
        return $resultLanguage;
    }

    /** Execute an SQL, fetch resultset into an array reindexed by first field.
     * If the query selects only two fields, the first one is a key and the second one a value of the result array
     * Example: 'SELECT id,name FROM employees' --> [3=>"John", 4=>"Mary", 5=>"Joe"]
     * If the result set has more than two fields, whole resultset is fetched into each array item
     * Example: 'SELECT id,name,surname FROM employees' --> [3=>[id=>3, name=>"John", surname=>"Smith"], [...]]
     * @param string SQL to be executed
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
                if (isset($result[$key]) && count($row) == 2) {
                    $result[$key] = (array) $result[$key] + array(
                        count($result[$key]) => next($row)
                    );
                } else {
                    $result[$key] = count($row) == 2 ? next($row) : $row;
                }
            }
        }
        return $result;
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
            error_log('Translation does not exist - ' . $id); //@todo replace with a standard logger
        }
        $result = isset($this->TRANSLATION[$id]) ? $this->TRANSLATION[$id] : $id;
        if ($options === L_UCFIRST) {
            $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
        } elseif (is_int($options) && ($options == MB_CASE_UPPER || $options == MB_CASE_LOWER || $options == MB_CASE_TITLE)) {
            $result = mb_convert_case($result, $options);
        }
        return $result;
    }
}
