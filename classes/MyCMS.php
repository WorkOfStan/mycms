<?php

namespace WorkOfStan\MyCMS;

use WorkOfStan\MyCMS\L10n;

/**
 * Extension of a MyCMS object with translations.
 * It holds all variables needed for the used project.
 * Among others, it translates multilingual texts.
 *
 * For a new project it is expected to make an extended class and place
 * there the additional attributes needed for running, then use that class.
 *
 * required constants: DEFAULT_LANGUAGE
 */
class MyCMS extends MyCMSMonoLingual
{
    use \Nette\SmartObject;

    /** @var L10n Folder and name prefix of localisation yml */
    protected $localisation;
    /** @var string Folder and name prefix of localisation yml */
    protected $prefixL10n;
    /** @var array<array<string|bool>> PARAMETRIC URL into TEMPLATE conditions (for FriendlyURL functionality) */
    public $templateAssignementParametricRules;
    /** @var string[] Type into database table mapping (for MyFriendlyUrl::findFriendlyUrlToken) */
    public $typeToTableMapping;
    /** @var string[] Available languages */
    public $TRANSLATIONS;

    /**
     * Constructor
     *
     * @param mixed[] $myCmsConf
     */
    public function __construct(array $myCmsConf = [])
    {
        parent::__construct($myCmsConf);
        $this->localisation = new L10n($this->prefixL10n, $this->TRANSLATIONS);
    }

    /**
     *
     * @param mixed[] $getArray $_GET or its equivalent
     * @param mixed[] $sessionArray $_SESSION or its equivalent
     * @param bool $makeInclude for testing may be set to false as mycms itself doesn't contain the language-XX.inc.php
     * @return string to be used as $_SESSION['language']
     *
     * constant DEFAULT_LANGUAGE expected
     */
    public function getSessionLanguage(array $getArray, array $sessionArray, $makeInclude = true)
    {
        // rtrim($string, '/\\'); //strip both forward and back slashes to normalize both xx and xx/ to xx
        $resultLanguage = (
            isset($getArray['language']) && is_string(($getArray['language']))
            && isset($this->TRANSLATIONS[rtrim($getArray['language'], '/\\')])
        ) ?
            rtrim($getArray['language'], '/\\') :
            (
                (isset($sessionArray['language']) && is_string(($sessionArray['language']))
                && isset($this->TRANSLATIONS[$sessionArray['language']])
                ) ? $sessionArray['language'] : DEFAULT_LANGUAGE
            );
        if ($makeInclude) {
            $this->localisation->loadLocalisation($resultLanguage);
        }
        return $resultLanguage;
    }

    /**
     * Translate defined string to the language stored in $_SESSION['language'].
     * Returns original text if translation not found.
     * TODO: refactor this wrapper away
     *
     * @param string $id text to translate
     * @param int|null $options case transposition - null || [MB_CASE_UPPER|MB_CASE_LOWER|MB_CASE_TITLE|L_UCFIRST]
     * @return string
     */
    public function translate($id, $options = null)
    {
        return $this->localisation->translate($id, $options);
    }
}
