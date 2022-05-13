<?php

namespace WorkOfStan\mycmsprojectnamespace;

use GodsDev\Tools\Tools;
use Tracy\Debugger;
use Tracy\Dumper;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\MyCommon;
use WorkOfStan\MyCMS\Tracy\BarPanelTemplate;
use WorkOfStan\mycmsprojectnamespace\Controller;
use WorkOfStan\mycmsprojectnamespace\Latte\CustomFilters;
use WorkOfStan\mycmsprojectnamespace\MyCMSProject;
use WorkOfStan\mycmsprojectnamespace\Utils;

/**
 * App class handles the request dispatching in index.php
 * (Last MyCMS/dist revision: 2022-03-06, v0.4.6+)
 */
class App extends MyCommon
{
    use \Nette\SmartObject;

    /** @var Backyard */
    protected $backyard;
    /** @var bool */
    protected $developmentEnvironment;
    /**
     * Feature flags that bubble down to latte and controller
     *
     * @var array<bool>
     */
    protected $featureFlags;
    /** @var mixed[] */
    protected $get;
    /** @var MyCMSProject extends the inherited MyCMS */
    protected $MyCMS;
    /** @var mixed[] */
    protected $myCmsConf;
    /** @var mixed[] */
    protected $post;
    /** @var mixed[] */
    protected $session;
    /** @var string[] */
    protected $server;
    /** @var array<array<array<string|array<array<string>>>>> (TODO: explore - one more array layer??) */
    protected $WEBSITE;

    /**
     * @param MyCMSProject $MyCMS
     * @param array<mixed> $options overrides default values of properties
     */
    public function __construct(MyCMSProject $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
    }

    /**
     *
     * @return void
     */
    public function run()
    {
        // TODO move part of this function to MyCMS core (either run or construct?) based on how much changes needed
        // Process POST, especially form submits
        if (is_array($this->post) && !empty($this->post)) {
            Debugger::getBar()->addPanel(new BarPanelTemplate('HTTP POST', $this->post));
            // set up translation for some multi-lingual messages
            $this->MyCMS->getSessionLanguage($this->get, $this->session, true); // todo check is it necessary here?
            //require_once './process.php'; //TODO: delete this line
            // CSRF token check
            if (!isset($this->post['token'], $this->session['token'])) {
                $this->MyCMS->logger->error('CSRF token not set');
                return;
            } elseif (!is_array($this->session['token'])) {
                $this->MyCMS->logger->error("CSRF token storage _SESSION['token'] corrupt");
                return;
            } elseif (!in_array($this->post['token'], $this->session['token'])) {
                $this->MyCMS->logger->error("CSRF token mismatch {$this->post['token']} not in _SESSION['token']");
                return;
            }
            // language switch (TODO explore where is this called from)
            if (isset($this->post['language'], $this->MyCMS->TRANSLATIONS[$this->post['language']])) {
                header('Content-type: application/json');
                exit(json_encode(['success' => true]));
            }
            // newsletter subscription
            if (
                array_key_exists('newsletter_input_box', $this->featureFlags) &&
                $this->featureFlags['newsletter_input_box'] &&
                isset($this->post['newsletter']) &&
                $this->post['newsletter']
            ) {
                if (
                    $this->MyCMS->dbms->query('INSERT INTO `' . TAB_PREFIX . 'subscriber` SET email="'
                        . $this->MyCMS->escapeSQL($this->post['newsletter'])
                        . '", info="' . $this->server['REMOTE_ADDR'] . '"')
                ) {
                    Tools::addMessage('success', $this->MyCMS->translate('Váš e-mail byl přidán k odběru.'));
                    $this->MyCMS->logger->info("Odběratel {$this->post['newsletter']} přidán k odběru.");
                } elseif ($this->MyCMS->dbms->errorDuplicateEntry()) {
                    // duplicate entry = subscriber's e-mail address already exists
                    Tools::addMessage('info', $this->MyCMS->translate('Zadaný e-mail již existuje.'));
                    $this->MyCMS->logger->warning(
                        "Odběratel {$this->post['newsletter']} nepřidán k odběru, protože již existuje."
                    );
                } else {
                    Tools::addMessage('error', $this->MyCMS->translate('Váš e-mail se nepodařilo přidat k odběru.'));
                    $this->MyCMS->logger->error(
                        "Odběratele {$this->post['newsletter']} se nepodařilo přidat k odběru."
                    );
                }
                $this->MyCMS->dbms->showSqlBarPanel();
                Tools::redir('', 303, false);
            }
        }
        $this->MyCMS->csrfStart();

        // Run Controller
        DEBUG_VERBOSE and Debugger::barDump($this->MyCMS, 'MyCMS before controller');
        $controller = new Controller($this->MyCMS, [
            'get' => Debugger::barDump(array_merge($this->get, $this->post), 'request'),
            'httpMethod' => $this->server['REQUEST_METHOD'], // TODO: pre-filter!
            // todo change in MyCMS, that get and requestUri are not processed in duplicate way
            // (changed here + in .htaccess)
            // but .htaccess isn't modified, is it?
            'requestUri' => preg_replace(
                '/(api)(\/)([a-zA-Z0-9=]*)(\?)?(.*)/',
                '?\1-\3&\5',
                $this->server['REQUEST_URI']
            ), // necessary for FriendlyURL feature: /api/item?id=14 || /api/item/?id=14 => ?api-item&id=14
            'session' => $_SESSION, // as $_SESSION['token'] updated in csrfStart()
            'language' => $this->session['language'],
            'verbose' => DEBUG_VERBOSE,
            'featureFlags' => $this->featureFlags,
        ]);

        $controller->run();
        $this->MyCMS->template = $controller->template();
        $this->MyCMS->setContext($controller->context());
        // language is already properly set through FriendlyURL mechanism
        $this->MyCMS->WEBSITE = $this->WEBSITE[$this->session['language']];
        Debugger::barDump(
            ['template' => $controller->template(), 'context' => $controller->context()],
            'ControllerResult',
            [Dumper::DEPTH => 5]
        );

        // Process API
        if (array_key_exists('json', $this->MyCMS->context)) {
            $this->MyCMS->renderJson(
                Utils::directJsonCall($this->server['HTTP_ACCEPT']),
                $this->backyard,
                isset($this->get['human']) // context human
            );
            exit; //renderLatte etc isn't required
        }

        // Render web pages

        // texy initialization (@todo refactor) .. used in CustomFilters
        //$Texy = null;
        //ProjectSpecific::prepareTexy();

        $customFilters = new CustomFilters($this->MyCMS);

        $this->MyCMS->renderLatte(
            DIR_TEMPLATE_CACHE,
            //[$customFilters, 'loader'], // TODO use this with $Latte->addFilterLoader($this->customFilters)
            [$customFilters, 'common'],
            array_merge(
                [
                    'WEBSITE' => $this->MyCMS->WEBSITE,
                    'SETTINGS' => $this->MyCMS->SETTINGS,
                    'ref' => $this->MyCMS->template,
                    'gauid' => GA_UID,
                    'token' => end($_SESSION['token']), // as $_SESSION['token'] updated in csrfStart()
                    'search' => Tools::setifnull($this->get['search'], ''),
                    'messages' => Tools::setifnull($this->session['messages'], []),
                    'language' => $this->session['language'],
                    'translations' => $this->MyCMS->TRANSLATIONS,
                    'languageSelector' => $this->myCmsConf['LANGUAGE_SELECTOR'],
                    'development' => $this->developmentEnvironment,
                    'pageResourceVersion' => PAGE_RESOURCE_VERSION,
                    'useCaptcha' => USE_CAPTCHA,
                    'featureFlags' => $this->featureFlags,
                ],
                $this->MyCMS->context
            )
        );
    }
}
