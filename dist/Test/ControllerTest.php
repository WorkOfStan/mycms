<?php

namespace WorkOfStan\mycmsprojectnamespace\Test;

use Tracy\Debugger;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\mycmsprojectnamespace\Controller;
use WorkOfStan\mycmsprojectnamespace\MyCMSProject;

require_once __DIR__ . '/../conf/config.php';

/**
 * Tests of Controller (of MVC)
 * (Last MyCMS/dist revision: 2022-03-05, v0.4.6)
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MyCMSProject */
    protected $myCms;

    /** @var Controller */
    protected $object;

    /** @var string */
    protected $language;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @global array $backyardConf
     * @return void
     */
    protected function setUp()
    {
        global $backyardConf;
        error_reporting(E_ALL); // incl E_NOTICE
        Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../log');
        $backyard = new Backyard($backyardConf);
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
                'cs' => 'Česky', // todo remove this line with DEFAULT_LANGUAGE and
                // fix the MyFriendlyUrl.php:207 Arg #2 not an array
                // and WorkOfStan\mycmsprojectnamespace\Test\ControllerTest::testControllerContext
                // Webmozart\Assert\InvalidArgumentException: Expected one of: "en", "zh". Got: "cs"
                'en' => 'English',
                'zh' => '中文',
            ],
        ];
        $this->myCms = new MyCMSProject($mycmsOptions);
        $get = []; // ['language' => 'en']; TODO use this definition when cs above removed

        // set language as one of the TRANSLATIONS array above 'language' => 'en'
        $_SESSION = []; // because $_SESSION is not defined in the PHPUnit mode
        $this->language = $_SESSION['language'] = $this->myCms->getSessionLanguage($get, $_SESSION, false);

        //according to what you test, change $this->myCms->context before invoking
        //$this->object = new Controller; within Test methods
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
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerNoContext()
    {
        $this->object = new Controller($this->myCms, ['language' => $this->language, 'httpMethod' => 'GET']);
        $controller = $this->object->run();
        $this->assertArraySubset(['template' => 'home', 'context' => []], $controller);
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerContext()
    {
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $this->object = new Controller($this->myCms, ['httpMethod' => 'GET']);
        $this->assertArraySubset(
            ['template' => 'home', 'context' => $this->myCms->context],
            $this->object->run()
        );
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\Controller::controller
     *
     * @return void
     */
    public function testControllerAbout()
    {
        $this->object = new Controller($this->myCms, [
            'get' => [
                'about' => '',
            ],
            'httpMethod' => 'GET',
        ]);
        $controller = $this->object->run();
        $this->assertArrayHasKey('template', $controller);
        $this->assertInternalType('string', $controller['template']);
        $this->assertEquals('home', $controller['template']);
        $this->assertArrayHasKey('context', $controller);
        $this->assertInternalType('array', $controller['context']);
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
