<?php

namespace WorkOfStan\MyCMS\Test;

use PHPUnit\Framework\TestCase;
use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\MyController;

require_once __DIR__ . '/../conf/config.php';

class MyControllerTest extends TestCase
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
    protected function setUp(): void
    {
        global $backyardConf;
        error_reporting(E_ALL); // incl E_NOTICE
        if (!defined('DIR_TEMPLATE')) {
            define('DIR_TEMPLATE', __DIR__ . '/../dist/template'); // for Latte
        }
        $backyard = new Backyard($backyardConf);
        $mycmsOptions = [
            'TRANSLATIONS' => [
                'tl' => 'Test language', // so that `tl` is allowed
                'en' => 'English',
                'zh' => '中文',
            ],
            'logger' => $backyard->BackyardError,
            'prefixL10n' => __DIR__ . '/conf/L10nTest-',
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
    protected function tearDown(): void
    {
        // no action
    }

    /**
     * @covers WorkOfStan\MyCMS\MyController::controller
     *
     * @return void
     */
    public function testControllerNoContext(): void
    {
        $controllerOptions = [
            'session' => ['language' => 'tl'],
        ];
        $this->object = new MyController($this->myCms, $controllerOptions);
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
    public function testControllerContext(): void
    {
        $controllerOptions = [
            'session' => ['language' => 'tl'],
        ];
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $this->object = new MyController($this->myCms, $controllerOptions);
        $this->assertEquals(['template' => 'home', 'context' => $this->myCms->context], $this->object->run());
    }

    /**
     * @covers WorkOfStan\MyCMS\MyController::getVars
     *
     * @return void
     */
    public function testGetVars(): void
    {
        $this->myCms->context = ['1' => '2', '3' => '4', 'c'];
        $controllerOptions = [
            'get' => ['v1' => 'getSth'],
            'session' => ['v1' => 'getSth'],
        ];
        $this->object = new MyController($this->myCms, $controllerOptions);
        $this->assertEquals($controllerOptions, $this->object->getVars());
    }
}
