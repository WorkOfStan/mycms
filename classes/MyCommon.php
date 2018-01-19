<?php

namespace GodsDev\MyCMS;

/**
 * Generic ancestor for classes that uses MyCMS
 * 
 */
class MyCommon
{

    use \Nette\SmartObject;

    /** @var \GodsDev\MyCMS\MyCMS */
    protected $MyCMS;

    /** @var array */
    protected $acceptedAttributes;

    /**
     * accepted attributes:
     */


    /**
     * Child may pre-populate $acceptedAttributes by addAcceptedmethod, all of them MUST be declared as protected/public variables
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = array())
    {
        //nothing by default $this->addAccepted('get session');
        foreach (array_merge(
                array(//default values
                ), $options) as $optionVariable => $optionContent) {
            if (in_array($optionVariable, $this->acceptedAttributes, true)) {
                $this->{$optionVariable} = $optionContent;
            }
        }
        $this->MyCMS = $MyCMS;
    }

    /**
     * 
     * @param string $acceptedList space delimited
     */
    protected function addAccepted($acceptedList)
    {
        $this->acceptedAttributes = array_merge(explode(' ', $acceptedList), $this->acceptedAttributes ? $this->acceptedAttributes : array());
    }

}
