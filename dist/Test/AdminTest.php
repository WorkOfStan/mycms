<?php

namespace GodsDev\mycmsprojectnamespace\Test;

use GodsDev\Backyard\Backyard;
use GodsDev\MyCMS\LogMysqli;
use GodsDev\mycmsprojectnamespace\Admin;
use GodsDev\mycmsprojectnamespace\MyCMSProject;
use GodsDev\mycmsprojectnamespace\TableAdmin;
use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class AdminTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MyCMSProject
     */
    protected $myCms;

    /**
     * @var Admin
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
        //maybe according to what you test, change $this->myCms->context before
        //invoking $this->object = new Admin; within Test methods
        $this->object = new Admin($this->myCms, [
            'agendas' => [],
            'tableAdmin' => new TableAdmin(
                $mycmsOptions['dbms'],
                '',
                ['TRANSLATIONS' => [
                    'cs' => 'Česky',
                    'en' => 'English',
                ]]
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
     * @covers GodsDev\mycmsprojectnamespace\Admin::outputAdmin
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
