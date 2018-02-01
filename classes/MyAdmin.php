<?php

namespace GodsDev\MyCMS;

/**
 * Parent for deployed Admin instance
 * 
 */
class MyAdmin extends MyCommon
{

    /**
     * Child may pre-populate $acceptedAttributes by addAcceptedmethod, all of them MUST be declared as protected/public variables
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        //nothing by default: $this->addAccepted('');
        parent::__construct($MyCMS, $options);
    }

}
