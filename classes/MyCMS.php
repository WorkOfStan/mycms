<?php

namespace GodsDev\MyCMS;

/**
 * Extension of a MyCMS object with translations.
 * It holds all variables needed for the used project.
 * Among others, it translates multilingual texts.
 *
 * For a new project it is expected to make a extended class and place
 * additional attributes needed for running, then use that class.
 *
 * required constants: DEFAULT_LANGUAGE
 */
class MyCMS extends MyCMSMonoLingual
{
    use \Nette\SmartObject;

    /**
     * Selected locale strings
     *
     * @var array<string>
     */
    public $TRANSLATION;

    /**
     * Available languages
     *
     * @var array<string>
     */
    public $TRANSLATIONS;

    /**
     * PARAMETRIC URL into TEMPLATE conditions (for FriendlyURL functionality)
     *
     * @var array<array>
     */
    public $templateAssignementParametricRules;

    /**
     * Type into database table mapping (for MyFriendlyUrl::findFriendlyUrlToken)
     *
     * @var array<string>
     */
    public $typeToTableMapping;

    /**
     * Constructor
     *
     * @param array<mixed> $myCmsConf
     */
    public function __construct(array $myCmsConf = [])
    {
        parent::__construct($myCmsConf);
    }

    /**
     *
     * @param array<string> $getArray $_GET or its equivalent
     * @param array<string> $sessionArray $_SESSION or its equivalent
     * @param bool $makeInclude for testing may be set to false as mycms itself doesn't contain the language-XX.inc.php
     * @return string to be used as $_SESSION['language']
     *
     * constant DEFAULT_LANGUAGE expected
     */
    public function getSessionLanguage(array $getArray, array $sessionArray, $makeInclude = true)
    {
        // rtrim($string, '/\\'); //strip both forward and back slashes to normalize both xx and xx/ to xx
        $resultLanguage = (
            isset($getArray['language']) && isset($this->TRANSLATIONS[rtrim($getArray['language'], '/\\')])
        ) ?
            rtrim($getArray['language'], '/\\') :
            ((isset($sessionArray['language']) && isset($this->TRANSLATIONS[$sessionArray['language']])) ?
            $sessionArray['language'] : DEFAULT_LANGUAGE);
        if ($makeInclude) {
            $languageFile = DIR_TEMPLATE . '/../language-' . $resultLanguage . '.inc.php';
            if (!file_exists($languageFile)) {
                throw new \Exception("Missing expected language file {$languageFile}");
            }
            include_once $languageFile; //MUST contain $translation = [...];
            // TODO instead of PHP file with array $translation, let's put localized texts into yaml.
            /** @phpstan-ignore-next-line */
            if (!(isset($translation) && is_array($translation))) {
                throw new \Exception("Missing expected translation {$languageFile}");
            }
            /** @phpstan-ignore-next-line */
            $this->TRANSLATION = $translation;
        }
        return $resultLanguage;
    }

    /**
     * Translate defined string to the language stored in $_SESSION['language'].
     * Returns original text if translation not found.
     *
     * @param string $id text to translate
     * @param int|null $options case transposition - null || [MB_CASE_UPPER|MB_CASE_LOWER|MB_CASE_TITLE|L_UCFIRST]
     * @return string
     */
    public function translate($id, $options = null)
    {
        if (
            !isset($this->TRANSLATION[$id]) && isset($_SESSION['test-translations'])
            && $_SESSION['language'] != DEFAULT_LANGUAGE
        ) {
            $this->logger->warning('Translation does not exist - ' . $id);
        }
        $result = isset($this->TRANSLATION[$id]) ? $this->TRANSLATION[$id] : $id;
        if ($options === L_UCFIRST) {
            $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
        } elseif (
            is_int($options) && ($options == MB_CASE_UPPER || $options == MB_CASE_LOWER || $options == MB_CASE_TITLE)
        ) {
            $result = mb_convert_case($result, $options);
        }
        return $result;
    }
}
