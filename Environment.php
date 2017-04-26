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
        return filter_input(INPUT_COOKIE, $key);
    }

    public static function env($key)
    {
        $value = filter_input(INPUT_ENV, $key, FILTER_SANITIZE_STRING);
        if (is_null($value) && isset($_ENV[$key])) {
            $value = filter_var($_ENV[$key], FILTER_SANITIZE_STRING);
        }

        return $value;
    }

    public static function server($key)
    {
        $key = strtoupper($key);
        $value = filter_input(INPUT_SERVER, $key, FILTER_SANITIZE_STRING);
        if (is_null($value) && isset($_SERVER[$key])) {
            $value = filter_var($_SERVER[$key], FILTER_SANITIZE_STRING);
        }

        return $value;
    }
}
