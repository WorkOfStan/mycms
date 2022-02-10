<?php

namespace WorkOfStan\MyCMS;

use Tracy\Debugger;

/**
 * Generic ancestor for classes that uses MyCMS
 *
 */
class MyCommon
{
    use \Nette\SmartObject;

    // MUST be the same both for Controller extends MyController extends MyCommon and MyFriendlyUrl extends MyCommon
    const TEMPLATE_NOT_FOUND = 'error404';

    // MUST be the same both for Controller extends MyController extends MyCommon and MyFriendlyUrl extends MyCommon
    const TEMPLATE_DEFAULT = 'home';

    /** @var MyCMS */
    protected $MyCMS;

    /**
     * Bleeds information
     * false - nothing, true - Debugger::barDump()
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     *
     * @param MyCMS $MyCMS
     * @param array<mixed> $options overrides default values of declared properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        foreach ($options as $optionVariable => $optionContent) {
            if (property_exists($this, $optionVariable)) {
                $this->{$optionVariable} = $optionContent;
            }
        }
        $this->MyCMS = $MyCMS;
    }

    /**
     * Dumps information about a variable in Tracy Debug Bar or is silent
     *
     * @param  mixed  $var
     * @param  string $title
     * @param  array<mixed> $options of Debugger::barDump
     *   where array keys are [Dumper::DEPTH, Dumper::TRUNCATE, Dumper::LOCATION, Dumper::LAZY]
     * @return mixed  variable itself
     */
    protected function verboseBarDump($var, $title = null, array $options = [])
    {
        if ($this->verbose == true) {
            Debugger::barDump($var, $title, $options);
        }
        return $var;
    }

    /**
     * Dumps information about a variable in Tracy Debug Bar or is silent
     *
     * @param  string $var
     * @param  string $title
     * @param  array<mixed> $options of Debugger::barDump
     *   where array keys are [Dumper::DEPTH, Dumper::TRUNCATE, Dumper::LOCATION, Dumper::LAZY]
     * @return string variable itself
     */
    protected function verboseBarDumpString($var, $title = null, array $options = [])
    {
        if ($this->verbose == true) {
            Debugger::barDump($var, $title, $options);
        }
        return $var;
    }
}
