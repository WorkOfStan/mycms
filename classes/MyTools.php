<?php

namespace WorkOfStan\mycms;

use GodsDev\Tools\Tools;
use Webmozart\Assert\Assert;

/**
 * Wrapper for Tools
 *
 * @author rejthar@stanislavrejthar.com
 */
class MyTools
{
    use \Nette\SmartObject;

    /**
     * h that accepts mixed but throws exception if it isn't a string
     *
     * @param mixed $str
     * @return string
     */
    public static function h($str)
    {
        Assert::string($str);
        return Tools::h($str);
    }

}
