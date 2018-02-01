<?php

namespace GodsDev\MyCMS;

class ProjectCommon
{

    use \Nette\SmartObject;

    /** @var \GodsDev\MyCMS\MyCMS */
    protected $MyCMS;

    /**
     * 
     * @param \GodsDev\MyCMS\MyCMS $MyCMS
     */
    public function __construct(MyCMS $MyCMS)
    {
        $this->MyCMS = $MyCMS;
    }

    /**
     * Shortcut for echo'<pre>'; var_dump(); and exit;
     * @param mixed variable(s) or expression to display
     */
    public static function dump($var)
    {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        exit;
    }

    //@todo refactor as getTexy() which returns Texy object that is initialized in this dynamic class
    public static function prepareTexy()
    {
        global $Texy;
        if (!is_object($Texy)) {
            $Texy = new \Texy();
            $Texy->headingModule->balancing = TEXY_HEADING_FIXED;
            $Texy->headingModule->generateID = true;
            $Texy->allowedTags = true;
            $Texy->dtd['a'][0]['data-lightbox'] = 1;
        }
    }

    /**
     * 
     * @param string $stringOfTime
     * @param string $language
     */
    public static function localDate($stringOfTime, $language)
    {
        switch ($language) {
            case 'cs': return date('j.n.Y', strtotime($stringOfTime));
        }
        //en
        return date('D, j M Y', strtotime($stringOfTime));
    }

}
