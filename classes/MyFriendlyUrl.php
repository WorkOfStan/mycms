<?php

namespace WorkOfStan\MyCMS;

use Exception;
use Tracy\Debugger;
use Webmozart\Assert\Assert;

class MyFriendlyUrl extends MyCommon
{
    use \Nette\SmartObject;

    public const PAGE_NOT_FOUND = '404';

    /**
     * interestingPath pattern to match `language subpattern` and the `rest of the path`
     * in the method friendlyIdentifyRedirect
     * It SHOULD be changed by the child class FriendlyUrl to use the languages used in the MyCMS application
     *
     * @var string
     */
    protected $parsePathPattern = '~/(de/|en/|zh/)?(.*/)?.*?~';

    /**
     * accepted attributes:
     */

    /** @var array<mixed> content of $_GET and $_POST */
    protected $get;

    /**
     * used in friendlyIdentifyRedirect
     *
     * @var array<mixed>
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
     * @param array<mixed> $options overides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        Debugger::barDump($options, 'friendlyUrl instantiated');
        // so that URL relative to root may be constructed in latte (e.g. language selector) It never ends with `/'
        $this->applicationDir = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME) === '/'
            ? '' : pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME);
        $this->verboseBarDump($this->applicationDir, 'applicationDir');
    }

    /**
     * Wrapper for creating redir response
     *
     * TODO: consider creating object redirWrapper that would be checked as instanceof instead of checking for presence
     * of field 'redir'. Only question is how to Debugger::getBar()->addPanel as done in MyController::redir
     *
     * @param string $url
     * @param string $barDumpTitle
     * @param int $httpCode
     * @return array<int|string> with redir string field
     */
    protected function redirWrapper($url, $barDumpTitle, $httpCode = 301)
    {
        $this->verboseBarDump(
            $this->applicationDir . $url,
            'redir identified: ' . $barDumpTitle
        );
        return ['redir' => $this->applicationDir . $url, 'httpCode' => $httpCode];
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
     * @param array<string> $options
     * @return array<int|string|array<string>>|true
     *     `bool (true)` when `TEMPLATE_NOT_FOUND` || `array<int,string>` with redir string field
     *     || `array` with token string field and matches array field (see above)
     * @throws Exception on malformed URL
     */
    protected function friendlyIdentifyRedirect(array $options = [])
    {
        $this->verboseBarDump($url = parse_url($options['REQUEST_URI']), 'friendlyIdentifyRedirect: parse_url');
        if ($url === false || !array_key_exists('path', $url)) {
            throw new Exception('Malformed url ' . (string) $options['REQUEST_URI']);
        }
        $this->verboseBarDump($token = $this->MyCMS->escapeSQL(
            pathinfo(
                $url['path'],
                PATHINFO_FILENAME
            )
        ), 'friendlyIdentifyRedirect: token');
        //PAGE NOT FOUND
        if ($token === self::PAGE_NOT_FOUND) {
            $this->MyCMS->template = self::TEMPLATE_NOT_FOUND;
            return true;
        }
        $this->verboseBarDump(
            ['FORCE_301' => FORCE_301, 'FRIENDLY_URL' => FRIENDLY_URL, 'REDIRECTOR_ENABLED' => REDIRECTOR_ENABLED,],
            'Constants'
        );
        //part of PATH beyond applicationDir
        $this->verboseBarDump($interestingPath = (substr(
            $url['path'],
            0,
            strlen(
                $this->applicationDir
            )
        ) === $this->applicationDir) ? (substr(
            $url['path'],
            strlen($this->applicationDir)
        )) : $url['path'], 'friendlyIdentifyRedirect: interestingPath');

        //if FORCE_301 set as true and it is possible, redir to Friendly URLs (if FRIENDLY_URL and set)
        //to Friendly URLs or to simple parametric URLs (type=id) //TODO: explain better
        if (
            FORCE_301 && !empty($this->verboseBarDump(
                $friendlyUrl = $this->friendlyfyUrl(isset($url['query']) ? '?' . $url['query'] : ''),
                'friendlyIdentifyRedirect: friendlyUrl'
            ))
        ) {
            if (isset($url['query']) && ($friendlyUrl != ('?' . $url['query']))) {
                // other than default language should have its directory
                $this->verboseBarDump(
                    $addLanguageDirectory = ($this->language != DEFAULT_LANGUAGE)
                    // unless the friendlyURL already has it
                    && !preg_match("~^{$this->language}/~", $friendlyUrl),
                    'friendlyIdentifyRedirect: addLanguageDirectory 301 friendly'
                );
                return $this->redirWrapper(
                    ($addLanguageDirectory ? '/' . $this->language : '') . '/' . $friendlyUrl,
                    'SEO Force 301 friendly'
                );
            } elseif ($interestingPath != '/' && $interestingPath != "/{$this->language}/") {
                // other than default language should have its directory
                $this->verboseBarDump(
                    $addLanguageDirectory = ($this->language != DEFAULT_LANGUAGE)
                    // unless the friendlyURL already has it
                    && !preg_match("~^language={$this->language}~", $friendlyUrl),
                    'friendlyIdentifyRedirect: addLanguageDirectory 301 parametric'
                );
                return $this->redirWrapper(
                    '/' . $friendlyUrl . ($addLanguageDirectory ? "&language={$this->language}" : ''),
                    'SEO Force 301 parametric'
                );
            }
        }

        $matches = [];
        $matchResult = preg_match($this->parsePathPattern, $interestingPath, $matches);
        $this->verboseBarDump([
            'parsePathPattern' => $this->parsePathPattern,
            'folderInPath matches' => $matches,
            'match result' => $matchResult
            ], 'match result (1=pattern matches given subject, 0=it does not, or FALSE=error)');
        //language reset if path requires it
        if (isset($matches[1]) && !(substr($interestingPath, 0, strlen('/assets/')) === '/assets/')) {
            // non-existent page resources SHOULD NOT change the web language to the default
            // transforms 'en/' to 'en' // $makeInclude=false as $this->MyCMS->TRANSLATION is already set.
            $this->verboseBarDump(
                $this->language = $this->MyCMS->getSessionLanguage(
                    ['language' => substr($matches[1], 0, 2)],
                    $this->session,
                    false
                ),
                'friendlyIdentifyRedirect: Language reset according to path'
            );
        } elseif (
            !isset($matches[1]) && FORCE_301 && ($this->language != DEFAULT_LANGUAGE)
            && (!isset($this->get['language']))
        ) {
            return $this->redirWrapper(
                $interestingPath . '?' . http_build_query(array_merge(['language' => DEFAULT_LANGUAGE], $this->get)),
                'SEO Force 302 language folder',
                302
            );
        }
        // If there is a redirect specified
        if (
            REDIRECTOR_ENABLED && $this->verboseBarDump(
                (
                    $found = $this->MyCMS->fetchSingle(
                        'SELECT `new_url` FROM `' . TAB_PREFIX . 'redirector` WHERE `old_url`="' . $interestingPath
                        . '" AND `active` = "1"'
                    )
                ),
                'friendlyIdentifyRedirect: found redirect'
            )
        ) {
            Assert::string($found);
            // Multiple directories,
            // such as /spolecnost/tiskove-centrum/logo-ke-stazeni.html -> /index.php?category&id=14
            return $this->redirWrapper($found, 'old to new redirector');
        }

        // If there are more (non language) folders, the base of relative URLs would be incorrect, therefore either
        // redirect to a base URL with query parameters or to a 404 Page not found.
        if ($this->verboseBarDump(isset($matches[2]), 'friendlyIdentifyRedirect: folderInPath')) {
            $this->verboseBarDump(
                $addLanguageDirectory = FRIENDLY_URL && $this->language != DEFAULT_LANGUAGE
                // other than default language should have its directory
                && !preg_match("~/{$this->language}/~", $this->requestUri),
                'friendlyIdentifyRedirect: addLanguageDirectory many folders'
            ); // unless the page already has it
            return isset($url['query']) ? $this->redirWrapper('/?' . $url['query'], 'complex URL with params')
                : $this->redirWrapper(($addLanguageDirectory ? "/{$this->language}/" : '/')
                    . self::PAGE_NOT_FOUND . '?url=' . $interestingPath, '404 for complex unknown URL');
        }
        $this->verboseBarDump(compact('token', 'matches'), 'friendlyIdentifyRedirect: return [token, matches]');
        return compact('token', 'matches');
    }

    /**
     * Checks rules against current get parameters
     *
     * @return string|null string template name on success, null on necessity to continue
     */
    private function parametricRuleToTemplate()
    {
        //template assigned based on 'templateAssignementParametricRules' and id/code presence checked
        foreach ($this->MyCMS->templateAssignementParametricRules as $getParam => $assignement) {
            if (!isset($this->get[$getParam]) && !isset($this->get[$getParam . '/'])) { // skip irrelevant rules
                continue;
            }
            $this->MyCMS->logger->info(print_r(
                $this->verboseBarDump(
                    $assignement,
                    'determineTemplate: assignement loop'
                ),
                true
            ));
            $this->MyCMS->logger->info($this->verboseBarDumpString(
                "{$getParam} may lead to '{$assignement['template']}' template",
                'determineTemplate: template assignement'
            ));
            if (!isset($assignement['idcode']) || $assignement['idcode'] === false) {
                Assert::string($assignement['template']);
                return $this->verboseBarDumpString(
                    $assignement['template'],
                    'determineTemplate: assignement established from get parameter name'
                );
            }
            if (isset($this->get['id']) || isset($this->get['code'])) {
                if (isset($this->get['id'])) {
                    $this->get['id'] = filter_var(
                        $tempGetId = $this->get['id'],
                        FILTER_VALIDATE_INT,
                        ['default' => 0, 'min_range' => 0, 'max_range' => 1e9]
                    );
                    Assert::scalar($tempGetId);
                    if (!$this->get['id']) {
                        $this->MyCMS->logger->error($this->verboseBarDumpString(
                            "this->get['id'] {$tempGetId} did not pass number filter",
                            "get id did not pass filter"
                        ));
                        return self::TEMPLATE_NOT_FOUND;
                    }
                }
                Assert::string($assignement['template']);
                return $this->verboseBarDumpString(
                    $assignement['template'],
                    'determineTemplate: assignement established from id or code parameter'
                );
            }
        }
        return null;
    }

    /**
     * Determines which template will be used (or redirect should be performed)
     *
     * How does it work:
     * If the URL matches redirector/old_url, then ['redir' => redirector/new_url] is returned
     * so that 301 redirect may be performed.
     * First match of parametes returns flow back to self::controller().
     * If friendly URL is matched, then get parameters are set and this method is called recursively
     * (to find match of parameters.)
     *
     * Note:
     * The default template already set in MyControler as `$this->MyCMS->template = 'home';
     * $this->MyCMS->templateAssignementParametricRules is array where key is get parameter and value is array
     * of 'template' => template-name and optionally (bool)'idcode' if value is not in $_GET[key]
     * but either in (int)id or (string)code GET parameters
     *
     * TODO: simplify management of TEMPLATE_NOT_FOUND result as currently it is indicated
     * as self::TEMPLATE_NOT_FOUND || null || true
     *
     * @param array<string> $options (TODO:rest of line valid??)OPTIONAL verbose==true bleeds info to standard output
     * @return string|array<int|string>|true `string` with name of the template when template determined
     *     || `array` with redir field when redirect || `bool (true)` when template set to `TEMPLATE_NOT_FOUND`
     */
    public function determineTemplate(array $options = [])
    {
        $this->verboseBarDump(
            ['options' => $options, 'get' => $this->get],
            'FriendlyURL: determineTemplate options and HTTP request parameters'
        );

        //FRIENDLY URL & Redirect variables
        $friendlyUrlRedirectVariables = $this->friendlyIdentifyRedirect($options);
        if (is_bool($friendlyUrlRedirectVariables)) {
            return $friendlyUrlRedirectVariables; // true
        }
        if (isset($friendlyUrlRedirectVariables['redir'])) {
            //var_dump($friendlyUrlRedirectVariables);exit;
            Assert::string($friendlyUrlRedirectVariables['redir']);
            Assert::integer($friendlyUrlRedirectVariables['httpCode']);
            // array<int|string>
            return [
                'redir' => $friendlyUrlRedirectVariables['redir'],
                'httpCode' => $friendlyUrlRedirectVariables['httpCode']
            ];
        }
        //$token, $matches - will be expected below for FRIENDLY URL & Redirect calculation
        //(see friendlyIdentifyRedirect PHPDoc for explanation)
        Assert::isArray($friendlyUrlRedirectVariables);
        $token = $friendlyUrlRedirectVariables['token'];
        Assert::string($token);
        $matches = $friendlyUrlRedirectVariables['matches'];
        Assert::isArray($matches);

        $parametricRuleToTemplate = $this->parametricRuleToTemplate();
        if (!is_null($parametricRuleToTemplate)) {
            return $parametricRuleToTemplate; // string
        }

        //FRIENDLY URL & Redirect calculation where $token, $matches are expected from above
        $pureFriendlyUrl = $this->pureFriendlyUrl($options, $token, $matches);
        if (!is_null($pureFriendlyUrl)) {
            $this->verboseBarDump($this->get, 'determineTemplate this->get before return pureFriendlyUrl');
            $this->verboseBarDumpString($pureFriendlyUrl, 'determineTemplate return pureFriendlyUrl');
            return $pureFriendlyUrl; // string
        }

        // URL token not found
        $this->MyCMS->logger->error("404 Not found - {$options['REQUEST_URI']} and token=`{$token}`");
        $this->verboseBarDump("No condition catched the input.", "determineTemplate fail");
        return self::TEMPLATE_NOT_FOUND; // string
    }

    /**
     * FRIENDLY URL & Redirect calculation
     *
     * @param array<mixed> $options for recursive determineTemplate call (TODO: isn't this obsolete?)
     * @param string $token calculated by friendlyIdentifyRedirect
     * @param array<string> $matches calculated by friendlyIdentifyRedirect
     * @return null|string
     *    `null` leads to self::TEMPLATE_NOT_FOUND (TODO: factor-out in favor of the constant? or true is redundant?)
     *    || `string` with name of the template when template determined
     *    NEVER OCCURS || `array<int,string>` with redir field when redirect
     *    NEVER OCCURS || `bool (true)` when template set to `TEMPLATE_NOT_FOUND`
     */
    private function pureFriendlyUrl(array $options, $token, array $matches)
    {
        //default scripts and language directories all result into the default template
        if (in_array($token, array_merge([HOME_TOKEN, '', 'index'], array_keys($this->MyCMS->TRANSLATIONS)))) {
            return $this->verboseBarDumpString(self::TEMPLATE_DEFAULT, 'pureFriendlyUrl return default');
        }

        // Language MUST be set
        if (!isset($matches[1])) {
            $this->language = DEFAULT_LANGUAGE;
            $this->verboseBarDumpString($this->language, 'pureFriendlyUrl: Language reset to DEFAULT');
        }
        // If there is a pure friendly URL, i.e. the token exactly matches a record in content database,
        // decode it internally to type=id
        $found = $this->findFriendlyUrlToken($token);
        $this->verboseBarDump($found, 'pureFriendlyUrl: found');
        if ($found) {
            $this->verboseBarDump($found, 'pureFriendlyUrl: found friendly URL');
            $this->get[$found['type']] = $this->get['id'] = $found['id'];
            $this->verboseBarDump($this->get, 'pureFriendlyUrl: this->get within pureFriendlyUrl');
            //TODO change the description as recursion is limited here
            $result = $this->parametricRuleToTemplate();
            $this->verboseBarDump(
                $result,
                'pureFriendlyUrl return parametricRuleToTemplate()'
            );
            return $result;
        }
        //null leads to self::TEMPLATE_NOT_FOUND
        $this->verboseBarDump(null, 'pureFriendlyUrl return null leads to self::TEMPLATE_NOT_FOUND');
        return null;
    }

    /**
     * Returns SQL statement for getting the content piece for a single content type
     *
     * @param string $token
     * @param string $type
     * @param string $table
     * @return string
     */
    private function prepareTableSelect($token, $type, $table)
    {
        return 'SELECT id,"' . $type . '" AS type FROM `' . TAB_PREFIX . $table . '` WHERE active=1 AND '
            // usually type is stored in a dedicated table of the same name,
            // otherwise a column type within the table is expected
            . ($type === $table ? '' : 'type like "' . $type . '" AND ')
            . 'url_' . $this->language . '="' . $token . '"';
    }

    /**
     * SQL statement finds $token in url_LL column of table(s) with content pieces addressed by FriendlyURL tokens
     * The UNION on tables, where type is stored in a dedicated table of the same name, otherwise a column type within
     * the table is expected is just the simplest way,
     * but SQL statement may be adapted in any way so this method MAY be overidden in child class
     *
     * @param string $token
     * @return null|array<string|null>
     *     null on empty result | one-dimensional array [id, type] on success
     *     Throws exception on database failure
     */
    protected function findFriendlyUrlToken($token)
    {
        Debugger::barDump(
            ['token' => $token, 'typeToTableMapping' => $this->MyCMS->typeToTableMapping],
            'findFriendlyUrlToken started'
        );
        if (empty($this->MyCMS->typeToTableMapping)) {
            return null;
        }
        foreach ($this->MyCMS->typeToTableMapping as $type => $table) {
            $output[] = $this->prepareTableSelect($token, $type, $table);
        }
        $result = $this->MyCMS->dbms->fetchSingle(implode(' UNION ', $output));
        if (is_null($result)) {
            return null;
        }
        Assert::isArray($result);
        return $result;
    }

    /**
     * Project specific function that SHOULD be overidden in child class
     * Returns Friendly Url string for type=id URL if it is available or it returns type=id
     * If the rule is not defined, then log info level message by backyard logger (if info level is logged)
     *
     * @param string $outputKey `type`
     * @param string $outputValue `id`
     * @return string|null
     *     null: do not change the output even if it means returning "?{$outputKey}={$outputValue}"
     *     string: URL - friendly or parametric
     */
    protected function switchParametric($outputKey, $outputValue)
    {
        return null;
    }

    /**
     * Returns URL according to FRIENDLY_URL and url_XX settings
     * based on $this->MyCMS->templateAssignementParametricRules
     * Handles not only param=value but also param&id=value or param&code=value
     * (int) id or (string) code are taken into account only if 'idcode' subfield
     * of templateAssignementParametricRules is equal to `true`
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
        parse_str((substr($params, 0, 1) === '?') ? substr($params, 1) : $params, $output);
        // array $output now contains all parameters
        $output2 = array_slice($output, 0, 1);
        // $output2 contains only the first parameter
        $output2Array = array_keys($output2);
        $outputKey = (string) reset($output2Array);
        $outputValue = (isset($this->MyCMS->templateAssignementParametricRules[$outputKey])
            && isset($this->MyCMS->templateAssignementParametricRules[$outputKey]['idcode'])
            && $this->MyCMS->templateAssignementParametricRules[$outputKey]['idcode'])
            ? (isset($output['id']) ? (int) ($output['id'])
            :
//            (string) Tools::ifset($output['code'], '')
            (isset($output['code']) ? $output['code'] : '')
            )
            : $output2[$outputKey];

        $result = $this->switchParametric($outputKey, $outputValue);
        $this->MyCMS->logger->info(
            $this->verboseBarDumpString(
                "{$params} friendlyfyUrl " . (is_null($result) ? 'unchanged' : 'to ' . print_r($result, true)),
                'friendlyfyUrl result'
            )
        );
        return is_null($result) ? $params : $result;
    }

    /**
     * $this->get may be changed and Controller needs to know
     *
     * @return array<mixed>
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
