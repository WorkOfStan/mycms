<?php

namespace GodsDev\mycmsprojectnamespace\Test;

use GodsDev\Backyard\Backyard;
use GodsDev\MyCMS\LogMysqli;
use GodsDev\mycmsprojectnamespace\FriendlyUrl;
use GodsDev\mycmsprojectnamespace\MyCMSProject;
use GodsDev\Tools\Tools;
use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class FriendlyUrlTest extends \PHPUnit_Framework_TestCase
{

    /** @var MyCMSProject */
    protected $myCms;

    /** @var FriendlyUrl */
    protected $object;

    /** @var string */
    protected $language;

    /** @var Backyard */
    protected $backyard;

    /** @var string */
    protected $apiBaseUrl;

    /** @var string */
    protected $apiBaseDomain;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @global array $backyardConf
     * @global array $myCmsConf
     * @return void
     */
    protected function setUp()
    {
        global $backyardConf,
        $myCmsConf;
        error_reporting(E_ALL); // incl E_NOTICE
        Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../log');
        $this->backyard = new Backyard($backyardConf);
        $myCmsConf['logger'] = $this->backyard->BackyardError;
        $myCmsConf['dbms'] = new LogMysqli(
            DB_HOST . ':' . DB_PORT,
            DB_USERNAME,
            DB_PASSWORD,
            DB_DATABASE,
            $myCmsConf['logger']
        ); //@todo - use test db instead. Or use other TAB_PREFIX !

        $this->myCms = new MyCMSProject($myCmsConf);

        $_SESSION = []; //because $_SESSION is not defined in the PHPUnit mode
        $this->language = $this->myCms->getSessionLanguage([], [], false);
        //according to what is tested, change $this->myCms->context before
        //invoking $this->object = new FriendlyUrl; within Test methods
        $this->assertArrayHasKey(
            'web_domain',
            $backyardConf,
            '$backyardConf[\'web_domain\'] MUST be configured in config.local.php'
        );
        $this->assertArrayHasKey(
            'web_path',
            $backyardConf,
            '$backyardConf[\'web_path\'] MUST be configured in config.local.php'
        );
        $this->apiBaseDomain = $backyardConf['web_domain'];
        $this->apiBaseUrl = $backyardConf['web_domain'] . $backyardConf['web_path'];
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        // no action
    }

    /**
     * @covers GodsDev\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testDetermineTemplateParametric()
    {
        $determineTemplateOptions = ['REQUEST_URI' => '/?product&id=1'];
        $friendlyUrlOptions = [
            'get' => ['product' => '', 'id' => '1'],
            'language' => 'cs', // in production taken from $_SESSION['language']
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get']) ?
            (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            //(' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('httpCode', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                301,
                $templateDetermined['httpCode'],
                'non-301 httpCode field in ' . print_r($templateDetermined, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertStringEndsWith('/alfa', $templateDetermined['redir'], 'redir field MUST end with /alfa');
        } else {
            $this->assertEquals('product', $templateDetermined, $message);
        }

        $determineTemplateOptions = ['REQUEST_URI' => '/?product&id=2'];
        $friendlyUrlOptions = [
            'get' => ['product' => '', 'id' => '2'],
            'language' => 'cs', // in production taken from $_SESSION['language']
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session'])
            // ? (' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('httpCode', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                301,
                $templateDetermined['httpCode'],
                'non-301 httpCode field in ' . print_r($templateDetermined, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertStringEndsWith('/beta', $templateDetermined['redir'], 'redir field MUST end with /beta');
        } else {
            $this->assertEquals('product', $templateDetermined, $message);
        }

        $determineTemplateOptions = ['REQUEST_URI' => '/?product&id=5'];
        $friendlyUrlOptions = [
            'get' => ['product' => '', 'id' => '5'],
            'language' => 'cs', // in production taken from $_SESSION['language']
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session'])
            //? (' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        $this->assertEquals('product', $templateDetermined, $message);

        // non-existent product
        $determineTemplateOptions = ['REQUEST_URI' => '/?product&id=15000'];
        $friendlyUrlOptions = [
            'get' => ['product' => '', 'id' => '15000'],
            'language' => 'cs',
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            //(' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('httpCode', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                301,
                $templateDetermined['httpCode'],
                'non-301 httpCode field in ' . print_r($templateDetermined, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertStringEndsWith('/404', $templateDetermined['redir'], 'redir field MUST end with /404');
        } else {
            $this->assertEquals('product', $templateDetermined, $message);
            // Note: to error404 it will change in Controller::prepareTemplate
        }

        $determineTemplateOptions = ['REQUEST_URI' => '/?category=1'];
        $friendlyUrlOptions = [
            'get' => ['category' => '1'],
            'language' => 'cs',
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            //(' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('httpCode', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                301,
                $templateDetermined['httpCode'],
                'non-301 httpCode field in ' . print_r($templateDetermined, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertStringEndsWith(
                '/default-category-cs',
                $templateDetermined['redir'],
                'redir field MUST end with /default-category-cs'
            );
        } else {
            $this->assertEquals('category', $templateDetermined, $message);
        }

        $determineTemplateOptions = ['REQUEST_URI' => '/?category=2'];
        $friendlyUrlOptions = [
            'get' => ['category' => '2'],
            'language' => 'cs',
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            //(' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        $this->assertEquals('category', $templateDetermined, $message);

        $determineTemplateOptions = ['REQUEST_URI' => '/?category'];
        $friendlyUrlOptions = [
            'get' => ['category' => ''],
            'language' => 'cs',
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            // (' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        $this->assertEquals('category', $templateDetermined, $message);

        // non-existent category
        $determineTemplateOptions = ['REQUEST_URI' => '/?category=10000'];
        $friendlyUrlOptions = [
            'get' => ['category' => '10000'],
            'language' => 'cs',
        ];
        $this->object = new FriendlyUrl($this->myCms, $friendlyUrlOptions);
        $templateDetermined = $this->object->determineTemplate($determineTemplateOptions);
        $message = 'Failed for request URI ' . $determineTemplateOptions['REQUEST_URI']
            . (Tools::nonempty($friendlyUrlOptions['get'])
            ? (' get:' . http_build_query($friendlyUrlOptions['get'])) : '')
            //. (Tools::nonempty($friendlyUrlOptions['session']) ?
            //(' session:' . http_build_query($friendlyUrlOptions['session'])) : '')
            . ' templateDetermined: ' . print_r($templateDetermined, true);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('httpCode', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                301,
                $templateDetermined['httpCode'],
                'non-301 httpCode field in ' . print_r($templateDetermined, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertStringEndsWith('/404', $templateDetermined['redir'], 'redir field MUST end with /404');
        } else {
            $this->assertEquals('category', $templateDetermined, $message);
            // Note: result of determineTemplate is 'category';
            // in Controller::prepareTemplate it will change to 'error404'
        }
    }

    /**
     * @covers GodsDev\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerRedirectorVsFriendlyURL()
    {
        $requestUri = '/takova-stranka-neni';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, [
            'get' => [],
//            'requestUri' => $requestUri,
//            "session" => $_SESSION,
//            'sectionStyles' => ['red'],
        ]);
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $this->assertEquals('error404', $templateDetermined, 'MUST return false: Determine template ' . $message);

        $requestUri = '/kontakty';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
//            "requestUri" => $requestUri,
//            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $this->assertEquals('article', $templateDetermined, 'Trying to determine template ' . $message);

        $requestUri = '/takovy-adresar-neni/odpovednost?product&id=2';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [
                'product' => '',
                'id' => '2',
            ],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $applicationDir = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME);
        $this->assertInternalType(
            'array',
            $templateDetermined,
            'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );
        $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
        $this->assertEquals(
            [
            'redir' => $applicationDir . ((FRIENDLY_URL && FORCE_301) ? '/beta' : '/?product&id=2'),
            'httpCode' => 301
            ],
            $templateDetermined,
            'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );

        $requestUri = '/takovy-adresar-neni/odpovednost';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $this->assertInternalType(
            'array',
            $templateDetermined,
            'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );
        $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
        $this->assertEquals(
            ['redir' => $applicationDir . '/404?url=' . $requestUri, 'httpCode' => 301],
            $templateDetermined,
            'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );

        $requestUri = '/data-centers/dadas/dadsads';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $this->assertInternalType(
            'array',
            $templateDetermined,
            'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );
        $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
        $this->assertEquals(
            ['redir' => $applicationDir . '/404?url=' . $requestUri, 'httpCode' => 301],
            $templateDetermined,
            'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );

        $requestUri = '/xx/data-centers';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        $this->assertInternalType(
            'array',
            $templateDetermined,
            'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );
        $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
        $this->assertEquals(
            ['redir' => $applicationDir . '/404?url=' . $requestUri, 'httpCode' => 301],
            $templateDetermined,
            'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
        );

        $requestUri = '/en/contacts'; // Note: url_en=contacts MUST be set
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri,
//            'verbose' => true,
        ]);
        $this->assertEquals('article', $templateDetermined, 'Determine template ' . $message);

        // Test below is for redirector
        $requestUri = '/adresa';
        //Note: to properly test e.g. /spolecnost/odpovednost (which MUST be defined in redirector table)
        //the /odpovednost MUST be tested first.
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
//            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (REDIRECTOR_ENABLED) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/kontakty', 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertEquals(
                'error404',
                $templateDetermined,
                'Determine template ' . $message . ' but result is ' . print_r($templateDetermined, true)
            );
        }

        // Test below is for redirector
        $requestUri = '/firma/adresa';
        //Note: to properly test e.g. /firma/adresa (which MUST be defined in redirector table)
        //the /adresa redirect MUST be tested first.
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (REDIRECTOR_ENABLED) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/kontakty', 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/404?url=' . $requestUri, 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        }

        // Test below is for redirector
        $requestUri = '/adresa/do/nasi/kancelare';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => [],
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (REDIRECTOR_ENABLED) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/kontakty', 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, 'Determine template ' . $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/404?url=' . $requestUri, 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        }

        $requestUri = '/en/beta?product&id=1';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => array(
                "product" => "",
                "id" => "1",
            ),
            "language" => "en",
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (
            FORCE_301
//            && FRIENDLY_URL
        ) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template "' . print_r($templateDetermined, true)
                . '" ' . $message . ' but template=' . print_r($this->myCms->template, true)
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $this->assertEquals(
                [
                'redir' => $applicationDir . (FRIENDLY_URL ? '/en/alfa' : '/?product&id=1&language=en'),
                'httpCode' => 301
                ],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertEquals(
                'product',
                $templateDetermined,
                $message . ' template=' . print_r($templateDetermined, true)
            );
        }


        $requestUri = '/en/beta?product&id=4';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => array(
                "product" => "",
                "id" => "4",
            ),
            "language" => "en",
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (
            FORCE_301
//            && FRIENDLY_URL
        ) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $this->assertEquals(
                ['redir' => $applicationDir . '/?product&id=4&language=en', 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertEquals(
                'product',
                $templateDetermined,
                $message . ' template=' . print_r($templateDetermined, true)
            );
        }

        // follow-up from previous test
        $requestUri = '/?product&id=4&language=en';
        $message = "For request URI " . $requestUri;
        $this->object = new FriendlyUrl($this->myCms, array(
            "get" => array(
                "product" => "",
                "id" => "4",
                'language' => 'en',
            ),
            "language" => "en",
            "requestUri" => $requestUri,
            "session" => $_SESSION,
//            "sectionStyles" => array("red"),
        ));
        $templateDetermined = $this->object->determineTemplate([
            'PATH_HOME' => '0000000001',
            'REQUEST_URI' => $requestUri
        ]);
        if (FORCE_301 && FRIENDLY_URL) {
            $this->assertInternalType(
                'array',
                $templateDetermined,
                'MUST return array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
            $this->assertArrayHasKey('redir', $templateDetermined, $message);
            $resultRedir = $applicationDir . '/en/?product&id=4';
            $this->assertEquals(
                ['redir' => $resultRedir, 'httpCode' => 301],
                $templateDetermined,
                'MUST return redirect array: Determine template ' . $message . ' but template=' . $this->myCms->template
            );
        } else {
            $this->assertEquals('product', $templateDetermined, $message);
        }
    }

    /**
     * @covers GodsDev\mycmsprojectnamespace\FriendlyUrl::friendlyfyUrl
     *
     * @return void
     */
    public function testFriendlyfyUrl()
    {
        if (!FRIENDLY_URL) {
            $this->markTestIncomplete(
                'This test may be performed only with FRIENDLY_URL === true.'
            );
            return;
        }
        $this->object = new FriendlyUrl($this->myCms, []);

        $this->assertEquals('alfa', $this->object->friendlyfyUrl('?product&id=1'));
        $this->assertEquals('404', $this->object->friendlyfyUrl('?product=3170'), 'Nonexistent product');
        $this->assertEquals('default-category-cs', $this->object->friendlyfyUrl('?category=1'));
        $this->assertEquals('404', $this->object->friendlyfyUrl('?category=1456'), 'Nonexistent category');
    }

    /**
     * This PHPUnit test is meant just for MyCMS development. It is recommended to remove it from real applications.
     *
     * @return void
     */
    public function testPageStatusOverHttp()
    {
        $urlsToBeCheckedAny = [
            [
                'relative_url' => '', // home
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Lorem ipsum dolor sit amet',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa', // alfa for CS
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => 'en/alfa', // en/alfa
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Product 1',
                'is_json' => false
            ],
            [
                'relative_url' => 'non-sense', // non-existent page
                'http_status' => 404,
                'allow_redirect' => true,
//                'contains_text' => '',
                'is_json' => false
            ],
            [
                'relative_url' => '?article&code=contacts',
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Adresa:',
                'is_json' => false
            ],
        ];
        $urlsToBeCheckedFriendlyUrlFalseForce301False = [
            [
                'relative_url' => '?product&id=1',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1&x=y',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 2',
                'is_json' => false
            ],
        ];
        $urlsToBeCheckedFriendlyUrlFalseForce301True = [
            [
                'relative_url' => '?product&id=1',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1&x=y',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2',
                'http_status' => 301,
                'allow_redirect' => false,
                'redirect_contains' => '/?product&id=2',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2',
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Produkt 2',
                'is_json' => false
            ],
        ];
        $urlsToBeCheckedFriendlyUrlTrueForce301False = [
            [
                'relative_url' => '?product&id=1',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1&x=y',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 1',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2',
                'http_status' => 200,
                'allow_redirect' => false,
                'contains_text' => 'Produkt 2',
                'is_json' => false
            ],
        ];
        $urlsToBeCheckedFriendlyUrlTrueForce301True = [
            [
                'relative_url' => '?product&id=1', // redirect to alfa
                'http_status' => 200,
                'allow_redirect' => true,
//                'contains_text' => '',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1', // redirect to alfa
                'http_status' => 301,
                'allow_redirect' => false,
//                'contains_text' => '',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1&x=y', // redirect to alfa
                'http_status' => 200,
                'allow_redirect' => true,
//                'contains_text' => '',
                'is_json' => false
            ],
            [
                'relative_url' => '?product&id=1&x=y', // redirect to alfa
                'http_status' => 301,
                'allow_redirect' => false,
//                'contains_text' => '',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2', // redirects to beta
                'http_status' => 301,
                'allow_redirect' => false,
                'redirect_contains' => '/beta',
                'is_json' => false
            ],
            [
                'relative_url' => 'alfa?product&id=2', // redirects to beta
                'http_status' => 200,
                'allow_redirect' => true,
                'contains_text' => 'Produkt 2',
                'is_json' => false
            ],
        ];

        $urlsToBeChecked = array_merge(
            $urlsToBeCheckedAny,
            FRIENDLY_URL ?
            (FORCE_301 ? $urlsToBeCheckedFriendlyUrlTrueForce301True : $urlsToBeCheckedFriendlyUrlTrueForce301False)
            : (FORCE_301 ? $urlsToBeCheckedFriendlyUrlFalseForce301True : $urlsToBeCheckedFriendlyUrlFalseForce301False)
        );

        foreach ($urlsToBeChecked as $singleUrl) {
            $url = $this->apiBaseUrl . $singleUrl['relative_url'];
            $result = $this->backyard->Http->getData(
                $url,
                'PHPUnit/' . \PHPUnit_Runner_Version::id() . ' ' . __FUNCTION__
            );
            // var_dump("first RESULT for {$url}", $result);
            if (isset($singleUrl['redirect_contains']) && isset($result['REDIRECT_URL'])) {
                $this->assertContains(
                    $singleUrl['redirect_contains'],
                    $result['REDIRECT_URL'],
                    "Redirect '{$singleUrl['redirect_contains']}' needle is not in the haystack {$url}"
                );
            }
            if (
                isset($singleUrl['allow_redirect']) && $singleUrl['allow_redirect'] && isset($result['REDIRECT_URL'])
            ) {//fixes e.g. http to https 301 redirect
                $result = $this->backyard->Http->getData(
                    $this->apiBaseDomain . $result['REDIRECT_URL'],
                    'PHPUnit/' . \PHPUnit_Runner_Version::id() . ' ' . __FUNCTION__
                );
                // var_dump('2nd RESULT', $result);
            }
            $this->assertNotFalse(is_array($result), "cURL failed on {$url} with result=" . print_r($result, true));
            $this->assertFalse(
                ($result['HTTP_CODE'] === 0),
                "URL {$url} is not available. (Is \$backyardConf['web_address'] properly configured?)"
            );
            if (isset($singleUrl['http_status']) && $singleUrl['http_status']) {
                $this->assertEquals(
                    $singleUrl['http_status'],
                    $result['HTTP_CODE'],
                    "URL {$url} returns other HTTP code. (HOME_TOKEN is set?) Test parameters: "
                    . print_r($singleUrl, true)
                );
            }
            $this->assertArrayHasKey('message_body', $result, "URL {$url} nevraci obsah.");
            if (isset($singleUrl['contains_text'])) {
                $this->assertContains(
                    $singleUrl['contains_text'],
                    $result['message_body'],
                    "Needle '{$singleUrl['contains_text']}' is not in the haystack {$url}"
                );
            }
            if (isset($singleUrl['is_json']) && $singleUrl['is_json']) {
                $jsonArr = json_decode($result['message_body'], true);
                $this->assertTrue(
                    is_array($jsonArr),
                    "Vysledek neni pole, tedy vstup na URL {$url} nebyl JSON: " . substr($result['message_body'], 0, 20)
                );
            }
        }
    }
}
