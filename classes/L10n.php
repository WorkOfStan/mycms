<?php

namespace WorkOfStan\MyCMS;

use GodsDev\Tools\Tools;
use Symfony\Component\Yaml\Yaml;
use Tracy\Debugger;
use Tracy\ILogger;
use Webmozart\Assert\Assert;

/**
 * Localisation class unifying capabilities of translation method
 * Both read/update the yml
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

    /**
     *
     * @param string $prefix folder & prefix of the yml localisation file
     * @param array<string> $enabledLanguages
     */
    public function __construct($prefix, array $enabledLanguages)
    {
        $this->prefix = $prefix; // "$prefixXX.yml" where XX is the language e.g. 'conf/l10n/admin-'
        $this->enabledLanguages = array_keys($enabledLanguages);
        DEBUG_VERBOSE && Debugger::barDump($enabledLanguages, 'List of enabled languages in the prefix ' . $prefix);
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
     * @throws \InvalidArgumentException
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
                    'log/translation_missing_' . date("Y-m") . '.log'
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
        $languageFile = ($this->prefix != '' ? dirname(dirname(dirname($this->prefix))) : DIR_TEMPLATE . '/..')
            . '/language-' . $language . '.inc.php'; // deprecated

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
            if (!(isset($translation) && is_array($translation))) {
                throw new \Exception("Missing expected translation {$languageFile}");
            }
            return $translation;
        }
        throw new \Exception(
            "Missing expected '{$language}' language file both {$translationFile} and {$languageFile}"
        );
    }

    /**
     * Update the localisation file
     * Called from MyAdminProcess::processTranslationsUpdate (post[tr] - post[delete] + post[new] / post[rename])
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
        foreach ($this->enabledLanguages as $code) {
            $this->assertLanguage($code);
            $yml = [];
            if ($newStrings[0]) {
                $allStrings[$code][$newStrings[0]] = $newStrings[$code];
            }
            foreach ($allStrings[$code] as $key => $value) {
                if ($key == $oldName) {
                    $key = $newName;
                    $value = $deleteFlag ? false : $value;
                }
                if ($value) {
                    $yml[$key] = $value;
                }
            }
            // todo write only in case of changes
            $yamlDump = Yaml::dump($yml);
            // todo fix [warning] missing document start "---" (document-start)
            file_put_contents($this->prefix . $code . '.yml', $yamlDump);
        }
    }
}
