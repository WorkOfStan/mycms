<?php

namespace WorkOfStan\mycmsprojectnamespace;

use Webmozart\Assert\Assert;
use WorkOfStan\Backyard\Backyard;

/**
 * Frequently used methods that are candidates to become part of MyCMS library as MyCMS\Utils
 * (Last MyCMS/dist revision: 2021-05-28, v0.4.2)
 *
 * @author rejthar@stanislavrejthar.com
 */
class Utils
{
    use \Nette\SmartObject;

    /**
     * Transforms array of scalars or arrays or objects to an HTML ordered list
     *
     * Note: when $delimiter is set to ";", niceDumpArray may be used to generate CSV-like dumps
     *
     *
     * @param array<mixed> $ary
     * @param bool $showKey
     * @param string $delimiter OPTIONAL default ': '
     * @param float $boldWhenHigherThan
     * @return string
     */
    public static function niceDumpArray(array $ary, $showKey, $delimiter = ': ', $boldWhenHigherThan = 6)
    {
        Assert::numeric($boldWhenHigherThan);
        Assert::isArray($ary);
        Assert::string($delimiter);
        $result = "<ol>" . PHP_EOL;
        foreach ($ary as $k => $v) {
            if (is_object($v)) {
                $k = $k . ' <i>(object ' . get_class($v) . ')</i>';
                $v = (array) $v;
            }
            $result .= ($showKey ? $k . (string) $delimiter : "");
            $result .= (
                is_array($v)
            ) ? self::niceDumpArray(
                $v,
                $showKey
            ) : (is_scalar($v) ? (
                ((int) $v > $boldWhenHigherThan) ? "<b>{$v}</b>" : $v
                ) : ("cannot display type: " . gettype($v))
            );
            $result .= "<br/>" . PHP_EOL;
        }
        return $result . "</ol>" . PHP_EOL;
    }

    /**
     *
     * @param string $str
     * @return string
     */
    public static function getDateFromMySQLTimestamp($str)
    {
        //TODO jistě je nějaká bezpečnější metoda,
        //jak vytáhnout datum z MySQL timestamp políčka než prvních 10 znaků
        return substr($str, 0, 10);
    }

    /**
     * Parse $_SERVER['HTTP_ACCEPT'] and identify AJAX call
     *
     * @param string $httpAccept
     * @return bool
     *
     * TODO: PHPUnit test
     */
    public static function directJsonCall($httpAccept)
    {
        // Chrome 76 jQuery $.ajax Accept: application/json, text/javascript, */*; q=0.01
        $result = (
            substr(
                $httpAccept,
                0,
                strlen('application/json')
            ) === 'application/json'
        ) || (substr($httpAccept, 0, strlen('text/javascript')) === 'text/javascript');
//        error_log($httpAccept . ' ' . print_r($result, true));//debug
        return $result;
    }

    /**
     * Standard output of json_encoded value.
     * If $directJsonCall then output preceded by json content-type header
     *
     * @param mixed $value
     * @param bool $directJsonCall
     * @param Backyard $backyard
     *
     * @return void
     */
    public static function jsonOrEcho($value, $directJsonCall, Backyard $backyard)
    {
        $response = json_encode($value);
        Assert::string($response);
        if ($directJsonCall) {
            $backyard->Json->outputJSON($response);
        } else {
            echo $response;
        }
    }
}
