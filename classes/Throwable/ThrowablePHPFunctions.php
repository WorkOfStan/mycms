<?php

/**
 * Replacement for PHP functions that returns false or null instead of the strict type.
 * These function throw an \Exception instead.
 */

namespace GodsDev\MyCMS\Throwable;

use Exception;

/**
 *
 * @param mixed $result
 * @return void as it can't be used to return the result directly as mixed return would not be type strict
 * @throws Exception
 */
function throwOnFalse($result)
{
    if ($result === false) {
        throw new Exception('error ' . debug_backtrace()[1]['function']);
    }
}

/**
 *
 * @param mixed $result
 * @return void as it can't be used to return the result directly as mixed return would not be type strict
 * @throws Exception
 */
function throwOnNull($result)
{
    if (is_null($result)) {
        throw new Exception('error ' . debug_backtrace()[1]['function']);
    }
}

/**
 *
 * @param string $pattern
 * @param string $subject
 * @param array $matches
 * @param int $flags
 * @param int $offset
 * @return int
 */
function preg_match($pattern, $subject, array &$matches = null, $flags = 0, $offset = 0)
{
    $result = \preg_match($pattern, $subject, $matches, $flags, $offset);
    throwOnFalse($result);
    return $result;
}

/**
 *
 * @param string|array $pattern
 * @param string|array $replacement
 * @param string|array $subject
 * @param int $limit
 * @param int $count
 * @return string|array
 */
function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
    throwOnNull($result);
    return $result;
}

/**
 * $subject is expected to be string, so the function returns string
 *
 * @param string|array $pattern
 * @param string|array $replacement
 * @param string $subject
 * @param int $limit
 * @param int $count
 * @return string
 */
function preg_replaceString($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
    throwOnNull($result);
    return $result;
}
