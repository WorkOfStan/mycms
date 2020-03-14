<?php
namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;
use Tracy\Debugger;

class MyFriendlyUrl extends MyCommon
{

    const PAGE_NOT_FOUND = '404';
    const PARSE_PATH_PATTERN = '~/(en\/|de/|cn/)?(.*/)?.*?~';
    const TEMPLATE_NOT_FOUND = 'error404'; // SHOULD be same as in Controller
    const TEMPLATE_DEFAULT = 'home'; // SHOULD be same as in GodsDev\MyCMS\MyController and in Controller and in config.php

    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var array */
    protected $get;

    /**
     * used in friendlyIdentifyRedirect
     * 
     * @var array
     */
    protected $session;

    /** @var string */
    protected $requestUri = ''; //default is homepage

    /** @var string */
    protected $language = DEFAULT_LANGUAGE; //default is Czech

    /** @var string */
    protected $userAgent = '';

    /**
     * URL fragement of application directory
     * so that URL relative to root may be constructed in latte (e.g. language selector)
     * It never ends with /
     * 
     * @var string
     */
    public $applicationDir = null;

    /**
     * Bleeds information within determineTemplate method
     * 0 - nothing, 1 - Debugger::barDump(), 2 - var_dump()
     * 
     * @var bool
     */
    protected $verbose = 1;

    /**
     * Controller ascertain what the request is
     *
     * Expect variables: $MyCMS->template, context, logger, SETTINGS
     * Expect constants: PATH_MODULE, TAB_PREFIX
     *
     * @param MyCMS $MyCMS
     * @param array $options overides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $this->verboseBarDump($options, 'friendlyUrl instantiated'));
        $this->verboseBarDump($this->applicationDir = (pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME) === '/' ? '' : pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME)), 'applicationDir'); // so that URL relative to root may be constructed in latte (e.g. language selector) It never ends with /
    }

    /**
     * Dumps information about a variable in Tracy Debug Bar.
     * or dumps it to standard output
     * 
     * @param  mixed  $var
     * @param  string $title
     * @param  array  $options
     * @return mixed  variable itself
     */
    protected function verboseBarDump($var, $title = null, array $options = [])
    {
        if ($this->verbose > 1) {
            var_dump("{$title}:", $var);
        } elseif ($this->verbose == 1) {
            return Debugger::barDump($var, $title, $options);
        }
        return $var;
    }

    /**
     * Wrapper for creating redir response
     * 
     * @param string $url
     * @param string $barDumpTitle
     * @return array with redir string field
     */
    protected function redirWrapper($url, $barDumpTitle)
    {
        return ['redir' => $this->verboseBarDump($this->applicationDir . $url, 'redir identified: ' . $barDumpTitle)];
    }

    /**
     * Set FRIENDLY URL & Redirect variables, triggers redirect
     * Constant REDIRECTOR_ENABLED expected
     * 
     * @param array $options
     * @return mixed boolean or array
     */
    protected function friendlyIdentifyRedirect(array $options = array())
    {
        $this->verboseBarDump($url = parse_url($options['REQUEST_URI']), 'parse_url');
        $this->verboseBarDump($token = $this->MyCMS->escapeSQL(pathinfo($url['path'], PATHINFO_FILENAME)), 'token');
        if ($token === self::PAGE_NOT_FOUND) {
            $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
            return true;
        }
        $this->verboseBarDump($interestingPath = ( substr($url['path'], 0, strlen($this->applicationDir)) === $this->applicationDir ) ? (substr($url['path'], strlen($this->applicationDir))) : $url['path'], 'interestingPath');

        if ($this->verboseBarDump(FORCE_301, 'FORCE_301') && !empty($this->verboseBarDump($friendlyUrl = $this->friendlyfyUrl(isset($url['query']) ? '?' . $url['query'] : ''), 'friendlyUrl'))) {
            if (($friendlyUrl != ('?' . $url['query']))) {
                $this->verboseBarDump($addLanguageDirectory = ($this->language != DEFAULT_LANGUAGE) // other than default language should have its directory
                    && !preg_match("~^{$this->language}/~", $friendlyUrl), 'addLanguageDirectory 301'); // unless the friendlyURL already has it
                return $this->redirWrapper(($addLanguageDirectory ? '/' . $this->language : '') . '/' . $friendlyUrl, 'SEO Force 301 friendly');
            } elseif ($interestingPath != '/') {
                return $this->redirWrapper('/' . $friendlyUrl, 'SEO Force 301 parametric');
            }
        }

        $matches = [];
        $matchResult = preg_match(self::PARSE_PATH_PATTERN, $interestingPath, $matches);
        $this->verboseBarDump($matches, 'folderInPath matches');
        $this->verboseBarDump($matchResult, 'match result (1=pattern matches given subject, 0=it does not, or FALSE=error)');
        if (isset($matches[1]) && !(substr($interestingPath, 0, strlen('/assets/')) === '/assets/')) { // non-existent page resources SHOULD NOT change the web language to the default
            // Note: Controller MUST request  the current values of $this->language and $this->session['language']
            $this->language = $this->MyCMS->getSessionLanguage(['language' => substr($matches[1], 0, 2)], $this->session, false); // transforms 'en/' to 'en' //$makeInclude=false as $this->MyCMS->TRANSLATION is already set.
            $this->verboseBarDump($this->language, 'Language reset according to path');
        }
        // If there is a redirect specified
        if (REDIRECTOR_ENABLED && $this->verboseBarDump(($found = $this->MyCMS->fetchSingle('SELECT `new_url` FROM ' . TAB_PREFIX . 'redirector WHERE `old_url`="' . $interestingPath . '" AND `active` = "1"')), 'found redirect')) {
            // Multiple directories, such as /spolecnost/tiskove-centrum/logo-ke-stazeni.html -> /index.php?category&id=14
            return $this->redirWrapper($found, 'old to new redirector');
        }

        // If there is more (non language) folders, the base of relative URLs would be incorrect, therefore either redirect to a base URL with query parameters or to a 404 Page not found.
        if ($this->verboseBarDump(isset($matches[2]), 'folderInPath')) {
            $this->verboseBarDump($addLanguageDirectory = FRIENDLY_URL && ($this->language != DEFAULT_LANGUAGE) // other than default language should have its directory
                && !preg_match("~/{$this->language}/~", $this->requestUri), 'addLanguageDirectory many folders'); // unless the page already has it
            return isset($url['query']) ? $this->redirWrapper('/?' . $url['query'], 'complex URL with params') : $this->redirWrapper(($addLanguageDirectory ? "/{$this->language}/" : '/') . self::PAGE_NOT_FOUND . '?url=' . $interestingPath, '404 for complex unknown URL');
        }
        return compact('token', 'matches');
    }

    /**
     * Determines which template will be used (or redirect should be performed)
     * 
     * How does it work:
     * If the URL matches redirector/old_url, then ['redir' => redirector/new_url] is returned so that 301 redirect may be performed.
     * First match of parametes returns flow back to self::controller().
     * If friendly URL is matched, then get parameters are set and this method is called recursively (to find match of parameters.)
     * 
     * Note:
     * The default $this->MyCMS->template = 'home'; already set in MyControler
     * $this->MyCMS->context and $this->MyCMS->template will be changed
     *
     * TODO determineTemplate bude metodou class FriendlyUrl where $templateAssignementParametricRules instantiates it
     * $this->MyCMS->templateAssignementParametricRules is array where key is get parameter and value is array of 'template' => template-name and optionally (bool)'idcode' if value is not in $_GET[key] but either in (int)id or (string)code GET parameters
     * 
     * @param array $options OPTIONAL verbose==true bleeds info to standard output
     * @return mixed string when template determined, array with redir field when redirect
     */
    public function determineTemplate(array $options = array())
    {
        $this->verbose = isset($options['verbose']) && ($options['verbose'] === true);
        $this->verboseBarDump($options, 'determineTemplate options');
        $this->verboseBarDump($this->get, 'get to determineTemplate');

        //FRIENDLY URL & Redirect variables
        $friendlyUrlRedirectVariables = $this->friendlyIdentifyRedirect($options);
        if (is_bool($friendlyUrlRedirectVariables) || isset($friendlyUrlRedirectVariables['redir'])) {
            return $friendlyUrlRedirectVariables;
        }
        //$token, $matches - will be expected below for FRIENDLY URL & Redirect calculation
        $token = $friendlyUrlRedirectVariables['token'];
        $matches = $friendlyUrlRedirectVariables['matches'];

        foreach ($this->MyCMS->templateAssignementParametricRules as $getParam => $assignement) {
            if (!isset($this->get[$getParam])) { // skip irrelevant rules
                continue;
            }
            $this->verboseBarDump($assignement, 'assignement loop');
            $this->verboseBarDump("{$getParam} may lead to '{$assignement['template']}' template", 'template assignement');
            if (!isset($assignement['idcode']) || $assignement['idcode'] === false) {
                return $this->verboseBarDump($assignement['template'], 'assignement established from get parameter name');
            }
            if (isset($this->get['id']) || isset($this->get['code'])) {
                if (isset($this->get['id'])) {
                    $this->get['id'] = filter_var($tempGetId = $this->get['id'], FILTER_VALIDATE_INT, array('default' => 0, 'min_range' => 0, 'max_range' => 1e9));
                    if (!$this->get['id']) {
                        $this->MyCMS->logger->error($this->verboseBarDump("this->get['id'] {$tempGetId} did not pass number filter"), "get id did not pass filter");
                        return self::TEMPLATE_NOT_FOUND;
                    }
                }
                return $this->verboseBarDump($assignement['template'], 'assignement established from id or code parameter');
            }
        }

        //FRIENDLY URL & Redirect calculation where $token, $matches are expected from above
        $pureFriendlyUrl = $this->pureFriendlyUrl($options, $token, $matches);
        if (!is_null($pureFriendlyUrl)) {
            return $pureFriendlyUrl;
        }

        // URL token not found
        $this->MyCMS->logger->error("404 Not found - {$options['REQUEST_URI']} and token=`{$token}`");
        if ($this->verbose) {
            echo "No condition catched the input.";
        }
        return self::TEMPLATE_NOT_FOUND;
    }

    /**
     * FRIENDLY URL & Redirect calculation
     * 
     * @param array $options
     * @param string $token calculated by friendlyIdentifyRedirect
     * @param array $matches calculated by friendlyIdentifyRedirect
     * @return mixed boolean or array
     */
    private function pureFriendlyUrl(array $options, $token, array $matches)
    {
        if (in_array($token, array_merge(array(HOME_TOKEN, '', 'index'), array_keys($this->MyCMS->TRANSLATIONS)))) {
            return self::TEMPLATE_DEFAULT;
        }

        // If there is a pure friendly URL
        if (!isset($matches[1])) {
            $this->language = DEFAULT_LANGUAGE;
            $this->verboseBarDump($this->language, 'Language reset to DEFAULT');
        }
        $found = $this->findFriendlyUrlToken($token);
        $this->verboseBarDump($found, 'found');
        if ($found) {
            $this->verboseBarDump($found, 'found friendly URL');
            $this->get[$found['type']] = $this->get['id'] = $found['id'];
            $this->verboseBarDump($this->get, 'this->get within pureFriendlyUrl');
            return $this->determineTemplate($options);
        }
        return null; //null does not trigger any reaction
    }

    /**
     * Project specific function that SHOULD be overidden in child class
     * SQL statement searching for $token in url_LL column of table product
     * 
     * @param string $token
     * @return mixed null on failure or string on success
     */
    protected function findFriendlyUrlToken($token)
    {
        return null;
    }

    /**
     * Project specific function that SHOULD be overidden in child class
     * Returns Friendly Url string for type=id URL if it is available
     * 
     * @param string $outputKey
     * @param string $outputValue
     * @return mixed null or string
     */
    protected function switchParametric($outputKey, $outputValue)
    {
        return null;
    }

    /**
     * Returns URL according to FRIENDLY_URL and url_XX settings based on $this->MyCMS->templateAssignementParametricRules
     * Handles not only param=value but also param&id=value or param&code=value
     * 
     * @param string $params
     * @return string
     */
    public function friendlyfyUrl($params)
    {
        $this->verboseBarDump($params, 'friendlyfyUrl params');
        if (!FRIENDLY_URL || empty($params)) {
            return $params;
        }
        parse_str((substr($params, 0, 1) === '?') ? substr($params, 1) : $params, $output); // $output contains all parameters
        $output2 = array_slice($output, 0, 1); // $output2 contains only the first parameter
//        $this->verboseBarDump($output, 'friendlyfyUrl output');
//        $this->verboseBarDump($output2, 'friendlyfyUrl output2');
        $output2Array = array_keys($output2);
        $outputKey = reset($output2Array);
//        $this->verboseBarDump($this->MyCMS->templateAssignementParametricRules, 'fU tAPR');
        $outputValue = (isset($this->MyCMS->templateAssignementParametricRules[$outputKey]) && isset($this->MyCMS->templateAssignementParametricRules[$outputKey]['idcode']) && $this->MyCMS->templateAssignementParametricRules[$outputKey]['idcode']) ?
            (isset($output['id']) ? (int) ($output['id']) : (string) Tools::ifset($output['code'], '')) : $output2[$outputKey];

        $result = $this->switchParametric($outputKey, $outputValue);
        $this->MyCMS->logger->info($this->verboseBarDump("{$params} friendlyfyUrl to " . print_r($result, true), 'friendlyfyUrl result'));
        return is_null($result) ? $params : $result;
    }

    /**
     * $this->get may be changed and Controller needs to know
     * 
     * @return array
     */
    public function getGet()
    {
        return $this->get;
    }

    /**
     * $this->language may be changed and Controller needs to know
     * 
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

}
