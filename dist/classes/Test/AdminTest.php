<?php

namespace GodsDev\MYCMSPROJECTNAMESPACE;

use GodsDev\MYCMSPROJECTNAMESPACE\MyCMSProject;
use GodsDev\MYCMSPROJECTNAMESPACE\Admin;
use GodsDev\MYCMSPROJECTNAMESPACE\TableAdmin;
use Tracy\Debugger;

require_once __DIR__ . '/../../conf/config.php';

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-01-19 at 16:59:16.
 */
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
     */
    protected function setUp()
    {
        global $backyardConf;
        Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../../log');
        $backyard = new \GodsDev\Backyard\Backyard($backyardConf);
        $mycmsOptions = array(
            'TRANSLATIONS' => array(
                'en' => 'English',
                'cn' => '中文'
            ),
            'logger' => $backyard->BackyardError,
            'dbms' => new \GodsDev\MyCMS\LogMysqli(DB_HOST . ":" . DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE, $backyard->BackyardError), //@todo - use test db instead. Or use other TAB_PREFIX !
        );
        $this->myCms = new MyCMSProject($mycmsOptions);
        $_SESSION = array(
            'language' => $this->myCms->getSessionLanguage(array(), array(), false),
            'token' => rand(1e8, 1e9),
        ); //because $_SESSION is not defined in the PHPUnit mode
        //maybe according to what you test, change $this->myCms->context before invoking $this->object = new Admin; within Test methods        
        $this->object = new Admin($this->myCms, array('agendas' => array(),
            'TableAdmin' => new TableAdmin($mycmsOptions['dbms'], '', array())
        ));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @covers GodsDev\MYCMSPROJECTNAMESPACE\Admin::outputAdmin
     */
    public function testOutputAdmin()
    {
        $response = $this->object->outputAdmin();
        $this->assertStringStartsWith('<!DOCTYPE html>', $response);
        $this->assertStringEndsWith('</html>', $response);

//        // TODO: check HTML validity
//        // use SimpleXMLElement;
//		// Fail if errors
//		$xml = new SimpleXMLElement($response);
//		$nonDocumentErrors = $xml->{'non-document-error'};
//		$errors = $xml->error;
//		if (count($nonDocumentErrors) > 0) {
//			// Indeterminate
//			$this->markTestIncomplete();
//		} elseif (count($errors) > 0) {
//			// Invalid
//			$this->fail("HTML output did not validate.");
//		}
//		// Valid
//		$this->assertTrue(TRUE);
    }

}
