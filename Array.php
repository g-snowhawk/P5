<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
/**
 * Array class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Array
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Swap elements.
     *
     * @param string $needle
     * @param array  $array
     * @param bool   $push
     *
     * @return mixed
     */
    public static function swap($needle, array $array, $push = false)
    {
        if ($index = array_search($needle, $array)) {
            $element = $array[$index];
            unset($array[$index]);
            if ($push) {
                array_push($array, $element);
            } else {
                array_unshift($array, $element);
            }

            return array_values($array);
        }

        return $array;
    }

    /**
     * Hash to Array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function values(array $array)
    {
        $ret = array();
        foreach ($array as $key => $value) {
            array_push($ret, $value);
        }

        return $ret;
    }

    /**
     * Check hash.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function is_hash(&$array)
    {
        if (!is_array($array)) {
            return false;
        }
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Repeat same value.
     *
     * @param mixed $value
     * @param int   $repeat
     *
     * @return array
     */
    public static function createRepeater($value, $repeat)
    {
        $arr = array();
        for ($i = 0; $i < $repeat; ++$i) {
            array_push($arr, $value);
        }

        return $arr;
    }
}
