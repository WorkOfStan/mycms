<?php

namespace WorkOfStan\MyCMS;

use GodsDev\Tools\Tools;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * Localisation
 *  ... Translation
 * (Last MyCMS/dist revision: 2022-02-23, v0.4.6)
 * TODO: move to MyCMS core
 *
 * @author rejthar@stanislavrejthar.com
 */
class L10n
{
    use \Nette\SmartObject;

    /** @var string */
    protected $prefix;

    /** @var string */
    protected $selectedLanguage = null;

    /** @var array<string> */
    protected $translation;

    // as in MyTableLister
    /** @ var array<string> Selected locale strings */
//    public $TRANSLATION = [];

    // as in MyTableLister
    /** @ var array<string> Available languages for MyCMS */
//    public $TRANSLATIONS = [
//        'en' => 'English'
//    ];

    /**
     *
     * @param string $prefix folder & prefix of the yml localisation file
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix; // "$prefixXX.yml" where XX is the language e.g. 'conf/l10n/admin-'
        // select language
        // select folder .. i.e. prefix for yml (inc is deprecated in the root)
        // read inc/yml
        // write inc/yml
        // translate
    }

    /**
     * Return text translated according to $this->TRANSLATION[].
     * Return original text, if translation is not found.
     * The returned text is HTML escaped.
     * If the text differs only by case of the first letter, return its translation and change the case of its first
     *     letter.
     * @example: TRANSLATION['List'] = 'Seznam'; $this->translate('List') --> "Seznam",
     *     $this->translate('list') --> "seznam"
     * @example: TRANSLATION['list'] = 'seznam'; $this->translate('list') --> "seznam",
     *     $this->translate('List') --> "Seznam"
     * Or if the key in translation is all the same case, then it can be modified by changing the argument case.
     *     See L10nTest for examples.
     *
     * @param string $key
     * @param int|null $mbCaseMode https://www.php.net/manual/en/mbstring.constants.php
     *   supports only MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE, others are ignored
     * @param string|null $encoding null (default) for mb_internal_encoding()
     * @return string
     */
    public function translate($key, $mbCaseMode = null, $encoding = null)
    {
        Assert::notNull($this->selectedLanguage);
        $encoding = $encoding ?: mb_internal_encoding();
        $first = mb_substr($key, 0, 1, $encoding); // first char
        $rest = mb_substr($key, 1, null, $encoding); // 2nd char till the end
        $text = $key; // fall-back
        if (array_key_exists($key, $this->translation)) {
//            echo "exact";
            $text = $this->translation[$key];
        } else {
            $ucfirst = mb_strtoupper($first, $encoding);
            $lcfirst = mb_strtolower($first, $encoding);
            // $changeCase - 0 = no change, 1 = first upper, -1 = first lower, 2 = all caps, -2 = all lower
            if (array_key_exists($ucfirst . $rest, $this->translation)) {
//                echo "1";
                $text = $this->translation[$ucfirst . $rest];
//                $changeCase = 1;
                //$text = mb_strtoupper($ucfirst, $encoding) . $rest;
                $text = mb_strtolower(mb_substr($text, 0, 1, $encoding), $encoding)
                    . mb_substr($text, 1, null, $encoding);
            } elseif (array_key_exists($lcfirst . $rest, $this->translation)) {
//                echo "-1";
                $text = $this->translation[$lcfirst . $rest];
//                $changeCase = -1;
                //$text = mb_strtolower($lcfirst, $encoding) . $rest;
                $text = mb_strtoupper(mb_substr($text, 0, 1, $encoding), $encoding)
                    . mb_substr($text, 1, null, $encoding);
            } elseif (array_key_exists(mb_strtoupper($key, $encoding), $this->translation)) {
//                echo "2";
                $text = $this->translation[mb_strtoupper($key, $encoding)];
                $text = mb_strtolower($text, $encoding);
//                $changeCase = 2;
            } elseif (array_key_exists(mb_strtolower($key, $encoding), $this->translation)) {
//                echo "-2";
                $text = $this->translation[mb_strtolower($key, $encoding)];
                $text = mb_strtoupper($text, $encoding);
//                $changeCase = -2;
            } elseif (DEBUG_VERBOSE) {
//                echo "nothing";
                // if text isn't present in $this->translation array, let's log it to be translated
                error_log(
                    '[' . date("d-M-Y H:i:s") . '] ' .
                    //                    (array_key_exists('language', $this->options) && is_string($this->options['language']) ?
                    //                        $this->options['language'] : '')
                    $this->selectedLanguage
                    . '\\' . $key . PHP_EOL,
                    3,
                    'log/translation_missing.log'
                );
            }
        }
//        if ($changeCase) {
//            $fn = $changeCase > 0 ? 'mb_strtoupper' : 'mb_strtolower';
//            $text = $fn($first, $encoding) . (abs($changeCase) > 1 ? $fn($rest, $encoding) : $rest);
//        }
        if ($mbCaseMode === L_UCFIRST) {
//            echo "L-UCFIRST";
            $text = mb_strtoupper(mb_substr($text, 0, 1, $encoding), $encoding) . mb_substr($text, 1, null, $encoding);
        } elseif (
            is_int($mbCaseMode) && in_array($mbCaseMode, [MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE])
        ) {
//            echo "mbCaseMode";
            $text = mb_convert_case($text, $mbCaseMode, $encoding);
        }
        return Tools::h($text); // HTML escaped
    }

//    /**
//     * Translate defined string to the language stored in $_SESSION['language'].
//     * Returns original text if translation not found.
//     * (From MyCMS::translate)
//     * MyTableLister::translate
//     *
//     * @param string $id text to translate
//     * @param int|null $options case transposition - null || [MB_CASE_UPPER|MB_CASE_LOWER|MB_CASE_TITLE|L_UCFIRST]
//     * @return string
//     */
//    public function translateAsInMyCMS($id, $options = null)
//    {
//        if (
//            !isset($this->translate[$id]) && DEBUG_VERBOSE
//            && $_SESSION['language'] != DEFAULT_LANGUAGE
//        ) {
//            Debugger::log('Translation does not exist - ' . $id, ILogger::WARNING);
//        }
//        $result = array_key_exists($id, $this->translation) ? $this->translation[$id] : $id;
//        if ($options === L_UCFIRST) {
//            $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
//        } elseif (
//            is_int($options) && ($options == MB_CASE_UPPER || $options == MB_CASE_LOWER || $options == MB_CASE_TITLE)
//        ) {
//            $result = mb_convert_case($result, $options);
//        }
//        return $result;
//    }

    /**
     * Load the localisation file
     *
     * @param string $language
     * @return void
     */
    public function loadLocalisation($language)
    {
        // Assert ISO 639-1 format
        Assert::string($language);
        Assert::length($language, 2);
        $this->selectedLanguage = $language;

        // language
//        Assert::isArray($options['TRANSLATIONS']);
//        $this->TRANSLATIONS = $options['TRANSLATIONS'];
        //$translationFile = 'conf/l10n/admin-' . Tools::setifempty($_SESSION['language'], 'en') . '.yml';
        $translationFile = $this->prefix . $language . '.yml';

        // The union operator ( + ) might be more useful than array_merge.
        // The array_merge function does not preserve numeric key values.
        // If you need to preserve the numeric keys, then using + will do that.
        // TODO/Note: TRANSLATION is based on A project, rather than F project.
        //delete//$this->TRANSLATION += file_exists($translationFile) ? Yaml::parseFile($translationFile) : [];

        $languageFile = DIR_TEMPLATE . '/../language-' . $language . '.inc.php'; // deprecated

        if (file_exists($translationFile)) {
            $tempYaml = Yaml::parseFile($translationFile);
            Assert::isArray($tempYaml);
            //$this->translation += $tempYaml;
            $this->translation = $tempYaml;
        } elseif (file_exists($languageFile)) {
            // deprecated
            // todo: read the $prefix.$language.'.inc.php'
//            $languageFile = DIR_TEMPLATE . '/../language-' . $language . '.inc.php';
//            if (!file_exists($languageFile)) {
//                throw new \Exception("Missing expected language file {$languageFile}");
//            }
            // include (as include_once triggers error in PHPUnit tests because of attempted repeated includes)
            include $languageFile; // MUST contain $translation = [...];
            // TODO instead of PHP file with array $translation, let's put localized texts into yaml.
            /** @phpstan-ignore-next-line */
            if (!(isset($translation) && is_array($translation))) {
                throw new \Exception("Missing expected translation {$languageFile}");
            }
            /** @phpstan-ignore-next-line */
            $this->translation = $translation;
        } else {
            throw new \Exception("Missing expected language file both {$translationFile} and {$languageFile}");
        }
/**
 *
 * see TableAdmin::__construct
 *
220218 yml instead of inc.php
MyCMS::getSessionLanguage
If yml then read yml
Else
  //If-Else Deprecated
  If inc.php then
    Orig translation read
    Log->warn
  Else
    // Keep
    Exception
  Fi
Fi
*/
/**
 *
Admin:: section Translation
Reads

*/
    }

    /**
     * Update the localisation file
     * TBD
     *
     * @return void
     */
    public function updateLocalisation()
    {
/**
 *
AdminProcess::adminProcess
Rewrites
*/
    }
}
