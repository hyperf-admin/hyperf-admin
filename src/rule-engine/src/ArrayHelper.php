<?php
namespace HyperfAdmin\RuleEngine;

class ArrayHelper
{
    public static function array_get(string $key, $arr = [], $default = null)
    {
        $path = explode('.', $key);
        foreach($path as $key) {
            $key = trim($key);
            if(empty($arr) || !isset($arr[$key])) {
                return $default;
            }
            $arr = $arr[$key];
        }

        return $arr;
    }

    public static function array_set(array &$array, $key, $value): array
    {
        if(is_null($key)) {
            return $array = $value;
        }
        if(!is_string($key)) {
            $array[$key] = $value;

            return $array;
        }
        $keys = explode('.', $key);
        while(count($keys) > 1) {
            $key = array_shift($keys);
            if(!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function array_depth(array $array)
    {
        $max_depth = 1;
        foreach($array as $value) {
            if(is_array($value)) {
                $depth = array_depth($value) + 1;
                if($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    public static function array_remove(array &$arr, $key)
    {
        $current = self::array_get($key, $arr);
        if($current === null) {
            return false;
        }
        $keys = explode('.', $key);
        $target = &$arr;
        $unset_key = array_pop($keys);
        while($item = array_shift($keys)) {
            $target = &$target[$item];
        }
        unset($target[$unset_key]);
    }
}

