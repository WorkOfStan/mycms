<?php

namespace WorkOfStan\mycmsprojectnamespace;

use WorkOfStan\MyCMS\MyCMS;

/**
 * Class for a MyCMS object.
 * It holds all specific variables needed for this application.
 * (Last MyCMS/dist revision: 2021-05-20, v0.4.0)
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
