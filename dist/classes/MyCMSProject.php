<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;

/**
 * Class for a MyCMS object.
 * It holds all variables needed for the used project.
 * Among others, it translates multilingual texts.
 */
class MyCMSProject extends MyCMS
{
    use \Nette\SmartObject;

    // attributes we need for this project

    /** @var array<string> */
    public $PAGES_SPECIAL;

    /** @var array<mixed> */
    public $SETTINGS;

    /** @var array<array> */
    public $WEBSITE;

    /**
     * Constructor
     *
     * @param array<mixed> $myCmsConf
     */
    public function __construct(array $myCmsConf = [])
    {
        parent::__construct($myCmsConf);
    }
}
