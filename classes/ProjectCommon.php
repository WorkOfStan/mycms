<?php

namespace GodsDev\MyCMS;

class ProjectCommon extends MyCommon
{

    use \Nette\SmartObject;

    /**
     * accepted attributes:
     */

    /** @var string */
    protected $language;

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

    /**
     * Replace spaces with \0160 after selected short words
     * The list of selected words may be enlarged or redefined in the ProjectSpecific child
     * 
     * @param string $text
     * @param array $addReplacePatterns add or redefine patterns
     * @return string
     */
    public function correctLineBreak($text, array $addReplacePatterns = [])
    {
        $replacePatterns = array_merge(array(
            '/ a /' => ' a ',
            '/ i /' => ' i ',
            '/ k /' => ' k ',
            '/ o /' => ' o ',
            '/ s /' => ' s ',
            '/ u /' => ' u ',
            '/ v /' => ' v ',
            '/ ve /' => ' ve ',
            '/ z /' => ' z ',
            '/ %/' => ' %',
            '/ & /' => ' & ',
            '/ an /' => ' an ',
            '/Industry 4.0/' => 'Industry 4.0',
                ), $addReplacePatterns);
        return preg_replace(array_keys($replacePatterns), array_values($replacePatterns), $text);
    }

}
