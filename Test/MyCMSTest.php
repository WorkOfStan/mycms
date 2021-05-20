<?php

namespace WorkOfStan\MyCMS\Test;

use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\MyCMS;

require_once __DIR__ . '/../conf/config.php';

class MyCMSTest extends \PHPUnit_Framework_TestCase
{

    /** @var MyCMS */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     *
     * @global array $backyardConf
     */
    protected function setUp()
    {
        global $backyardConf;
        error_reporting(E_ALL); // incl E_NOTICE
        $backyard = new Backyard($backyardConf);
        $mycmsOptions = [
            'TRANSLATIONS' => [
                'en' => 'English',
                'zh' => '中文',
            ],
            'logger' => $backyard->BackyardError,
        ];
        $this->object = new MyCMS($mycmsOptions);
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
     * @covers WorkOfStan\MyCMS\MyCMS::getSessionLanguage
     *
     * @return void
     */
    public function testGetSessionLanguageBasic()
    {
        $this->assertEquals(
            'en',
            $this->object->getSessionLanguage(['language' => 'en'], ['language' => 'en'], false),
            'Fail for both languages are same en'
        );
        $this->assertEquals(
            DEFAULT_LANGUAGE,
            $this->object->getSessionLanguage(['language' => 'xx'], ['language' => 'xx'], false),
            'Fail unknown language is return'
        );
        $this->assertEquals(
            'en',
            $this->object->getSessionLanguage(['language' => 'en'], ['language' => 'zh'], false),
            'get language should prevail'
        );
        $this->assertEquals(
            'zh',
            $this->object->getSessionLanguage(['language' => 'zh'], ['language' => 'en'], false),
            'get language should prevail'
        );
    }

    /**
     * @covers WorkOfStan\MyCMS\MyCMS::getSessionLanguage
     *
     * @return void
     */
    public function testGetSessionLanguageAdvanced()
    {
        $this->assertEquals(
            'zh',
            $this->object->getSessionLanguage([], ['language' => 'zh'], false),
            'Solo session prevails'
        );
        $this->assertEquals(
            'zh',
            $this->object->getSessionLanguage(['language' => 'zh'], [], false),
            'Solo get is used'
        );
        $this->assertEquals(
            DEFAULT_LANGUAGE,
            $this->object->getSessionLanguage([], ['language' => 'xx'], false),
            'Solo wrong session is ignored'
        );
        $this->assertEquals(
            DEFAULT_LANGUAGE,
            $this->object->getSessionLanguage(['language' => 'xx'], [], false),
            'Solo wrong get is ignored'
        );
        $this->assertEquals(
            'zh',
            $this->object->getSessionLanguage(['language' => 'xx'], ['language' => 'zh'], false),
            'get language should prevail only if correct'
        );
        $this->assertEquals(
            'en',
            $this->object->getSessionLanguage(['language' => 'xx'], ['language' => 'en'], false),
            'get language should prevail only if correct'
        );
    }

    /**
     * @covers WorkOfStan\MyCMS\MyCMS::fetchAndReindex
     * @todo   Implement testFetchAndReindex().
     *
     * @return void
     */
    public function testFetchAndReindex()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers WorkOfStan\MyCMS\MyCMS::translate
     * @todo   Implement testTranslate().
     *
     * @return void
     */
    public function testTranslate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
