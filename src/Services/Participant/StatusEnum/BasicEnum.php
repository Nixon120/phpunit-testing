<?php

namespace AllDigitalRewards\RewardStack\Services\Participant\StatusEnum;

use ReflectionClass;

abstract class BasicEnum
{
    private static $constCacheArray = null;

    private static function getConstants()
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value, $strict = true)
    {
        if ($value === null || is_numeric($value) === false || ctype_digit((string)$value) === false) {
            return false;
        }

        $values = array_values(self::getConstants());
        return in_array((int) $value, $values, $strict);
    }

    /**
     * Validation has already happened at this point so we can
     * be sure the value will be returned searching by KEY or VALUE
     *
     * @param $status
     * @return mixed
     */
    public static function hydrateStatus($status)
    {
        $status = strtolower($status);
        $values = self::getConstants();
        foreach ($values as $key => $value) {
            if ($status === strtolower($key) || $status === strtolower($value)) {
                return $value;
            }
        }
    }
}
