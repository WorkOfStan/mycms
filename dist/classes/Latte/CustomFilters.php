<?php

namespace GodsDev\mycmsprojectnamespace\Latte;

use GodsDev\Tools\Tools;
//use GodsDev\mycmsprojectnamespace\ProjectSpecific;
//use GodsDev\mycmsprojectnamespace\Template;
use GodsDev\MyCMS\MyCMS;
use Webmozart\Assert\Assert;

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

    /**
     *
     * @param string $filter
     * @param mixed $value
     * @return string|void
     */
    public function common($filter, $value)
    {
        $args = func_get_args();
        array_shift($args);
        /* if (strtolower($filter) == 'showmessages') {
          return ProjectSpecific::$filter();
          } else */
        if (method_exists(__CLASS__, $filter)) {
            $tempCallable = [__CLASS__, $filter];
            Assert::isCallable($tempCallable);
            return call_user_func_array($tempCallable, $args);
        }
    }

    /**
     *
     * @param string $s
     * @param int $len
     * @return string
     */
    public static function shortify($s, $len = 10)
    {
        return mb_substr($s, 0, $len);
    }

    /**
     *
     * @param string $s
     * @return string
     */
    public static function firstLower($s)
    {
        return mb_strtolower(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    /**
     *
     * @param string $s
     * @return string
     */
    public static function webalize($s)
    {
        return Tools::webalize($s);
    }

    /**
     *
     * @param string $text
     * @return string
     */
    public function translate($text)
    {
        return $this->MyCMS->translate($text);
    }

    /**
     *
     * @param mixed $args
     * @return string
     */
    public static function vardump($args)
    {
        $result = '';
        foreach (func_get_args() as $arg) {
            $result .= print_r($arg, true);
        }
        return $result;
    }

    /**
     *
     * @param string $parameter
     * @return string
     */
    public function section($parameter)
    {
//        global $Texy;
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
//            ALSO UNUSED:
//            case 'footer':
//                return Template::templateTranslate($Texy->process($this->MyCMS->WEBSITE['footer']));
            default:
                $this->MyCMS->logger->warning("CustomFilter section called with undefined parameter: {$parameter}");
        }
        return $parameter;
    }
}
