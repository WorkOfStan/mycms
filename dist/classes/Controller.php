<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyController;
use GodsDev\mycmsprojectnamespace\FriendlyUrl;
use GodsDev\mycmsprojectnamespace\ProjectSpecific;
use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;

class Controller extends MyController
{

    use \Nette\SmartObject;

    //project specific accepted attributes:

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var \GodsDev\mycmsprojectnamespace\ProjectSpecific */
    private $projectSpecific;

    /** @var string */
    protected $httpMethod;

    /** @var string */
    protected $language = DEFAULT_LANGUAGE;

    /**
     * Feature flags that bubble down to latte and controller
     *
     * @var array
     */
    protected $featureFlags;

    /**
     * Bleeds information within determineTemplate method
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Controller ascertain what the request is
     *
     * Expect variables:
     * $MyCMS->template, context, logger, SETTINGS
     * $_SESSION
     * $_GET
     *
     * Expect constants:
     * PATH_MODULE
     * TAB_PREFIX
     *
     *
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options overrides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, array_merge($options, [
            'friendlyUrl' => new FriendlyUrl($MyCMS, $options), //$this->friendlyUrl instantiated
        ]));
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, ['language' => $this->language]);
        //Note: $this->featureFlags is populated
    }

    /**
     * Processes $this->MyCMS->template after method prepareTemplate
     * Set $this->MyCMS->context accordingly for all (or multiple) pages
     * Might even change $this->MyCMS->template value
     * Contains the typical controller code
     *
     * @param array $options
     * @return bool true on success, false on error
     */
    protected function prepareAllTemplates(array $options = [])
    {
        return true;
    }

    /**
     * Processes $this->MyCMS->template after method determineTemplate
     * Set $this->MyCMS->context accordingly for single templates
     * May even change $this->MyCMS->template value
     *
     * @param array $options ['REQUEST_URI']
     * @return bool true on success, false on error
     */
    protected function prepareTemplate(array $options = [])
    {
        $this->verboseBarDump($this->MyCMS->template, 'template used to prepareTemplate switch');
        Assert::inArray($this->httpMethod, ['GET', 'POST',], 'Unauthorized HTTP method %s');
        Debugger::barDump($requestMethod = $this->httpMethod, 'REQUEST_METHOD');
        switch ($this->MyCMS->template) {
            case self::TEMPLATE_DEFAULT: return true;
            case self::TEMPLATE_NOT_FOUND: return true;
            case 'article':
                Assert::integer((int) $this->get['id'], 'product MUST be identified by id');
                $this->MyCMS->context['article'] = $this->MyCMS->fetchSingle(
                    'SELECT id,'
                    . 'context,'
//                . 'category_id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description '
                    // TODO: Note: takto se do pole context[product] přidá field [link], který obsahuje potenciálně friendly URL, ovšem relativní, tedy bez jazyka. Je to příprava pro forced 301 SEO a pro hreflang funkcionalitu.
                    . ',' . $this->projectSpecific->getLinkSql('?article&id=', $this->language)
                    . ' FROM ' . TAB_PREFIX . 'content WHERE active="1" AND'
                    . ' type LIKE "article" AND'
//                . ' name_' . $this->language . ' NOT LIKE "" AND' // hide product language variants with empty title
                    . ' id=' . intval((int) $this->get['id']) . ' LIMIT 1'
                );
                $this->MyCMS->context['article']['context'] = json_decode($this->MyCMS->context['article']['context'], true); //decodes json so that article context may be used within template
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['article']['title'];
                $this->MyCMS->context['article']['image'] = array_key_exists('image', $this->MyCMS->context['article']['context']) ? (string) $this->MyCMS->context['article']['context']['image'] : '';
                return true;
            case 'category':
                if (!Tools::ifset($this->get['category'])) {
                    $categoryId = null;
                    $this->MyCMS->context['pageTitle'] = 'Categories'; // TODO localize // TODO content element
                    $this->MyCMS->context['content']['description'] = 'About all categories'; // TODO localize perex for all categories // TODO content element
                } else {
                    $this->MyCMS->context['content'] = $this->projectSpecific->getCategory(
                        Tools::ifset($this->get['category']),
                        null,
                        ['language' => $this->language]
                    );
                    Debugger::barDump($this->MyCMS->context['content'], 'category');
                    if (is_null($this->MyCMS->context['content'])) {
                        $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
                        return true;
                    }
                    $categoryId = $this->MyCMS->context['content']['category_id'];
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['content']['title'];
                }
                // TODO add perex for categories and products from content
                $this->verboseBarDump($categoryId, 'categoryId');
                $this->MyCMS->context['limit'] = PAGINATION_LIMIT;
                $this->MyCMS->context['list'] = $this->MyCMS->dbms->queryArray(
                    is_null($categoryId) ?
                    // list categories
                    ('SELECT id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description,'
                    . ' added'
                    . ' FROM `' . TAB_PREFIX . 'category` WHERE `active` = 1 ORDER BY sort ASC') :
                    // list products within category
                    ('SELECT id,'
                    . ' name_' . $this->language . ' AS title,'
                    . ' content_' . $this->language . ' AS description,'
                    . ' added'
                    . ' FROM `' . TAB_PREFIX . 'product` WHERE `category_id` = ' . $categoryId . ' AND `active` = 1'
                    . ' AND name_' . $this->language . ' NOT LIKE ""' // hide product language variants with empty title
                    . ' ORDER BY sort ASC')
                );
                $this->MyCMS->context['totalRows'] = count($this->MyCMS->context['list']);
                return true;
            case 'item-1':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 1';
                return true;
            case 'item-B':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 2';
                return true;
            case 'item-gama':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 3';
                return true;
            case 'item-4':
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Demo page') . ' 4';
                return true;
            case 'product':
                $this->MyCMS->context['product'] = $this->projectSpecific->getProduct((int) $this->get['id']);
                if (is_null($this->MyCMS->context['product'])) {
                    $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
                } else {
                    $this->MyCMS->context['pageTitle'] = $this->MyCMS->context['product']['title'];
                }
                return true;
            case 'search-results': //search _GET[search] contains the search phrase
                $this->MyCMS->context['limit'] = PAGINATION_LIMIT;
                $this->MyCMS->context['offset'] = isset($this->get['offset']) ? filter_var($this->get['offset'], FILTER_VALIDATE_INT, ['default' => 0, 'min_range' => 0, 'max_range' => 1e9]) : 0;
                $this->MyCMS->context['results'] = $this->projectSpecific->searchResults($this->get['search'], $this->MyCMS->context['offset'], $this->MyCMS->context['totalRows']);
                //@todo ošetřit empty result
                $this->MyCMS->context['pageTitle'] = $this->MyCMS->translate('Výsledky hledání');
                return true;
            default:
                Debugger::log("Undefined template {$this->MyCMS->template}", ILogger::ERROR);
        }
        return false;
    }

    /**
     * For PHP Unit test
     *
     * @return array
     */
    public function getVars()
    {
        return [
            'get' => $this->get,
            'session' => $this->session,
        ];
    }

}
