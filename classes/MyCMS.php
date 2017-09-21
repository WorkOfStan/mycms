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

}
