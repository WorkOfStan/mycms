<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyController;
use GodsDev\Tools\Tools;
use GodsDev\mycmsprojectnamespace\ProjectSpecific;
use Tracy\Debugger;

class Controller extends MyController
{

    use \Nette\SmartObject;

    //project specific accepted attributes:
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
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
        $this->projectSpecific = new ProjectSpecific($this->MyCMS, array('language' => $this->language));
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

        // Note: $_SESSION['language'] je potřeba, protože to nastavuje stav jazyka pro browser
        // Note: $this->session je potřeba, protože je ekvivalentní proměnné $_SESSION, která je vstupem MyCMS->getSessionLanguage
        // Note: $this->language je potřeba, protože nastavuje jazyk v rámci instance Controller
        $this->session['language'] = $this->language;// = $this->friendlyUrl->getLanguage();
        $_SESSION['language'] = $this->MyCMS->getSessionLanguage(Tools::ifset($this->get, []), Tools::ifset($this->session, []), true); // Language is finally determined, therefore make the include creating TRANSLATION

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
        return array("get" => $this->get, "session" => $this->session,
            "sectionStyles" => $this->sectionStyles
        );
    }

}
