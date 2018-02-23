<?php

namespace GodsDev\MYCMSPROJECTNAMESPACE;

/**
 * Class for a MyCMS object.
 * It holds all variables needed for the used project.
 * Among others, it translates multilingual texts.
 */
class MyCMSProject extends \GodsDev\MyCMS\MyCMS
{

    // attributes we need for this project
    public $PAGES_SPECIAL;
    public $SETTINGS;
    public $WEBSITE;

    /**
     * Constructor
     *
     * @param array $myCmsConf
     */
    public function __construct(array $myCmsConf = array())
    {
        parent::__construct($myCmsConf);
    }

}
