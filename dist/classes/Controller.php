<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyController;
use GodsDev\Tools\Tools;
use GodsDev\mycmsprojectnamespace\ProjectSpecific;
use Tracy\Debugger;
use Tracy\ILogger;

class Controller extends MyController
{

    const TEMPLATE_NOT_FOUND = 'error404'; // SHOULD be same as in FriendlyURL
    const TEMPLATE_DEFAULT = 'home'; // SHOULD be same as in GodsDev\MyCMS\MyController and in FriendlyURL

    use \Nette\SmartObject;

    //project specific accepted attributes:

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var array */
    protected $sectionStyles;

    /** @var \GodsDev\mycmsprojectnamespace\ProjectSpecific */
    private $projectSpecific;

    /** @var string */
    protected $language = 'cs'; //default is Czech for MYCMSPROJECTSPECIFIC

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
        parent::__construct($MyCMS, $options);
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, ['language' => $this->language]);
    }

    /**
     * Simple method because of PHPUnit test
     *
     * @param array $options
     * @return mixed string when template determined, array with redir field when redirect
     * TODO: move to parent, i.e. to MyCMS\MyController, as public
     */
    public function determineTemplate(array $options = array())
    {
        //This is only placeholder as a preparation for a proper FriendlyURL mechanism
        return $this->MyCMS->template; // = 'home'; already set in MyControler
//        return $this->friendlyUrl->determineTemplate($options);
    }

    /**
     * Process $this->MyCMS->template after method determineTemplate
     * Set $this->MyCMS->context accordingly
     * May even change $this->MyCMS->template value
     *
     * @param array $options
     * @return boolean
     */
    private function prepareTemplate(array $options = [])
    {
        $this->verbose and Debugger::barDump($this->MyCMS->template, 'template used to prepareTemplate switch');
        switch ($this->MyCMS->template) {
            case self::TEMPLATE_DEFAULT: return true;
            case self::TEMPLATE_NOT_FOUND: return true;
            case 'line': return true; // line uses default home template
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
     * 301 Redirects to $redir (incl. relative) and die
     * TODO: move to parent, i.e. to MyCMS\MyController, as protected
     * 
     * @param string $redir
     */
    private function redir($redir)
    {
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION));
        }
        $sqlStatementsArray = $this->MyCMS->dbms->getStatementsArray();
        if (!empty($sqlStatementsArray)) {
            Debugger::getBar()->addPanel(new \GodsDev\MyCMS\Tracy\BarPanelTemplate('SQL: ' . count($sqlStatementsArray), $sqlStatementsArray));
        }
        $this->MyCMS->logger->info("Redir to {$redir} with SESSION[language]={$_SESSION['language']}");
        header("Location: {$redir}", true, 301); // For SEO 301 is much better than 303
        header('Connection: close');
        die('<script type="text/javascript">window.location=' . json_encode($redir) . ";</script>\n"
            . '<a href=' . urlencode($redir) . '>&rarr;</a>'
        );
    }

    /**
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     * 
     * @return array
     */
    public function controller()
    {
        $this->verbose and Debugger::barDump($this->language, 'Language on controller start');
        $result = parent::controller();

        //@todo refactor to use $result['template'] and ['context'] instead of $this->MyCMS->template and context ?
        $this->MyCMS->template = $result['template'];
        $this->MyCMS->context = $result['context'];
        $this->MyCMS->context['pageTitle'] = '';

        $options = array(
            'REQUEST_URI' => $this->requestUri,
        );

        // prepare variables and set templates for each kind of request
        $templateDetermined = $this->determineTemplate($options);

        // Note: $_SESSION['language'] je potřeba, protože to nastavuje stav jazyka pro browser
        // Note: $this->session je potřeba, protože je ekvivalentní proměnné $_SESSION, která je vstupem MyCMS->getSessionLanguage
        // Note: $this->language je potřeba, protože nastavuje jazyk v rámci instance Controller
        $this->session['language'] = $this->language; // = $this->friendlyUrl->getLanguage();
        $_SESSION['language'] = $this->MyCMS->getSessionLanguage(Tools::ifset($this->get, []), Tools::ifset($this->session, []), true); // Language is finally determined, therefore make the include creating TRANSLATION
        $this->MyCMS->logger->info("After determineTemplate: this->language={$this->language}, this->session['language']={$this->session['language']}, _SESSION['language']={$_SESSION['language']} this->get[language]=" . (isset($this->get['language']) ? $this->get['language'] : 'n/a'));
//        Debugger::barDump($this->get, 'get in controller after determineTemplate');
        if (is_string($templateDetermined)) {
            $this->MyCMS->template = $templateDetermined;
        } elseif (is_array($templateDetermined) && isset($templateDetermined['redir'])) {
            $this->redir($templateDetermined['redir']);
        }
        // PROJECT SPECIFIC CHANGE OF OPTIONS AFTER LANGUAGE IS DETERMINED
        $this->prepareTemplate($options);

        // PUT CONTROLLER CODE HERE

        if ($this->MyCMS->template === 'error404') {
            http_response_code(404);
        }

        return array(
            'template' => $this->MyCMS->template,
            'context' => $this->MyCMS->context,
        );
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
