<?php

namespace WorkOfStan\MyCMS\Test;

//use WorkOfStan\Backyard\Backyard;
use WorkOfStan\MyCMS\L10n;
//use Tracy\Debugger;

require_once __DIR__ . '/../conf/config.php';

class L10nTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $encoding;

    /** @var L10n */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new L10n(__DIR__ . '/conf/L10nTest-');
        $this->object->loadLocalisation('TL'); // TL as test language
        $this->encoding = 'UTF-8';
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
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyVerbatim()
    {
        $localisationTestArray = [
            // verbatim tests
            'First letter is upper case' => 'První písmeno je velké',
            'starting with lower case. OK.' => 'začátek s malým písmenem. OK.',
            'all lower case' => 'vše malými písmeny',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyLowerCaseBeginning()
    {
        $localisationTestArray = [
            // lower case beginning
            'first letter is upper case' => 'první písmeno je velké',
            'starting with lower case. OK.' => 'začátek s malým písmenem. OK.',
            'all lower case' => 'vše malými písmeny',
            'uPPER CASE' => 'vŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyUpperCaseBeginning()
    {
        $localisationTestArray = [
            // Upper case beginning
            'First letter is upper case' => 'První písmeno je velké',
            'Starting with lower case. OK.' => 'Začátek s malým písmenem. OK.',
            'All lower case' => 'Vše malými písmeny',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyAllLowerCase()
    {
        $localisationTestArray = [
            // all lower case
            'first letter is upper case' => 'první písmeno je velké',
            // TOO COMPLEX //'starting with lower case. ok.' => 'začátek s malým písmenem. ok.',
            'all lower case' => 'vše malými písmeny',
            'upper case' => 'vše velkými písmeny',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyAllUpperCase()
    {
        $localisationTestArray = [
            // ALL UPPER CASE
            // TOO COMPLEX //'FIRST LETTER IS UPPER CASE' => 'PRVNÍ PÍSMENO JE VELKÉ',
            // TOO COMPLEX //'STARTING WITH LOWER CASE. OK.' => 'ZAČÁTEK S MALÝM PÍSMENEM. OK.',
            'ALL LOWER CASE' => 'VŠE MALÝMI PÍSMENY',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateNotKey()
    {
        $localisationTestArray = [
            'Not in the yml (test)' => 'Not in the yml (test)',
            'low start not in the yml (test)' => 'low start not in the yml (test)',
//            'starting with lower case. OK.' => 'začátek s malým písmenem. OK.',
//            'all lower case' => 'vše malými písmeny',
//            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
            $this->assertEquals(mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1), $this->object->translate($key, L_UCFIRST), "L_UCFIRST Key: {$key}");
            $this->assertEquals(mb_strtoupper($value), $this->object->translate($key, MB_CASE_UPPER), "MB_CASE_UPPER Key: {$key}");
            $this->assertEquals(mb_strtolower($value), $this->object->translate($key, MB_CASE_LOWER), "MB_CASE_LOWER Key: {$key}");
            $this->assertEquals(mb_convert_case($value, MB_CASE_TITLE), $this->object->translate($key, MB_CASE_TITLE), "MB_CASE_TITLE Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyVerbatimEncoding()
    {
        $localisationTestArray = [
            // verbatim tests
            'First letter is upper case' => 'První písmeno je velké',
            'starting with lower case. OK.' => 'začátek s malým písmenem. OK.',
            'all lower case' => 'vše malými písmeny',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key, null, $this->encoding), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyLowerCaseBeginningEncoding()
    {
        $localisationTestArray = [
            // lower case beginning
            'first letter is upper case' => 'první písmeno je velké',
            'starting with lower case. OK.' => 'začátek s malým písmenem. OK.',
            'all lower case' => 'vše malými písmeny',
            'uPPER CASE' => 'vŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key, null, $this->encoding), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyUpperCaseBeginningEncoding()
    {
        $localisationTestArray = [
            // Upper case beginning
            'First letter is upper case' => 'První písmeno je velké',
            'Starting with lower case. OK.' => 'Začátek s malým písmenem. OK.',
            'All lower case' => 'Vše malými písmeny',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key, null, $this->encoding), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyAllLowerCaseEncoding()
    {
        $localisationTestArray = [
            // all lower case
            'first letter is upper case' => 'první písmeno je velké',
            // TOO COMPLEX //'starting with lower case. ok.' => 'začátek s malým písmenem. ok.',
            'all lower case' => 'vše malými písmeny',
            'upper case' => 'vše velkými písmeny',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key, null, $this->encoding), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateJustKeyAllUpperCaseEncoding()
    {
        $localisationTestArray = [
            // ALL UPPER CASE
            // TOO COMPLEX //'FIRST LETTER IS UPPER CASE' => 'PRVNÍ PÍSMENO JE VELKÉ',
            // TOO COMPLEX //'STARTING WITH LOWER CASE. OK.' => 'ZAČÁTEK S MALÝM PÍSMENEM. OK.',
            'ALL LOWER CASE' => 'VŠE MALÝMI PÍSMENY',
            'UPPER CASE' => 'VŠE VELKÝMI PÍSMENY',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key, null, $this->encoding), "Key: {$key}");
        }
    }

    /**
     * @covers WorkOfStan\mycmsprojectnamespace\L10n::translate
     *
     * @return void
     */
    public function testTranslateNotKeyEncoding()
    {
        $localisationTestArray = [
            'Not in the yml (test)' => 'Not in the yml (test)',
            'low start not in the yml (test)' => 'low start not in the yml (test)',
        ];
        foreach ($localisationTestArray as $key => $value) {
            $this->assertEquals($value, $this->object->translate($key), "Key: {$key}");
            $this->assertEquals(mb_strtoupper(mb_substr($value, 0, 1, $this->encoding), $this->encoding) . mb_substr($value, 1, null, $this->encoding), $this->object->translate($key, L_UCFIRST, $this->encoding), "L_UCFIRST Key: {$key}");
            $this->assertEquals(mb_strtoupper($value, $this->encoding), $this->object->translate($key, MB_CASE_UPPER, $this->encoding), "MB_CASE_UPPER Key: {$key}");
            $this->assertEquals(mb_strtolower($value, $this->encoding), $this->object->translate($key, MB_CASE_LOWER, $this->encoding), "MB_CASE_LOWER Key: {$key}");
            $this->assertEquals(mb_convert_case($value, MB_CASE_TITLE, $this->encoding), $this->object->translate($key, MB_CASE_TITLE, $this->encoding), "MB_CASE_TITLE Key: {$key}");
        }
    }
}
