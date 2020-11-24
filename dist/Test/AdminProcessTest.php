<?php

namespace GodsDev\mycmsprojectnamespace\Test;

use GodsDev\Backyard\Backyard;
use GodsDev\MyCMS\LogMysqli;
use GodsDev\mycmsprojectnamespace\AdminProcess;
use GodsDev\mycmsprojectnamespace\MyCMSProject;
use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class AdminProcessTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MyCMSProject
     */
    protected $myCms;

    /**
     * @var AdminProcess
     */
    protected $object;

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
            'TRANSLATIONS' => [
                'en' => 'English',
                'zh' => '中文',
            ],
            'logger' => $backyard->BackyardError,
            'dbms' => new LogMysqli(
                DB_HOST . ":" . DB_PORT,
                DB_USERNAME,
                DB_PASSWORD,
                DB_DATABASE,
                $backyard->BackyardError
            ), //@todo - use test db instead. Or use other TAB_PREFIX !
        ];
        $this->myCms = new MyCMSProject($mycmsOptions);
        $_SESSION = [
            'language' => $this->myCms->getSessionLanguage([], [], false),
            'token' => rand((int) 1e8, (int) 1e9),
        ]; //because $_SESSION is not defined in the PHPUnit mode
        $AGENDAS = [
            'category' => ['path' => 'path'],
            //        'page' => ['table' => 'content', 'where' => 'type="page"', 'prefill' => ['type' => 'page']],
            'press' => ['table' => 'content', 'where' => 'type="press"', 'prefill' => ['type' => 'press']],
            'slide' => ['table' => 'content', 'where' => 'type="slide"',
                'column' => 'content_' . DEFAULT_LANGUAGE, 'prefill' => ['type' => 'slide']],
            'claim' => ['table' => 'content', 'where' => 'type="claim"',
                'column' => 'description_' . DEFAULT_LANGUAGE, 'prefill' => ['type' => 'claim']],
            'testimonial' => ['table' => 'content', 'where' => 'type="testimonial"',
                'column' => 'description_' . DEFAULT_LANGUAGE, 'prefill' => ['type' => 'testimonial']],
            'system' => ['table' => 'content', 'where' => 'type="system"',
                'column' => 'code', 'prefill' => ['type' => 'system']],
        ];
        //maybe according to what you test, change $this->myCms->context before
        //invoking $this->object = new Admin; within Test methods
        $this->object = new AdminProcess($this->myCms, ['agendas' => $AGENDAS]);
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
     * @covers GodsDev\mycmsprojectnamespace\AdminProcess::adminProcess
     * @todo   Implement testAdminProcess().
     *
     * @return void
     */
    public function testAdminProcess()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            __FUNCTION__ . ' has not been implemented yet.'
        );
    }

    /**
     * @covers GodsDev\mycmsprojectnamespace\AdminProcess::getAgenda
     * @todo Depends on the web structure
     *
     * @return void
     */
    public function testGetAgenda()
    {
        //$adminAgendaCategoryArray = $this->object->getAgenda('category');
        //$this->assertEquals(['id' => '10', 'name' => 'MYCMSPROJECTSPECIFIC',
        //'path' => '0000000001'], $adminAgendaCategoryArray[0]);
        $this->markTestIncomplete(
            __FUNCTION__ . ' has not been implemented yet.'
        );
    }
}
