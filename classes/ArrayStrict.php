<?php

namespace WorkOfStan\MyCMS;

use Webmozart\Assert\Assert;

/**
 * Enforce type strictness of array fields
 * Some arrays contain various types (i.e. field $_GET or $_POST) so to ensure that its values can be used
 * in a type strict way as parameters of various functions without wrapping them with conditions and assertions,
 * this simple object has methods that return the value only if the value is of the required type.
 * Otherwise an Exception is thrown.
 *
 * @author rejthar@stanislavrejthar.com
 */
class ArrayStrict
{
    use \Nette\SmartObject;

    /**
     *
     * @var array<mixed>
     */
    private $arr;

    /**
     *
     * @param array<mixed>|null $arr
     */
    public function __construct($arr)
    {
        $this->arr = is_null($arr) ? [] : $arr;
    }

    /**
     * Asserts that all values of the array are of string type
     * To prevent 'expects array<string>, mixed given' PHPStan error mesage
     *
     * @return string[]
     */
    public function arrayString()
    {
        $newArr = [];
        foreach ($this->arr as $k => $v) {
            Assert::string($v);
            $newArr[$k] = $v;
        }
        return $newArr;
    }

    /**
     * Returns bool value of the field with bool value or throws an Exception
     *
     * @param string $field
     * @return bool
     */
    public function bool($field)
    {
        Assert::boolean($this->isset($field));
        Assert::boolean($this->arr[$field]); // for static analysis
        return $this->arr[$field];
    }

    /**
     * Returns float value of the field with float value or throws an Exception
     *
     * @param string $field
     * @return float
     */
    public function float($field)
    {
        Assert::float($this->isset($field));
        Assert::float($this->arr[$field]); // for static analysis
        return $this->arr[$field];
    }

    /**
     * Returns int value of the field with int value or throws an Exception
     *
     * @param string $field
     * @return integer
     */
    public function integer($field)
    {
        Assert::integer($this->isset($field));
        Assert::integer($this->arr[$field]); // for static analysis
        return $this->arr[$field];
    }

    /**
     * Returns the value of the field or throws an Exception if the $field is not set
     *
     * @param string $field
     * @return mixed
     */
    private function isset($field)
    {
        if (isset($this->arr[$field])) {
            return $this->arr[$field];
        }
        throw new \Exception("Field {$field} isn't set.");
    }

    /**
     * Returns whether the field is set or not
     *
     * @param string $field
     * @return bool
     */
    public function keyExists($field)
    {
        return array_key_exists($field, $this->arr);
    }

    /**
     * Returns string value of the field with string value or throws an Exception
     *
     * @param string $field
     * @return string
     */
    public function string($field)
    {
        Assert::string($this->isset($field));
        Assert::string($this->arr[$field]); // for static analysis
        return $this->arr[$field];
    }

    /**
     * Returns the original array (empty array in case of the original null value)
     *
     * @return array<mixed>
     */
    public function dump()
    {
        return $this->arr;
    }
}
