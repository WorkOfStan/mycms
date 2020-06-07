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
        //TODO consider injecting projectSpecific from Controller instead of creating new instance
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, [
            'language' => $this->language,
            'requestUri' => $this->requestUri,
        ]);
    }

    /**
     * SQL statement searching for $token in url_LL column of table(s) with content pieces addressed by FriendlyURL tokens
     * 
     * @param string $token
     * @return mixed null on empty result, false on database failure or one-dimensional array [id, type] on success
     */
    protected function findFriendlyUrlToken($token)
    {
        //A example
        return $this->MyCMS->fetchSingle('SELECT id,"product" AS type FROM ' . TAB_PREFIX . 'product WHERE url_' . $this->language . '="' . $token . '"
                    UNION SELECT id,"article" AS type FROM ' . TAB_PREFIX . 'content WHERE url_' . $this->language . '="' . $token . '"
                    UNION SELECT id,"category" AS type FROM ' . TAB_PREFIX . 'category WHERE url_' . $this->language . '="' . $token . '" AND path LIKE "' . $this->MyCMS->SETTINGS['PATH_HOME'] . '%"
                    UNION SELECT id,"line" AS type FROM ' . TAB_PREFIX . 'category WHERE url_' . $this->language . '="' . $token . '" AND path LIKE "' . $this->MyCMS->SETTINGS['PATH_CATEGORY'] . '%"');
        //F example
        return $this->MyCMS->fetchSingle('SELECT id,"product" AS type FROM ' . TAB_PREFIX . 'product WHERE active=1 AND url_' . $this->language . '="' . $token . '"
                    UNION SELECT id,"page" AS type FROM ' . TAB_PREFIX . 'content WHERE active=1 AND type LIKE "page" AND url_' . $this->language . '="' . $token . '"
                    UNION SELECT id,"news" AS type FROM ' . TAB_PREFIX . 'content WHERE active=1 AND type LIKE "news" AND url_' . $this->language . '="' . $token . '"');
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
        /*
        //A example
        $this->projectSpecific->setCategories();
        switch ($outputKey) {
            case "line":
                return (isset($this->MyCMS->context['categories'][(string) $outputValue]['link'])) ?
                    ($this->MyCMS->context['categories'][(string) $outputValue]['link']) : (self::PAGE_NOT_FOUND);
            case "product":
                $product = $this->MyCMS->dbms->fetchSingle('SELECT category_id, id, product_' . $this->language . ' AS product,'
                    . $this->projectSpecific->getLinkSql("?product=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'product WHERE active = 1 AND category_id IN (' . Tools::arrayListed($this->MyCMS->context['categories'], 8 | 128) . ') '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($product, 'product');
                return is_null($product) ? (self::PAGE_NOT_FOUND) : $product['link'];
            case 'language':
                return null; // i.e. do not change the output or return "?{$outputKey}={$outputValue}";
            //TODO: case ?category&code=career
            case 'article':
                $article = $this->projectSpecific->getContent(is_int($outputValue) ? $outputValue : null, is_int($outputValue) ? null : $outputValue, [
                    'PATH_HOME' => $this->MyCMS->SETTINGS['PATH_HOME'], // for $this->projectSpecific->getBreadcrumbs
                    'REQUEST_URI' => $this->requestUri
                ]);
                Debugger::barDump($article, 'article');
                return is_null($article) ? (self::PAGE_NOT_FOUND) : $article['link'];
//            default:
//                Debugger::log("undefined friendlyfyUrl for {$outputKey} => {$outputValue}", ILogger::ERROR);
        }
        // /A example
        //F example
        switch ($outputKey) {
            case 'news':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?news&offset=" . (int) $this->get['offset'] : "?news";
                }
                $news = $this->MyCMS->dbms->fetchSingle('SELECT id, page_' . $this->language . ' AS page,'
                    . $this->projectSpecific->getLinkSql("?news=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active = 1 '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($news, 'news');
                return is_null($news) ? (self::PAGE_NOT_FOUND) : $news['link'];
            case 'page':
                $page = $this->MyCMS->dbms->fetchSingle('SELECT id, page_' . $this->language . ' AS page,'
                    . $this->projectSpecific->getLinkSql("?page=", $this->language, 'link', null, 'code')
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active = 1 '
                    . ' AND code = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($page, 'page');
                return is_null($page) ? (self::PAGE_NOT_FOUND) : $page['link'];
            case 'product':
                $product = $this->MyCMS->dbms->fetchSingle('SELECT division_id, id, product_' . $this->language . ' AS product,'
                    . $this->projectSpecific->getLinkSql("?product=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'product WHERE active = 1 '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($product, 'product');
                return is_null($product) ? (self::PAGE_NOT_FOUND) : $product['link'];
            case 'language':
                return null; //or return "?{$outputKey}={$outputValue}";
            default:
                Debugger::log("undefined friendlyfyUrl for {$outputKey} => {$outputValue}", ILogger::ERROR);
        }
        // /F example
*/

        switch ($outputKey) {
            case 'article':
                if (empty($outputValue)) {
                    return isset($this->get['offset']) ? "?news&offset=" . (int) $this->get['offset'] : "?news";
                }
                $content = $this->MyCMS->dbms->fetchSingle('SELECT id, name_' . $this->language . ' AS name,'
                    . $this->projectSpecific->getLinkSql("?article=", $this->language)
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active = 1 '
                    . ' AND id = "' . $this->MyCMS->dbms->escapeSQL($outputValue) . '"');
                Debugger::barDump($content, 'content piece');
                return is_null($content) ? (self::PAGE_NOT_FOUND) : $content['link'];
            case 'language':
                return null; // i.e. do not change the output or return "?{$outputKey}={$outputValue}";
            default:
                Debugger::log("undefined friendlyfyUrl for {$outputKey} => {$outputValue}", ILogger::ERROR);
        }

        return null;
    }

}
