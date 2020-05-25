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
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     * @param array $options that overrides default values within constructor
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        $this->result = [
            'template' => 'home',
            'context' => ($this->MyCMS->context ? $this->MyCMS->context : [
            'pageTitle' => '',
            ])
        ];
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
        return [
            "get" => $this->get,
            "session" => $this->session
                //,"section_styles" => $this->sectionStyles
        ];
    }

}
