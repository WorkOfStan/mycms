<?php

namespace GodsDev\MyCMS;

use GodsDev\Tools\Tools;
use Tracy\Debugger;

class MyFriendlyUrl extends MyCommon
{

    const PAGE_NOT_FOUND = '404';
    const PARSE_PATH_PATTERN = '~/(en\/|de/|cn/)?(.*/)?.*?~';

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
        parent::__construct($MyCMS, $options);
        Debugger::barDump($options, 'friendlyUrl instantiated');
        $this->verboseBarDump($this->applicationDir = (pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME) === '/' ? '' : pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME)), 'applicationDir'); // so that URL relative to root may be constructed in latte (e.g. language selector) It never ends with `/'
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
     * Token and matches example:
     * URL=http://localhost:8080/godsdev/stockpiler/a/c/delete-parseurl.php?var=%C3%A9x&c=3#fdfd
     * token=delete-parseurl
     * matches = [ 
     *   0 => "/a/c/" ... the text that matched the full pattern
     *   1 => "" ... language subpattern
     *   2 => "a/c/"  ... rest of the path subpattern
     * ]
     * matchResult = (1=pattern matches `PARSE_PATH_PATTERN`, 0=it does not, or FALSE=error)
     * 
     * @param array $options
     * @return mixed 1) bool (true) or 2) array with redir string field or 3) array with token string field and matches array field (see above)
     */
    protected function friendlyIdentifyRedirect(array $options = [])
    {
        $this->verboseBarDump($url = parse_url($options['REQUEST_URI']), 'friendlyIdentifyRedirect: parse_url');
        $this->verboseBarDump($token = $this->MyCMS->escapeSQL(pathinfo($url['path'], PATHINFO_FILENAME)), 'friendlyIdentifyRedirect: token');
        //PAGE NOT FOUND
        if ($token === self::PAGE_NOT_FOUND) {
            $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
            return true;
        }
        //part of PATH beyond applicationDir
        $this->verboseBarDump($interestingPath = ( substr($url['path'], 0, strlen($this->applicationDir)) === $this->applicationDir ) ? (substr($url['path'], strlen($this->applicationDir))) : $url['path'], 'friendlyIdentifyRedirect: interestingPath');

        //if FORCE_301 set as true and it is possible, redir to Friendly URLs (if FRIENDLY_URL and set) to Friendly URLs or to simple parametric URLs (type=id) //TODO: explain better
        if ($this->verboseBarDump(FORCE_301, 'FORCE_301') && !empty($this->verboseBarDump($friendlyUrl = $this->friendlyfyUrl(isset($url['query']) ? '?' . $url['query'] : ''), 'friendlyIdentifyRedirect: friendlyUrl'))) {
            if (($friendlyUrl != ('?' . $url['query']))) {
                $this->verboseBarDump($addLanguageDirectory = ($this->language != DEFAULT_LANGUAGE) // other than default language should have its directory
                    && !preg_match("~^{$this->language}/~", $friendlyUrl), 'friendlyIdentifyRedirect: addLanguageDirectory 301'); // unless the friendlyURL already has it
                return $this->redirWrapper(($addLanguageDirectory ? '/' . $this->language : '') . '/' . $friendlyUrl, 'SEO Force 301 friendly');
            } elseif ($interestingPath != '/') {
                return $this->redirWrapper('/' . $friendlyUrl, 'SEO Force 301 parametric');
            }
        }

        $matches = [];
        $matchResult = preg_match(self::PARSE_PATH_PATTERN, $interestingPath, $matches);
        $this->verboseBarDump($matches, 'friendlyIdentifyRedirect: folderInPath matches');
        $this->verboseBarDump($matchResult, 'friendlyIdentifyRedirect: match result (1=pattern matches given subject, 0=it does not, or FALSE=error)');
        //language reset if path requires it
        if (isset($matches[1]) && !(substr($interestingPath, 0, strlen('/assets/')) === '/assets/')) { // non-existent page resources SHOULD NOT change the web language to the default
            // Note: Controller MUST request the current values of $this->language and $this->session['language']
            $this->language = $this->MyCMS->getSessionLanguage(['language' => substr($matches[1], 0, 2)], $this->session, false); // transforms 'en/' to 'en' //$makeInclude=false as $this->MyCMS->TRANSLATION is already set.
            $this->verboseBarDump($this->language, 'friendlyIdentifyRedirect: Language reset according to path');
        }
        // If there is a redirect specified
        if (REDIRECTOR_ENABLED && $this->verboseBarDump(($found = $this->MyCMS->fetchSingle('SELECT `new_url` FROM ' . TAB_PREFIX . 'redirector WHERE `old_url`="' . $interestingPath . '" AND `active` = "1"')), 'friendlyIdentifyRedirect: found redirect')) {
            // Multiple directories, such as /spolecnost/tiskove-centrum/logo-ke-stazeni.html -> /index.php?category&id=14
            return $this->redirWrapper($found, 'old to new redirector');
        }

        // If there are more (non language) folders, the base of relative URLs would be incorrect, therefore either redirect to a base URL with query parameters or to a 404 Page not found.
        if ($this->verboseBarDump(isset($matches[2]), 'friendlyIdentifyRedirect: folderInPath')) {
            $this->verboseBarDump($addLanguageDirectory = FRIENDLY_URL && ($this->language != DEFAULT_LANGUAGE) // other than default language should have its directory
                && !preg_match("~/{$this->language}/~", $this->requestUri), 'friendlyIdentifyRedirect: addLanguageDirectory many folders'); // unless the page already has it
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
     * The default template already set in MyControler as `$this->MyCMS->template = 'home';
     * $this->MyCMS->templateAssignementParametricRules is array where key is get parameter and value is array of 'template' => template-name and optionally (bool)'idcode' if value is not in $_GET[key] but either in (int)id or (string)code GET parameters
     * 
     * @param array $options OPTIONAL verbose==true bleeds info to standard output
     * @return mixed string with name of the template when template determined, array with redir field when redirect, bool when default template SHOULD be used
     */
    public function determineTemplate(array $options = [])
    {
        $this->verboseBarDump(['options' => $options, 'get' => $this->get], 'FriendlyURL: determineTemplate options and HTTP request parameters');

        //FRIENDLY URL & Redirect variables
        $friendlyUrlRedirectVariables = $this->friendlyIdentifyRedirect($options);
        if (is_bool($friendlyUrlRedirectVariables) || isset($friendlyUrlRedirectVariables['redir'])) {
            return $friendlyUrlRedirectVariables;
        }
        //$token, $matches - will be expected below for FRIENDLY URL & Redirect calculation (see friendlyIdentifyRedirect PHPDoc for explanation)
        $token = $friendlyUrlRedirectVariables['token'];
        $matches = $friendlyUrlRedirectVariables['matches'];

        //template assigned based on 'templateAssignementParametricRules' and id/code presence checked
        foreach ($this->MyCMS->templateAssignementParametricRules as $getParam => $assignement) {
            if (!isset($this->get[$getParam])) { // skip irrelevant rules
                continue;
            }
            $this->verboseBarDump($assignement, 'determineTemplate: assignement loop');
            $this->verboseBarDump("{$getParam} may lead to '{$assignement['template']}' template", 'determineTemplate: template assignement');
            if (!isset($assignement['idcode']) || $assignement['idcode'] === false) {
                return $this->verboseBarDump($assignement['template'], 'determineTemplate: assignement established from get parameter name');
            }
            if (isset($this->get['id']) || isset($this->get['code'])) {
                if (isset($this->get['id'])) {
                    $this->get['id'] = filter_var($tempGetId = $this->get['id'], FILTER_VALIDATE_INT, array('default' => 0, 'min_range' => 0, 'max_range' => 1e9));
                    if (!$this->get['id']) {
                        $this->MyCMS->logger->error($this->verboseBarDump("this->get['id'] {$tempGetId} did not pass number filter"), "get id did not pass filter");
                        return self::TEMPLATE_NOT_FOUND;
                    }
                }
                return $this->verboseBarDump($assignement['template'], 'determineTemplate: assignement established from id or code parameter');
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
            echo "No condition catched the input."; //TODO: localise (en, cs...)
        }
        return self::TEMPLATE_NOT_FOUND;
    }

    /**
     * FRIENDLY URL & Redirect calculation
     * 
     * @param array $options for recursive determineTemplate call
     * @param string $token calculated by friendlyIdentifyRedirect
     * @param array $matches calculated by friendlyIdentifyRedirect
     * @return mixed bool or array
     */
    private function pureFriendlyUrl(array $options, $token, array $matches)
    {
        //default scripts and language directories all result into the default template
        if (in_array($token, array_merge(array(HOME_TOKEN, '', 'index'), array_keys($this->MyCMS->TRANSLATIONS)))) {
            return self::TEMPLATE_DEFAULT;
        }

        // Language MUST always be set
        if (!isset($matches[1])) {
            $this->language = DEFAULT_LANGUAGE;
            $this->verboseBarDump($this->language, 'pureFriendlyUrl: Language reset to DEFAULT');
        }
        // If there is a pure friendly URL, i.e. the token exactly matches a record in content database, decode it to type=id
        $found = $this->findFriendlyUrlToken($token);
        $this->verboseBarDump($found, 'pureFriendlyUrl: found');
        if ($found) {
            $this->verboseBarDump($found, 'pureFriendlyUrl: found friendly URL');
            $this->get[$found['type']] = $this->get['id'] = $found['id'];
            $this->verboseBarDump($this->get, 'pureFriendlyUrl: this->get within pureFriendlyUrl');
            return $this->determineTemplate($options);
        }
        return null; //null does not trigger any reaction
    }

    /**
     * Project specific function that SHOULD be overidden in child class
     * SQL statement searching for $token in url_LL column of table(s) with content pieces addressed by FriendlyURL tokens
     * 
     * @param string $token
     * @return mixed null on empty result, false on database failure or one-dimensional array on success
     */
    protected function findFriendlyUrlToken($token)
    {
        return null;
    }

    /**
     * Project specific function that SHOULD be overidden in child class
     * Returns Friendly Url string for type=id URL if it is available or it returns type=id
     * 
     * @param string $outputKey `type`
     * @param string $outputValue `id`
     * @return mixed null (do not change the output) or string (URL - friendly or parametric)
     */
    protected function switchParametric($outputKey, $outputValue)
    {
        return null;
    }

    /**
     * Returns URL according to FRIENDLY_URL and url_XX settings based on $this->MyCMS->templateAssignementParametricRules
     * Handles not only param=value but also param&id=value or param&code=value
     * (int) id or (string) code are taken into account only if 'idcode' subfield of templateAssignementParametricRules is equal to `true`
     * 
     * @param string $params query key of parse_url, e.g  var1=12&var2=b
     * @return string query key of parse_url, e.g  var1=12&var2=b
     */
    public function friendlyfyUrl($params)
    {
        $this->verboseBarDump($params, 'friendlyfyUrl params');
        if (!FRIENDLY_URL || empty($params)) {
            return $params;
        }
        parse_str((substr($params, 0, 1) === '?') ? substr($params, 1) : $params, $output); // array $output contains all parameters
        $output2 = array_slice($output, 0, 1); // $output2 contains only the first parameter
        $output2Array = array_keys($output2);
        $outputKey = reset($output2Array);
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
