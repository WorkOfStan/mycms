<?php

namespace WorkOfStan\MyCMS;

use Exception;
use Webmozart\Assert\Assert;

use function WorkOfStan\MyCMS\ThrowableFunctions\preg_replaceString;

class ProjectCommon extends MyCommon
{
    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var string */
    protected $language;

    /** @var string */
    protected $requestUri = ''; //default is homepage

    //TODO: order methods alphabetically

    /**
     * Shortcut for echo'<pre>'; var_dump(); and exit;
     * @param mixed $var variable(s) or expression to display
     * @return never
     */
    public static function dump($var)
    {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        exit;
    }

    /**
     * Returns SQL fragment for column link ($fieldName) which construct either parametric URL or relative friendly URL
     *
     * @param string $idPrefix e.g. ?article=
     * @param string $language
     * @param string $fieldName OPTIONAL field name with URL of the resulting array - default is 'link'
     * @param string $sourceTable OPTIONAL name of the source table where default is null,
     *     i.e. there is no risk of ambiguous column url_XX
     * @param string $sourceField OPTIONAL name of the source field where default is 'id',
     *     i.e. use where 'code' is needed
     * @return string
     */
    public function getLinkSql($idPrefix, $language, $fieldName = 'link', $sourceTable = null, $sourceField = 'id')
    {
        $addLanguageDirectory = ($language != DEFAULT_LANGUAGE) // other than default language should have its directory
            && !preg_match("~/$language/~", $this->requestUri); // unless the page already has it
        $this->verboseBarDump(
            $addLanguageDirectory,
            'addLanguageDirectory getLinkSql - other then default and page does not have it'
        );
        return ' IF(' . (FRIENDLY_URL ? 1 : 0) . ','
            . ' if(' . (is_null($sourceTable) ? '' : $sourceTable . '.') . '`url_' . $language . "` <> '', "
            . ($addLanguageDirectory ? "CONCAT(\"{$language}/\", " : '')
            . (is_null($sourceTable) ? '' : $sourceTable . '.') . "`url_" . $language . '`'
            . ($addLanguageDirectory ? ')' : '')
            . ', CONCAT("' . $idPrefix . '", '
            . (is_null($sourceTable) ? '' : $sourceTable . '.') . $sourceField . ')) '
            . ',CONCAT("' . $idPrefix . '",' . (is_null($sourceTable) ? '' : $sourceTable . '.') . $sourceField . '))'
            . " AS {$fieldName} ";
    }

    /**
     * Without parameter just returns the language
     * With parameter set language (used by FriendlyUrl::switchParametric as language may change during Controller)
     *
     * @param string $language OPTIONAL
     * @return string
     */
    public function language($language = null)
    {
        if (!is_null($language)) {
            $this->language = $language;
        }
        return $this->language;
    }

    /**
     * @todo refactor as getTexy() which returns Texy object that is initialized in this dynamic class
     *
     * @global \Texy $Texy
     * @return void
     */
    public static function prepareTexy()
    {
        global $Texy;
        if (!is_object($Texy)) {
            $Texy = new \Texy();
            $Texy->headingModule->balancing = TEXY_HEADING_FIXED;
            $Texy->headingModule->generateID = true;
            $Texy->allowedTags = true;
            $Texy->dtd['a'][0]['data-lightbox'] = 1;
        }
    }

    /**
     *
     * @param string $stringOfTime
     * @param string $language
     * @return string
     * @throws Exception if $stringOfTime is malformed
     */
    public static function localDate($stringOfTime, $language)
    {
        $strToTime = strtotime($stringOfTime);
        if ($strToTime === false) {
            throw new Exception('$stringOfTime is malformed');
        }
        switch ($language) {
            case 'cs':
                return date('j.n.Y', $strToTime);
        }
        //en
        return date('D, j M Y', $strToTime);
    }

    /**
     *
     * @param array<mixed> $arr
     * @return array<string>
     */
    private function assertStringArray(array $arr)
    {
        foreach ($arr as $string) {
            Assert::string($string);
        }
        return $arr;
    }

    /**
     * Replace spaces with \0160 after selected short words
     * The list of selected words may be enlarged or redefined in the ProjectSpecific child
     *
     * @param string $text
     * @param array<string> $addReplacePatterns add or redefine patterns
     * @return string
     * @throws Exception in case of preg_replace error
     */
    public function correctLineBreak($text, array $addReplacePatterns = [])
    {
        $replacePatterns = array_merge([
            '/ a /' => ' a ',
            '/ i /' => ' i ',
            '/ k /' => ' k ',
            '/ o /' => ' o ',
            '/ s /' => ' s ',
            '/ u /' => ' u ',
            '/ v /' => ' v ',
            '/ ve /' => ' ve ',
            '/ z /' => ' z ',
            '/ %/' => ' %',
            '/ & /' => ' & ',
            '/ an /' => ' an ',
            '/Industry 4.0/' => 'Industry 4.0',
            ], $addReplacePatterns);
        // Parameter #1 $pattern of function preg_replaceString expects array<string>|string
        return preg_replaceString(
            $this->assertStringArray(array_keys($replacePatterns)),
            array_values($replacePatterns),
            $text
        );
    }
}
