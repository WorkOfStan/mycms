<?php

namespace WorkOfStan\MyCMS\Latte;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;
//use WorkOfStan\mycmsprojectnamespace\ProjectSpecific;
//use WorkOfStan\mycmsprojectnamespace\Template;
use WorkOfStan\MyCMS\MyCMS;

/**
 * MyCMS specific filters for Latte.
 */
class MyCustomFilters
{
    use \Nette\SmartObject;

    /** @var MyCMS */
    protected $MyCMS;

    /** var ProjectSpecific */
//    private $projectSpecific;

    /** @var callable|null */
    protected $translateMethod;

    /**
     *
     * @param MyCMS $MyCMS
     * @param callable $translateMethod to be used instead of the translate method within MyCMS
     * TODO consider constructing the class only with translate method and MyCMS as optional
     */
    public function __construct(MyCMS $MyCMS, $translateMethod = null)
    {
        $this->MyCMS = $MyCMS;
        $this->translateMethod = $translateMethod;
        //$this->projectSpecific = new ProjectSpecific($this->MyCMS);
    }

    /**
     *
     * @param string $filter
     * @param mixed $value
     * @deprecated 0.4.7 Latte::2.11.3 Notice: Engine::addFilter(null, ...) is deprecated, use addFilterLoader()
     * @see self::loader()
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
            $result = call_user_func_array($tempCallable, $args);
            Assert::string($result);
            return $result;
        }
    }

    /**
     * Filter loader expected by $latte->addFilterLoader()
     * @see https://latte.nette.org/en/develop#toc-filter-loaders
     * TODO: requires latte/latte::^2.10.8 which requires php: >=7.1 <8.2
     *
     * @param string $filter
     * @ todo return callable|null
     * @return array<$this|string>|null
     */
    public function loader(string $filter) //: ?callable
    {
        if (method_exists($this, $filter)) {
            return [$this, $filter];
        }
        return null;
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
        if (is_null($this->translateMethod)) {
            return $this->MyCMS->translate($text);
        }
        Assert::isCallable($this->translateMethod);
        $result = call_user_func($this->translateMethod, $text);
        Assert::string($result);
        return $result;
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
