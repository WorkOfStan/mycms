<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyFriendlyUrl;
use GodsDev\mycmsprojectnamespace\ProjectSpecific;
//use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Tracy\ILogger;

class FriendlyUrl extends MyFriendlyUrl
{

    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var array */
    protected $get;

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var \GodsDev\mycmsprojectnamespace\ProjectSpecific */
    private $projectSpecific;

    /** @var string */
    protected $language = DEFAULT_LANGUAGE; //default is Czech

    /** @var string */
    protected $userAgent = '';

    /**
     * @param MyCMS $MyCMS
     * @param array $options overides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        // construct a regexp rule from array_keys($MyCMS->TRANSLATIONS) without DEFAULT_LANGUAGE
        $this->parsePathPattern = '~/(' . implode('/|', array_diff(array_keys($MyCMS->TRANSLATIONS), [DEFAULT_LANGUAGE])) . '/)?(.*/)?.*?~';
        //TODO consider injecting projectSpecific from Controller instead of creating new instance
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, [
            'language' => $this->language,
            'requestUri' => $this->requestUri,
        ]);
    }

    /**
     * Returns Friendly Url string for type=id URL if it is available or it returns type=id
     *
     * @param string $outputKey `type`
     * @param string $outputValue `id`
     * @return mixed null (do not change the output) or string (URL - friendly or parametric)
     */
    protected function switchParametric($outputKey, $outputValue)
    {
        Debugger::barDump("{$outputKey} => {$outputValue}", 'switchParametric started');
        $this->projectSpecific->language($this->language);
        switch ($outputKey) {
            case 'article':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?article&offset=" . (int) $this->get['offset'] : "?article";
                }
                $content = $this->MyCMS->dbms->fetchSingle(
                    'SELECT id, name_' . $this->language . ' AS name,'
                    . $this->projectSpecific->getLinkSql("?article&id=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active = 1 AND '
                    . (is_numeric($outputValue) ? ' id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"' : ' code LIKE "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"')
                );
                Debugger::barDump($content, 'content piece');
                return is_null($content) ? (self::PAGE_NOT_FOUND) : $content['link'];
            case 'category':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?category&offset=" . (int) $this->get['offset'] : "?category";
                }
                $content = $this->MyCMS->dbms->fetchSingle('SELECT id, name_' . $this->language . ' AS title,'
                    . $this->projectSpecific->getLinkSql("?category=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'category WHERE active = 1 '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($content, 'category');
                return is_null($content) ? (self::PAGE_NOT_FOUND) : $content['link'];
            case 'language':
                return null; // i.e. do not change the output or return "?{$outputKey}={$outputValue}";
            case 'product':
                $content = $this->projectSpecific->getProduct((int) $outputValue);
                Debugger::barDump($content, 'product');
                return is_null($content) ? (self::PAGE_NOT_FOUND) : $content['link'];
            default:
                Debugger::log("switchParametric: undefined friendlyfyUrl for {$outputKey} => {$outputValue}", ILogger::ERROR);
        }

        return null;
    }

}
