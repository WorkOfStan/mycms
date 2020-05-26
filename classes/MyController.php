<?php

namespace GodsDev\MyCMS;

/**
 * Controller ascertain what the request is
 * 
 * The default template is `home`
 */
class MyController extends MyCommon
{

    /** @var array */
    protected $result;

    /**
     * accepted attributes:
     */

    /** @var array */
    protected $get;

    /** @var array */
    protected $session;

    /**
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overrides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        $this->result = [
            'template' => 'home',
            'context' => ($this->MyCMS->context ? $this->MyCMS->context : [
            'pageTitle' => '',
            ])
        ];
    }

    /**
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     * Keep only for backward compatibility for apps using 0.3.15 or older; to be replaced by run()
     * 
     * @return array
     */
    public function controller()
    {
        return $this->result;
    }

    /**
     * For PHP Unit test
     * 
     * @return array
     */
    public function getVars()
    {
        return [
            "get" => $this->get,
            "session" => $this->session
                //,"section_styles" => $this->sectionStyles
        ];
    }
    
    /**
     * 301 Redirects to $redir (incl. relative) and die
     *
     * @param string $redir
     */
    protected function redir($redir)
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
     * Determines template, set Session language, runs prepareTemplate for single template and prepareAllTemplates for general transformations
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     * 
     * Expected in Controller:
     * $this->friendlyUrl->determineTemplate($options);
     * $this->prepareTemplate($options);
     * $this->prepareAllTemplates($options);
     * 
     * @return array
     */
    public function run()
    {
        $this->verbose and Debugger::barDump($this->language, 'Language on controller start');

        //@todo refactor to use $this->result['template'] and ['context'] instead of $this->MyCMS->template and context ?
        $this->MyCMS->template = $this->result['template'];
        $this->MyCMS->context = $this->result['context'];
        $this->MyCMS->context['pageTitle'] = '';

        $options = array(
            'REQUEST_URI' => $this->requestUri,
        );

        // prepare variables and set templates for each kind of request
        $templateDetermined = // $this->determineTemplate($options); //TODO: rovnou 
                              $this->friendlyUrl->determineTemplate($options);// 
                              ////Note: $this->MyCMS->template = 'home'; already set in MyControler
        //
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
        $this->prepareAllTemplates($options);

        if ($this->MyCMS->template === self::TEMPLATE_NOT_FOUND) { //TODO: remove this comment ... 'error404') {
            http_response_code(404); //TODO: zkontrolovat, že fakt 404!  i se změnou řádku výše
        }

        return array(
            'template' => $this->MyCMS->template,
            'context' => $this->MyCMS->context,
        );
    }
    

}
