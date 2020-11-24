<?php

namespace GodsDev\MyCMS\Test;

use GodsDev\Backyard\Backyard;
use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\ProjectCommon;
use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class ProjectCommonTest extends \PHPUnit_Framework_TestCase
{

    /** @var ProjectCommon */
    protected $object;

    /** @var MyCMS */
    protected $myCms;

    /** @var string */
    protected $language;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @global array $backyardConf
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
            'dbms' => null,
        ];
        $this->myCms = new MyCMS($mycmsOptions);
        $this->language = $this->myCms->getSessionLanguage([], [], false);

        $this->object = new ProjectCommon($this->myCms, ['language' => $this->language]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        // no action
    }

    /**
     * @covers GodsDev\MyCMS\ProjectCommon::dump
     * @todo   Implement testDump().
     */
    public function testDump()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers GodsDev\MyCMS\ProjectCommon::prepareTexy
     * @todo   Implement testPrepareTexy().
     */
    public function testPrepareTexy()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers GodsDev\MyCMS\ProjectCommon::localDate
     * @todo   Implement testLocalDate().
     */
    public function testLocalDate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers GodsDev\MyCMS\ProjectCommon::correctLineBreak
     */
    public function testCorrectLineBreak()
    {
        $this->assertEquals('alfa a beta', $this->object->correctLineBreak('alfa a beta'));
        $this->assertEquals('alfa a&nbsp;beta', $this->object->correctLineBreak('alfa a&nbsp;beta'));
        $this->assertEquals('alfa Industry 4.0 beta', $this->object->correctLineBreak('alfa Industry 4.0 beta'));
        $this->assertEquals(
            'alfa Industry&nbsp;4.0 beta',
            $this->object->correctLineBreak('alfa Industry&nbsp;4.0 beta')
        );
        $this->assertEquals('alfa 3 % beta', $this->object->correctLineBreak('alfa 3 % beta'));
    }
}
