<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

/**
 * Environment Class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com/>
 */
class Environment
{
    /**
     * Object constructor.
     */
    public function __construct()
    {
    }

    public static function cookie($key)
    {
        return filter_input(INPUT_COOKIE, $key, FILTER_SANITIZE_STRING);
    }

    public static function get($key, $filter = FILTER_SANITIZE_STRING)
    {
        return filter_input(INPUT_GET, $key, $filter);
    }

    public static function post($key, $filter = FILTER_SANITIZE_STRING, $options = null)
    {
        return filter_input(INPUT_POST, $key, $filter, $options);
    }

    public static function env($key, $filter = FILTER_SANITIZE_STRING)
    {
        $value = filter_input(INPUT_ENV, $key, $filter);
        if (is_null($value) && isset($_ENV[$key])) {
            $value = filter_var($_ENV[$key], $filter);
        }

        return $value;
    }

    public static function server($key, $filter = FILTER_SANITIZE_STRING)
    {
        $key = strtoupper($key);
        $value = filter_input(INPUT_SERVER, $key, $filter);
        if (is_null($value) && isset($_SERVER[$key])) {
            $value = filter_var($_SERVER[$key], $filter);
        }

        return $value;
    }

    public static function session($key, $filter = FILTER_UNSAFE_RAW)
    {
        if (!isset($_SESSION[$key])) {
            return null;
        }

        return filter_var($_SESSION[$key], $filter);
    }
}
