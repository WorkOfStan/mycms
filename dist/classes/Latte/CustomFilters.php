<?php

namespace GodsDev\mycmsprojectnamespace\Latte;

use GodsDev\Tools\Tools;
//use GodsDev\mycmsprojectnamespace\ProjectSpecific;
use GodsDev\mycmsprojectnamespace\Template;
use GodsDev\MyCMS\MyCMS;

/**
 * Custom project-specific filters for Latte.
 */
class CustomFilters
{
    use \Nette\SmartObject;

    /** @var \GodsDev\MyCMS\MyCMS */
    protected $MyCMS;

    /** @var \GodsDev\mycmsprojectnamespace\ProjectSpecific */
//    private $projectSpecific;

    /**
     *
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     */
    public function __construct(MyCMS $MyCMS)
    {
        $this->MyCMS = $MyCMS;
//        $this->projectSpecific = new ProjectSpecific($this->MyCMS);
    }

    public function common($filter, $value)
    {
        $args = func_get_args();
        array_shift($args);
        /* if (strtolower($filter) == 'showmessages') {
          return ProjectSpecific::$filter();
          } else */
        if (method_exists(__CLASS__, $filter)) {
            return call_user_func_array([__CLASS__, $filter], $args);
        }
    }

    public static function shortify($s, $len = 10)
    {
        return mb_substr($s, 0, $len);
    }

    public static function firstLower($s)
    {
        return mb_strtolower(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    public static function webalize($s)
    {
        return Tools::webalize($s);
    }

    public function translate($text)
    {
        return $this->MyCMS->translate($text);
    }

    public static function vardump($args)
    {
        $result = '';
        foreach (func_get_args() as $arg) {
            $result .= print_r($arg, true);
        }
        return $result;
    }

    public function section($parameter)
    {
        global $Texy;
        switch ($parameter) {
            case 'showMessages':
                return Tools::showMessages(false);
//            case 'navigation':
//                return ProjectSpecific::pageNavigation();
//            case 'searchDialog':
//                return ProjectSpecific::searchDialog();
//            case 'favorites':
//                return 1; //$MyCMS->pageFavorites();
//            case 'compare':
//                return ProjectSpecific::itemComparison();
            case 'footer':
                return Template::templateTranslate($Texy->process($this->MyCMS->WEBSITE['footer']));
            default:
                $this->MyCMS->logger->warning("CustomFilter section called with undefined parameter: {$parameter}");
        }
        return $parameter;
    }
}
