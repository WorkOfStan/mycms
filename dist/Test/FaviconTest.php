<?php

namespace GodsDev\mycmsprojectnamespace\Test;

use GodsDev\Backyard\Backyard;
use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class FaviconTest extends \PHPUnit_Framework_TestCase
{

    /** @var Backyard */
    protected $backyard;

    /** @var string */
    protected $apiBaseUrl;

    /** @var string */
    protected $apiBaseDomain;

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
        $this->backyard = new Backyard($backyardConf);
        $this->assertArrayHasKey(
            'web_domain',
            $backyardConf,
            '$backyardConf[\'web_domain\'] MUST be configured in config.local.php'
        );
        $this->assertArrayHasKey(
            'web_path',
            $backyardConf,
            '$backyardConf[\'web_path\'] MUST be configured in config.local.php'
        );
        $this->apiBaseDomain = $backyardConf['web_domain'];
        $this->apiBaseUrl = $backyardConf['web_domain'] . $backyardConf['web_path'];
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
     * Check presence of favicon resources
     *
     * @return void
     */
    public function testPageStatusOverHttp()
    {
        $urlsToBeChecked = [
            'android-icon-144x144.png',
            'android-icon-192x192.png',
            'android-icon-36x36.png',
            'android-icon-48x48.png',
            'android-icon-72x72.png',
            'android-icon-96x96.png',
            'apple-icon-114x114.png',
            'apple-icon-120x120.png',
            'apple-icon-144x144.png',
            'apple-icon-152x152.png',
            'apple-icon-180x180.png',
            'apple-icon-57x57.png',
            'apple-icon-60x60.png',
            'apple-icon-72x72.png',
            'apple-icon-76x76.png',
            'apple-icon-precomposed.png',
            'apple-icon.png',
            'browserconfig.xml',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'favicon-96x96.png',
            'favicon.ico',
            'manifest.json',
            'ms-icon-144x144.png',
            'ms-icon-150x150.png',
            'ms-icon-310x310.png',
            'ms-icon-70x70.png',
        ];

        foreach ($urlsToBeChecked as $singleUrl) {
            $url = $this->apiBaseUrl . $singleUrl;
            $result = $this->backyard->Http->getData(
                $url,
                'PHPUnit/' . \PHPUnit_Runner_Version::id() . ' ' . __FUNCTION__
            );
            $this->assertNotFalse(is_array($result), "cURL failed on {$url} with result=" . print_r($result, true));
            $this->assertFalse(
                ($result['HTTP_CODE'] === 0),
                "URL {$url} is not available. (Is \$backyardConf['web_address'] properly configured?)"
            );
            $this->assertEquals(
                200,
                $result['HTTP_CODE'],
                "URL {$url} returns other HTTP code. (HOME_TOKEN is set?) Test parameters: "
                . print_r($singleUrl, true)
            );
        }
    }
}
