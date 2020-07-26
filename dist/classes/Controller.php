<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyController;
use GodsDev\mycmsprojectnamespace\FriendlyUrl;
use GodsDev\mycmsprojectnamespace\ProjectSpecific;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;

class Controller extends MyController
{

    use \Nette\SmartObject;

    //project specific accepted attributes:

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var array */
    protected $sectionStyles; //TODO is needed? Probably remove from here and also MyCMS/dist.

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
     * $SECTION_STYLES
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
     * @param array $options
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
            case 'category':
                $this->MyCMS->context['content'] = $this->projectSpecific->getCatgory($this->get['id'], $this->get['code'], ['language' => $this->language]);//todo maybe options contain language field??
                return true;     
            case 'line': return true; // line uses default home template
            case 'product':
                $this->MyCMS->context['content'] = $this->projectSpecific->getContent($this->get['id'], $this->get['code'], ['language' => $this->language]);
                return true;
            case 'search-results': //search _GET[search] contains the search phrase
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
            'sectionStyles' => $this->sectionStyles
        ];
    }

}
