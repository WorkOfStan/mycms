<?php

/**
 * Replacement for PHP functions that returns false or null instead of the strict type.
 * These function throw an \Exception instead.
 * 
 * Usage:
 * put such line into a file declaration ...
 * use function GodsDev\MyCMS\ThrowableFunctions\mb_eregi_replace;
 * ... and mb_eregi_replace will refer to this Throwable function, while
 * \mb_eregi_replace will refer to the PHP built-in function
 */

namespace GodsDev\MyCMS\ThrowableFunctions;

use Exception;

/**
 * Return the argument unless it is `false`.
 *
 * @param mixed $result
 * @return mixed
 * @throws Exception
 */
function throwOnFalse($result)
{
    if ($result === false) {
        throw new Exception('error (false) ' . debug_backtrace()[1]['function']);
    }
    return $result;
}

/**
 * Return the argument unless it is `null`.
 *
 * @param mixed $result
 * @return mixed
 * @throws Exception
 */
function throwOnNull($result)
{
    if (is_null($result)) {
        throw new Exception('error (null) ' . debug_backtrace()[1]['function']);
    }
    return $result;
}

/**
 *
 * @param string $filename
 * @return int
 */
function filemtime($filename)
{
    return throwOnFalse(\filemtime($filename));
}


/**
 *
 * @param string $pattern
 * @param int $flags
 * @return string[]
 */
function glob($pattern, $flags = 0)
{
    return throwOnFalse(\glob($pattern, $flags));
}

/**
 *
 * @param mixed $value
 * @param int $flags
 * @param int $depth
 * @return string
 */
function json_encode($value, $flags = 0, $depth = 512)
{
    return throwOnFalse(\json_encode($value, $flags, $depth));
}

/**
 *
 * @param string $pattern
 * @param string $replacement
 * @param string $string
 * @param string $options OPTIONAL
 * @return string
 */
function mb_eregi_replace($pattern, $replacement, $string, $options = null)
{
    return
        is_null($options) ?
        throwOnFalse(throwOnNull(\mb_eregi_replace($pattern, $replacement, $string))) :
        throwOnFalse(throwOnNull(\mb_eregi_replace($pattern, $replacement, $string, $options)));
}

/**
 *
 * @param string $pattern
 * @param string $subject
 * @param string[] $matches
 * @param int $flags
 * @param int $offset
 * @return int
 */
function preg_match($pattern, $subject, array &$matches = null, $flags = 0, $offset = 0)
{
    return throwOnFalse(\preg_match($pattern, $subject, $matches, $flags, $offset));
}

/**
 *
 * @param string|string[] $pattern
 * @param string|string[] $replacement
 * @param string|string[] $subject
 * @param int $limit
 * @param int $count
 * @return string|string[]
 */
function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    return throwOnNull(\preg_replace($pattern, $replacement, $subject, $limit, $count));
}

/**
 * $subject is expected to be string, so the function returns string
 *
 * @param string|string[] $pattern
 * @param string|string[] $replacement
 * @param string $subject
 * @param int $limit
 * @param int $count
 * @return string
 */
function preg_replaceString($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    return throwOnNull(\preg_replace($pattern, $replacement, $subject, $limit, $count));
}
