<?php

namespace GodsDev\MyCMS;

use Psr\Log\LoggerInterface;

class ProjectCommon {

    /** 
     * @todo refactor to PSR-3 compliant logger (either BackyardError or apache/log4php 2.3.0 or ^2.3.0 ??)
     * 
     * log to process file
     * @param string what to write
     */
    public static function log_($what) {
        global $MyCMS;
        if (preg_match('/^[a-z]+: /i', $what, $level)) { //@todo level není nikde nastavena?
            $level = strtolower(trim($level[0], ': '));
        }
        if (!is_string($level) || !isset($MyCMS->LOG_SETTINGS[$level])) {
            $level = 'other';
        }
        if (isset($MyCMS->LOG_SETTINGS[$level]['log']) && $MyCMS->LOG_SETTINGS[$level]['log'] === false) {
            return;
        }
        try {
            $logfile = fopen($MyCMS->LOG_SETTINGS[$level]['file'] ?: LOG_FILE, 'a+');
            fwrite($logfile, $what = date('Y-m-d H:i:s ') . (string) $what . "\n");
            fclose($logfile);
            if (isset($MyCMS->LOG_SETTINGS[$level]['mail']) && $MyCMS->LOG_SETTINGS[$level]['mail']) {
                mail($MyCMS->LOG_SETTINGS[$level]['mail'], 'Critical log', $what, "MIME-Version: 1.0\nContent-type:text/plain; charset=utf-8\nFrom: " . EMAIL_ADMIN . "\nDate: " . date('r'));
            }
        } catch (Exception $e) {
            error_log($what);
        }
    }

    /** 
     * Shortcut for echo'<pre>'; var_dump(); and exit;
     * @param mixed variable(s) or expression to display
     */
    public static function dump($var) {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        exit;
    }

    //@todo refactor as getTexy() which returns Texy object that is initialized in this dynamic class
    public static function prepareTexy() {
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
    public static function localDate($stringOfTime, $language) {
        switch ($language) {
            case 'cs': return date('j.n.Y', strtotime($stringOfTime));
        }
        //en
        return date('D, j M Y', strtotime($stringOfTime));
    }

}
