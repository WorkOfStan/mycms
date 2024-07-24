<?php

/**
 * Replacement for PHP functions that returns false or null instead of the strict type.
 * These function throw an \Exception instead.
 *
 * Usage:
 * put such line into a file declaration ...
 * use function WorkOfStan\MyCMS\ThrowableFunctions\mb_eregi_replace;
 * ... and mb_eregi_replace will refer to this Throwable function, while
 * \mb_eregi_replace will refer to the PHP built-in function
 */

namespace WorkOfStan\MyCMS\ThrowableFunctions;

use Exception;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

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
 * @throws \Webmozart\Assert\InvalidArgumentException
 */
function filemtime($filename)
{
    $result = throwOnFalse(\filemtime($filename));
    Assert::integer($result);
    return $result;
}

/**
 *
 * @param string $pattern
 * @param int $flags
 * @return string[]
 * @throws Exception
 */
function glob($pattern, $flags = 0)
{
    $result = \glob($pattern, $flags);
    if ($result === false) {
        throw new Exception('error (false) ' . debug_backtrace()[1]['function']);
    }
    return $result;
}

/**
 *
 * @param mixed $value
 * @param int $flags
 * @param int<1, max> $depth
 * @return string
 * @throws Exception
 * @throws InvalidArgumentException
 */
function json_encode($value, $flags = 0, $depth = 512)
{
    $result = throwOnFalse(\json_encode($value, $flags, $depth));
    Assert::string($result);
    return $result;
}

/**
 *
 * @param string $pattern
 * @param string $replacement
 * @param string $string
 * @param string $options OPTIONAL
 * @return string
 * @throws Exception
 * @throws InvalidArgumentException
 */
function mb_eregi_replace($pattern, $replacement, $string, $options = null)
{
    $result =
        is_null($options) ?
        throwOnFalse(throwOnNull(\mb_eregi_replace($pattern, $replacement, $string))) :
        throwOnFalse(throwOnNull(\mb_eregi_replace($pattern, $replacement, $string, $options)));
    Assert::string($result);
    return $result;
}

/**
 *
 * @param string $pattern
 * @param string $subject
 * @param string[] $matches
 * @param 0|256|512|768 $flags
 * @param int $offset
 * @return int
 * @throws Exception
 * @throws InvalidArgumentException
 */
function preg_match($pattern, $subject, array &$matches = null, $flags = 0, $offset = 0)
{
    $result = throwOnFalse(\preg_match($pattern, $subject, $matches, $flags, $offset));
    Assert::integer($result);
    return $result;
}

/**
 *
 * @param string $pattern
 * @param string $subject
 * @param string[] $matches
 * @param int $flags
 * @param int $offset
 * @return int
 * @throws Exception
 * @throws InvalidArgumentException
 */
function preg_match_all($pattern, $subject, array &$matches = null, $flags = 0, $offset = 0)
{
    $result = throwOnFalse(\preg_match_all($pattern, $subject, $matches, $flags, $offset));
    Assert::integer($result);
    return $result;
}

/**
 *
 * @param string|string[] $pattern
 * @param string|string[] $replacement
 * @param string|string[] $subject
 * @param int $limit
 * @param int $count
 * @return string|string[]
 * @throws Exception
 */
function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
    if (is_null($result)) {
        throw new Exception('error (null) ' . debug_backtrace()[1]['function']);
    }
    return $result;
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
 * @throws Exception
 * @throws InvalidArgumentException
 */
function preg_replaceString($pattern, $replacement, $subject, $limit = -1, &$count = null)
{
    $result = throwOnNull(\preg_replace($pattern, $replacement, $subject, $limit, $count));
    Assert::string($result);
    return $result;
}

/**
 *
 * @param string $datetime if it is not string, an Exception is thrown
 * @param int|null $baseTimestamp
 * @return int
 * @throws Exception
 * @throws InvalidArgumentException
 */
function strtotime($datetime, $baseTimestamp = null)
{
    $result = throwOnFalse(\strtotime($datetime, is_null($baseTimestamp) ? time() : $baseTimestamp));
    Assert::integer($result);
    return $result;
}
