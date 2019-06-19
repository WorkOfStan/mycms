<?php

namespace GodsDev\mycmsprojectnamespace;

/**
 * Class for a MyCMS object.
 * It holds all variables needed for the used project.
 * Among others, it translates multilingual texts.
 */
class MyCMSProject extends \GodsDev\MyCMS\MyCMS
{

    // attributes we need for this project

    /** @var array */
    public $PAGES_SPECIAL;

    /** @var array */    
    public $SETTINGS;

    /** @var array */
    public $WEBSITE;

    /**
     * Constructor
     *
     * @param array $myCmsConf
     */
    public function __construct(array $myCmsConf = [])
    {
        parent::__construct($myCmsConf);
    }

}
