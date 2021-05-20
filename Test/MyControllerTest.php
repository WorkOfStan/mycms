<?php

namespace WorkOfStan\MyCMS\Test;

use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\MyController;

require_once __DIR__ . '/../conf/config.php';

class MyControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MyCMS
     */
    protected $myCms;

    /**
     * @var MyController
     */
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
        if (!defined('DIR_TEMPLATE')) {
            define('DIR_TEMPLATE', __DIR__ . '/../dist/template'); // for Latte
        }
        $backyard = new Backyard($backyardConf);
        $mycmsOptions = [
            'TRANSLATIONS' => [
                'en' => 'English',
                'zh' => '中文',
            ],
            'logger' => $backyard->BackyardError,
        ];
        $this->myCms = new MyCMS($mycmsOptions);
        //$this->object = new MyController;
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
     * @covers WorkOfStan\MyCMS\MyController::controller
     *
     * @return void
     */
    public function testControllerNoContext()
    {
        $this->object = new MyController($this->myCms);
        $this->assertEquals(['template' => 'home', 'context' => [
                'pageTitle' => '',
//                'applicationDir' => dirname($_SERVER['PHP_SELF']) . '/',
            ]], $this->object->run());
    }

    /**
     * @covers WorkOfStan\MyCMS\MyController::controller
     *
     * @return void
     */
    public function testControllerContext()
    {
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $this->object = new MyController($this->myCms);
        $this->assertEquals(['template' => 'home', 'context' => $this->myCms->context], $this->object->run());
    }

    /**
     * @covers WorkOfStan\MyCMS\MyController::getVars
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
        $this->object = new MyController($this->myCms, $options);
        $this->assertEquals($options, $this->object->getVars());
    }
}
