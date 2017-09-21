<?php
namespace GodsDev\MyCMS;

class MyCMS {
    public $dbms;
    public $PAGES;
    public $PAYMENTS;
    public $PAGES_SPECIAL;
    public $SETTINGS = null;
    public $WEBSITE = null; //main info about this website
    public $CART_ITEM; //items in cart
    public $COUNTRIES;
    public $CURRENCIES;
    public $COMMISSION_FIELDS;
    public $COMMISSION_MANDATORY_FIELDS;
    public $COMMISSION_PERSON;
    public $ITEM_ORDER;
    public $LOG_SETTINGS;
    public $TRANSLATION;
    public $template; //which Latte template to load
    public $context = array(); //array of variables for template rendering

    /**
     * @param string string to add to context
     */
    public function addToPage($string)
    {
        $this->context['page'] .= $string;
    }

    public function addMessage($type, $message, $show)
    {
        $ob = ob_start();
        Tools::addMessage($type, $message);
        $tmp = ob_end_flush();
    } 

    /** Execute an SQL, fetch resultset into an array reindexed by first field.
     * If the query selects only two fields, the first one is a key and the second one a value of the result array
     * Example: 'SELECT id,name FROM employees' --> [1=>"John", 2=>"Mary", 5=>"Joe"]
     * If the result set has more than two fields, whole resultset is fetched into each array item
     * Example: 'SELECT id,name,surname FROM employees' --> [[id=>1, name=>"John", surname=>"Smith"], [...]]
     * @param string SQL to be executed
     * @result mixed - either associative array, empty array on empty select, or false on error
     */
    public function fetchAndReindex($sql)
    {
        $result = array();
        $query = $this->dbms->query($sql);
        if (is_object($query)) {
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
}
