<?php

namespace GodsDev\MyCMS;

use GodsDev\MyCMS\MyFriendlyUrl;
use GodsDev\MyCMS\Tracy\BarPanelTemplate;
use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Webmozart\Assert\Assert;

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

    /**
     * HTTP request parameters
     *
     * @var array
     */
    protected $get;

    /** @var string */
    protected $language = DEFAULT_LANGUAGE;

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var array */
    protected $session;

    /**
     * Friendly URL instance MAY be passsed from project Controller.
     * It is eventually instantiated in Controller in order to use project specific methods.
     *
     * @var \GodsDev\MyCMS\MyFriendlyUrl
     */
    protected $friendlyUrl;

    /**
     *
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overrides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        $this->result = [
            'template' => self::TEMPLATE_DEFAULT,
            'context' => ($this->MyCMS->context ? $this->MyCMS->context : [
            'pageTitle' => '',
            ])
        ];
        if (isset($this->friendlyUrl) && ($this->friendlyUrl instanceof MyFriendlyUrl)) {
            if (substr($this->friendlyUrl->applicationDir, -1) === '/') {
                throw new \Exception('applicationDir MUST NOT end with slash');
            }
            // so that URL relative to root may be constructed in latte (e.g. language selector)
            // $this->friendlyUrl->applicationDir never ends with / . Latte may use URL relative to domain root.
            // $this->MyCMS->context['applicationDir'] always ends with /
            $this->result['context']['applicationDir'] = $this->friendlyUrl->applicationDir . '/';
        }
    }

    /**
     * Kept only for backward compatibility for apps using 0.3.15 or older; to be replaced by run()
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     *
     * Expected in Controller:
     * $this->friendlyUrl->determineTemplate($options);
     * $this->prepareTemplate($options);
     * $this->prepareAllTemplates($options);
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
            'get' => $this->get,
            'session' => $this->session,
        ];
    }

    /**
     * To be defined in child
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
     * To be defined in child
     * Processes $this->MyCMS->template after method determineTemplate
     * Set $this->MyCMS->context accordingly for single templates
     * May even change $this->MyCMS->template value
     *
     * @param array $options ['REQUEST_URI']
     * @return bool true on success, false on error
     */
    protected function prepareTemplate(array $options = [])
    {
        return true;
    }

    /**
     * 301 Redirects to $redir (incl. relative) and die
     *
     * @param string $redir
     * @param int $httpCode Default is 301 as for SEO 301 is much better than 303
     */
    protected function redir($redir, $httpCode = 301)
    {
        Assert::inArray($httpCode, [301, 302, 303], 'Unauthorized redirect type %s');
        if (isset($_SESSION['user'])) {
            Debugger::getBar()->addPanel(new BarPanelTemplate('User: ' . $_SESSION['user'], $_SESSION));
        }
        if (!empty($this->MyCMS->dbms->getStatementsArray())) {
            Debugger::getBar()->addPanel(
                new BarPanelTemplate(
                    'SQL: ' . count($this->MyCMS->dbms->getStatementsArray()),
                    $this->MyCMS->dbms->getStatementsArray()
                )
            );
        }
        $this->MyCMS->logger->info("Redir to {$redir} with SESSION[language]={$_SESSION['language']}");
        header("Location: {$redir}", true, $httpCode); // Note: for SEO 301 is much better than 303
        header('Connection: close');
        die(
            '<script type="text/javascript">window.location=' . json_encode($redir) . ";</script>\n"
            . '<a href=' . urlencode($redir) . '>&rarr;</a>'
        );
    }

    /**
     * Determines template, set Session language, runs prepareTemplate for single template and prepareAllTemplates
     * for general transformations
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     *
     * @return array
     */
    public function run()
    {
        $this->verboseBarDump($this->language, 'Language on controller start');
        $this->MyCMS->template = $this->result['template'];
        $this->MyCMS->context = $this->result['context'];

        $options = ['REQUEST_URI' => $this->requestUri,];

        // prepare variables and set templates for each kind of request
        // Note: $this->MyCMS->template = 'home'; already set in MyControler
        $templateDetermined = $this->friendlyUrl->determineTemplate($options);
        // so that the FriendlyURL translation to parametric URL is taken into account
        $this->get = $this->friendlyUrl->getGet();
        // Note: $_SESSION['language'] je potřeba, protože to nastavuje stav jazyka pro browser
        // Note: $this->session je potřeba, protože je ekvivalentní proměnné $_SESSION,
        // která je vstupem MyCMS->getSessionLanguage
        // Note: $this->language je potřeba, protože nastavuje jazyk v rámci instance Controller
        $this->session['language'] = $this->language = $this->friendlyUrl->getLanguage();
        $_SESSION['language'] = $this->MyCMS->getSessionLanguage(
            Tools::ifset($this->get, []),
            Tools::ifset($this->session, []),
            true // Language is finally determined, therefore make the include creating TRANSLATION
        );
        $this->MyCMS->context['applicationDirLanguage'] = $this->MyCMS->context['applicationDir']
            . (($_SESSION['language'] === DEFAULT_LANGUAGE) ? '' : ($_SESSION['language'] . '/'));
        $this->MyCMS->logger->info("After determineTemplate: this->language={$this->language}, "
            . "this->session['language']={$this->session['language']}, _SESSION['language']={$_SESSION['language']} "
            . "this->get[language]=" . (isset($this->get['language']) ? $this->get['language'] : 'n/a'));
        $this->verboseBarDump([
            'get' => $this->get,
            'templateDetermined' => $templateDetermined, 'friendlyUrl->get' => $this->friendlyUrl->getGet()
            ], 'get in controller after determineTemplate');
        if (is_string($templateDetermined)) {
            $this->MyCMS->template = $templateDetermined;
        } elseif (is_array($templateDetermined) && isset($templateDetermined['redir'])) {
            $this->redir($templateDetermined['redir'], $templateDetermined['httpCode']);
        }

        // PROJECT SPECIFIC CHANGE OF OPTIONS AFTER LANGUAGE IS DETERMINED
        $this->prepareTemplate($options);

        // TYPICAL CONTROLLER CODE
        $this->prepareAllTemplates($options);

        if ($this->MyCMS->template === self::TEMPLATE_NOT_FOUND) {
            http_response_code(404);
        }

        return [
            'template' => $this->MyCMS->template,
            'context' => $this->MyCMS->context,
        ];
    }
}
