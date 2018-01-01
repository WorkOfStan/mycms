<?php

namespace GodsDev\MyCMS;

/**
 * Controller ascertain what the request is
 * 
 * The default template is `home`
 */
class MyController
{

    use \Nette\SmartObject;

    /** @var \GodsDev\MyCMS\MyCMS */
    protected $MyCMS;

    /** @var array */
    protected $result;

    /** @var array */
    protected $acceptedAttributes;

    //accepted attributes:
    /** @var array */
    protected $get;

    /** @var array */
    protected $session;

    /**
     * Child may pre-populate $acceptedAttributes by addAcceptedmethod, all of them MUST be declared as variables
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        $this->addAccepted('get session');
        foreach (array_merge(
                array(//default values
                ), $options) as $optionVariable => $optionContent) {
            if (in_array($optionVariable, $this->acceptedAttributes, true)) {
                $this->{$optionVariable} = $optionContent;
            }
        }
        $this->MyCMS = $MyCMS;
        $this->result = array("template" => "home", "context" => ($this->MyCMS->context ? $this->MyCMS->context : array()));
    }

    /**
     * 
     * @param string $acceptedList space delimited
     */
    protected function addAccepted($acceptedList)
    {
        $this->acceptedAttributes = array_merge(explode(' ', $acceptedList), $this->acceptedAttributes ? $this->acceptedAttributes : array());
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
        return array("get" => $this->get, "session" => $this->session,
                //"section_styles" => $this->sectionStyles
        );
    }

}
