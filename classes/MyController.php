<?php

namespace GodsDev\MyCMS;

/**
 * Controller ascertain what the request is
 * 
 * The default template is `home`
 */
class MyController extends MyCommon
{

    /** @var array */
    protected $result;

    /**
     * accepted attributes:
     */

    /** @var array */
    protected $get;

    /** @var array */
    protected $session;

    /**
     * Child may pre-populate $acceptedAttributes by addAcceptedmethod, all of them MUST be declared as protected/public variables
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        parent::__construct($MyCMS, $options);
        $this->result = array("template" => "home", "context" => ($this->MyCMS->context ? $this->MyCMS->context : array()));
    }

    /**
     * Outputs changed $MyCMS->template and $MyCMS->context as fields of an array
     * 
     * @return array
     */
    public function controller()
    {
        return $this->result;
    }

    /**
     * For PHP Unit test
     * 
     * @return array
     */
    public function getVars()
    {
        return array(
            "get" => $this->get,
            "session" => $this->session
                //,"section_styles" => $this->sectionStyles
        );
    }

}
