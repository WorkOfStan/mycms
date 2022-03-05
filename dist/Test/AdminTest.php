<?php

namespace WorkOfStan\mycmsprojectnamespace\Test;

use Tracy\Debugger;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\LogMysqli;
use WorkOfStan\mycmsprojectnamespace\Admin;
use WorkOfStan\mycmsprojectnamespace\MyCMSProject;
use WorkOfStan\mycmsprojectnamespace\TableAdmin;

require_once __DIR__ . '/../conf/config.php';

/**
 * Tests of Admin UI
 * (Last MyCMS/dist revision: 2022-03-05, v0.4.6)
 */
class AdminTest extends \PHPUnit_Framework_TestCase
{
    /** @var MyCMSProject */
    protected $myCms;

    /** @var Admin */
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
            // constants are defined by `new InitDatabase` in the alphabetically first test
            'dbms' => new LogMysqli(
                DB_HOST . ":" . DB_PORT,
                DB_USERNAME,
                DB_PASSWORD,
                DB_DATABASE,
                $backyard->BackyardError
            ),
            'logger' => $backyard->BackyardError,
            'TRANSLATIONS' => [
                'en' => 'English',
                'zh' => '中文',
            ],
        ];
        $this->myCms = new MyCMSProject($mycmsOptions);
        $_SESSION = [
            'language' => $this->myCms->getSessionLanguage([], [], false),
            'token' => rand((int) 1e8, (int) 1e9),
        ]; //because $_SESSION is not defined in the PHPUnit mode
        //maybe according to what you test, change $this->myCms->context before
        //invoking $this->object = new Admin; within Test methods
        $this->object = new Admin($this->myCms, [
            'agendas' => [],
            'tableAdmin' => new TableAdmin(
                $mycmsOptions['dbms'],
                '',
                [
                    'language' => 'en',
                    'prefixL10n' => __DIR__ . '/../conf/l10n/admin-',
                    'TRANSLATIONS' => [
                        'cs' => 'Česky',
                        'en' => 'English',
                    ],
                ]
            )
        ]);
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
     * @covers WorkOfStan\mycmsprojectnamespace\Admin::outputAdmin
     *
     * @return void
     */
    public function testOutputAdmin()
    {
        $response = $this->object->outputAdmin();
        $this->assertStringStartsWith('<!DOCTYPE html>', $response);
        $this->assertStringEndsWith('</html>', $response);

//        // TODO: check HTML validity
//        // use SimpleXMLElement;
//        // Fail if errors
//        $xml = new SimpleXMLElement($response);
//        $nonDocumentErrors = $xml->{'non-document-error'};
//        $errors = $xml->error;
//        if (count($nonDocumentErrors) > 0) {
//            // Indeterminate
//            $this->markTestIncomplete();
//        } elseif (count($errors) > 0) {
//            // Invalid
//            $this->fail("HTML output did not validate.");
//        }
//        // Valid
//        $this->assertTrue(TRUE);
    }
}
