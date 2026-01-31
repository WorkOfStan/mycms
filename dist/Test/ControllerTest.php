<?php

namespace WorkOfStan\mycmsprojectnamespace\Test;

use PHPUnit\Framework\TestCase;
use Tracy\Debugger;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\mycmsprojectnamespace\Controller;
use WorkOfStan\mycmsprojectnamespace\MyCMSProject;

require_once __DIR__ . '/../conf/config.php';

/**
 * Tests of Controller (of MVC)
 * (Last MyCMS/dist revision: 2022-03-06, v0.4.6)
 */
class ControllerTest extends TestCase
{
    /** @var mixed[] */
    protected $get;

    /** @var string */
    protected $language;

    /** @var MyCMSProject */
    protected $myCms;

    /** @var Controller */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @global array $backyardConf
     * @return void
     */
    protected function setUp(): void
    {
        global $backyardConf;
        error_reporting(E_ALL); // incl E_NOTICE
        Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../log');
        $backyard = new Backyard($backyardConf);
        if (!defined('DB_HOST')) {
            // if set-up of constants didn't happen in the first test according to alphabet
            new \WorkOfStan\MyCMS\InitDatabase('testing', __DIR__ . '/../');
        }
        $mycmsOptions = [
            // constants are defined by `new InitDatabase` in the alphabetically first test
            'dbms' => new LogMysqli(
                DB_HOST . ':' . DB_PORT,
                DB_USERNAME,
                DB_PASSWORD,
                DB_DATABASE,
                $backyard->BackyardError
            ),
            'logger' => $backyard->BackyardError,
            'prefixL10n' => __DIR__ . '/../conf/l10n/language-',
            'templateAssignementParametricRules' => [],
            'TRANSLATIONS' => [
                'en' => 'English',
                'zh' => '中文',
            ],
        ];
        $this->myCms = new MyCMSProject($mycmsOptions);

        // set language in $_SESSION and $this->get as one of the TRANSLATIONS array above
        // this settings is equivalent to going to another language homepage from the DEFAULT_LANGUAGE homepage
        $this->get = ['language' => 'en'];
        $_SESSION = ['language' => 'en']; // because $_SESSION is not defined in the PHPUnit mode
        $this->language = $_SESSION['language'] = $this->myCms->getSessionLanguage($this->get, $_SESSION, false);

        //according to what you test, change $this->myCms->context before invoking
        //$this->object = new Controller; within Test methods
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // no action
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerNoContext()
    {
        $this->object = new Controller($this->myCms, [
            'language' => $this->language,
            'httpMethod' => 'GET',
            'session' => $_SESSION,
            'get' => $this->get,
            ]);
        $controller = $this->object->run();
        //$this->assertArraySubset(['template' => 'home', 'context' => []], $controller);
        $this->assertSame('home', $controller['template']);
        $this->assertSame([], $controller['context']);
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerContext()
    {
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $this->object = new Controller($this->myCms, [
            'httpMethod' => 'GET',
            'session' => $_SESSION,
            'get' => $this->get,
            ]);
//        $this->assertArraySubset(
//            ['template' => 'home', 'context' => $this->myCms->context],
//            $this->object->run()
//        );
        $controller = $this->object->run();
        $this->assertSame('home', $controller['template']);
        $this->assertSame($this->myCms->context, $controller['context']);
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerAbout()
    {
        $this->object = new Controller($this->myCms, [
            'get' => array_merge($this->get, [
                'about' => '',
            ]),
            'httpMethod' => 'GET',
            'session' => $_SESSION,
        ]);
        $controller = $this->object->run();
        $this->assertArrayHasKey('template', $controller);
        $this->assertIsString($controller['template']);
        $this->assertEquals('home', $controller['template']);
        $this->assertArrayHasKey('context', $controller);
        $this->assertIsArray($controller['context']);
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::getVars
     *
     * @return void
     */
    public function testGetVars()
    {
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $options = [
            'get' => ['v1' => 'getSth'],
            'session' => ['v1' => 'getSth'],
        ];
        $this->object = new Controller($this->myCms, $options);
        $this->assertEquals($options, $this->object->getVars());
    }
}
