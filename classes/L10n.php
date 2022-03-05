<?php

namespace WorkOfStan\MyCMS;

use GodsDev\Tools\Tools;
use Symfony\Component\Yaml\Yaml;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;

/**
 * Localisation class unifying capabilities of translation method
 *
 * @author rejthar@stanislavrejthar.com
 */
class L10n
{
    use \Nette\SmartObject;

    /** @var array<string> Available languages for MyCMS */
    public $enabledLanguages; // ['en' => 'English']

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
     * @param array<string> $enabledLanguages
     */
    public function __construct($prefix, array $enabledLanguages)
    {
        $this->prefix = $prefix; // "$prefixXX.yml" where XX is the language e.g. 'conf/l10n/admin-'
        $this->enabledLanguages = $enabledLanguages;
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
            $text = $this->translation[$key];
        } else {
            $ucfirst = mb_strtoupper($first, $encoding);
            $lcfirst = mb_strtolower($first, $encoding);
            if (array_key_exists($ucfirst . $rest, $this->translation)) {
                $text = $this->translation[$ucfirst . $rest];
                $text = mb_strtolower(mb_substr($text, 0, 1, $encoding), $encoding)
                    . mb_substr($text, 1, null, $encoding);
            } elseif (array_key_exists($lcfirst . $rest, $this->translation)) {
                $text = $this->translation[$lcfirst . $rest];
                $text = mb_strtoupper(mb_substr($text, 0, 1, $encoding), $encoding)
                    . mb_substr($text, 1, null, $encoding);
            } elseif (array_key_exists(mb_strtoupper($key, $encoding), $this->translation)) {
                $text = mb_strtolower($this->translation[mb_strtoupper($key, $encoding)], $encoding);
            } elseif (array_key_exists(mb_strtolower($key, $encoding), $this->translation)) {
                $text = mb_strtoupper($this->translation[mb_strtolower($key, $encoding)], $encoding);
            } elseif (DEBUG_VERBOSE) {
                // if text isn't present in $this->translation array, let's log it to be translated
                error_log(
                    '[' . date("d-M-Y H:i:s") . '] ' . $this->selectedLanguage . '\\' . $key . PHP_EOL,
                    3,
                    'log/translation_missing.log'
                );
            }
        }
        if ($mbCaseMode === L_UCFIRST) {
            $text = mb_strtoupper(mb_substr($text, 0, 1, $encoding), $encoding) . mb_substr($text, 1, null, $encoding);
        } elseif (
            is_int($mbCaseMode) && in_array($mbCaseMode, [MB_CASE_UPPER, MB_CASE_LOWER, MB_CASE_TITLE])
        ) {
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
     * Assert ISO 639-1 format of the language identifier
     *
     * @param string $language
     * @return string
     */
    private function assertLanguage($language)
    {
        // Assert ISO 639-1 format
        Assert::string($language);
        Assert::length($language, 2);
        Assert::inArray($language, $this->enabledLanguages);
        return $language;
    }

    /**
     * Load the localisation file
     *
     * @param string $language
     * @return void
     */
    public function loadLocalisation($language)
    {
        $this->selectedLanguage = $this->assertLanguage($language);
        $this->translation = $this->readLocalisation($language);
    }

    /**
     * Returns the localisation string array
     *
     * @param string $language
     * @return string[]
     */
    public function readLocalisation($language)
    {
        $this->assertLanguage($language);
        $translationFile = $this->prefix . $language . '.yml';

        // expected to transform APP_DIR/conf/l10n/file into APP_DIR
        $languageFile = dirname(dirname(dirname($this->prefix))) . '/language-' . $language . '.inc.php'; // deprecated

        if (file_exists($translationFile)) {
            $tempYaml = Yaml::parseFile($translationFile);
            DEBUG_VERBOSE && Debugger::log("Yaml parse {$translationFile}", ILogger::INFO);
            Assert::isArray($tempYaml);
            return $tempYaml;
        }
        if (file_exists($languageFile)) {
            // deprecated (read the $prefix.$language.'.inc.php')
            DEBUG_VERBOSE && Debugger::log("including {$languageFile} with \$translation array", ILogger::INFO);
            include $languageFile; // MUST contain $translation = [...];
            /** @phpstan-ignore-next-line */
            if (!(isset($translation) && is_array($translation))) {
                throw new \Exception("Missing expected translation {$languageFile}");
            }
            /** @phpstan-ignore-next-line */
            return $translation;
        }
        throw new \Exception(
            "Missing expected '{$language}' language file both {$translationFile} and {$languageFile}"
        );

/**
 *
Admin:: section Translation
Reads

*/
    }

    /**
     * Update the localisation file
     * // tr - delete + new / rename
     *
     * @param array<array<string>> $allStrings
     * @param array<string> $newStrings
     * @param string $oldName
     * @param string $newName
     * @param bool $deleteFlag
     * @return void
     */
    public function updateLocalisation(array $allStrings, array $newStrings, $oldName, $newName, $deleteFlag)
    {
/**
 *
AdminProcess::adminProcess
Rewrites
*/


            //$postForYml = $post; // before legacy changes
            foreach (array_keys($this->enabledLanguages) as $code) {
                $this->assertLanguage($code);
                // new yml
                $yml = [];

                // legacy inc.php
//                $fp = fopen("language-$code.inc.php", 'w+');
//                Assert::resource($fp);
//                fwrite($fp, "<?php\n\n// MyCMS->getSessionLanguage expects \$translation=\n\$translation = [\n");

                // common
//                Assert::isArray($post['new']);
                if ($newStrings[0]) {
//                    Assert::isArray($post['tr']);
//                    Assert::isArray($post['tr'][$code]);
                    $allStrings[$code][$newStrings[0]] = $newStrings[$code];
                }
//                Assert::isArray($post['tr']);
//                Assert::isArray($post['tr'][$code]);
                foreach ($allStrings[$code] as $key => $value) {
                    if ($key == $oldName) {
                        $key = $newName;
                        $value = $deleteFlag ? false : $value;
                    }
                    if ($value) {
                        // legacy inc.php
//                        Assert::string($key);
//                        fwrite($fp, "    '" . strtr($key, array('&apos;' => "\\'", "'" => "\\'", '&amp;' => '&'))
//                            . "' => '" . strtr($value, array('&appos;' => "\\'", "'" => "\\'", '&amp;' => '&'))
//                            . "',\n");
                        // new yml
                        $yml[$key] = $value;
                    }
                }

                // legacy inc.php
//                fwrite($fp, "];\n");
//                fclose($fp);

                // new yml
//                if (
//                    !(isset($this->featureFlags['languageFileWriteIncOnlyNotYml'])
//                    && $this->featureFlags['languageFileWriteIncOnlyNotYml'])
//                ) {
                    // refactor into L10n
                    $yamlDump = Yaml::dump($yml);
                    // todo fix [warning] missing document start "---" (document-start)
                    file_put_contents($this->prefix . $code . '.yml', $yamlDump);
                    //$localisation = new L10n($this->prefixUiL10n);
//                }

                // new yml
                // tr - delete + new / rename
            }


    }
}
