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
 * Environment Class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Environment
{
    /**
     * Object constructor.
     */
    public function __construct() {}

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

    public static function osFromUserAgent($user_agent)
    {
        if (preg_match("/(iPod|iPad|iPhone); .+ OS ([0-9_]+) like Mac OS X; .+$/", $user_agent, $match)) {
            $name = 'iOS';
            $version = strtr($match[2], '_', '.');
        } elseif (preg_match("/Android ([0-9\.]+);/", $user_agent, $match)) {
            $name = 'Android';
            $version = $match[1];
        } elseif (preg_match("/Windows Phone(OS )? ([0-9\.]+);/", $user_agent, $match)) {
            $name = 'Windows Phone';
            $version = $match[2];
        } elseif (preg_match("/Windows NT ([0-9\.]+);/", $user_agent, $match)) {
            $name = 'Windows';
            if ($match[1] < 5.1) {
                $version = 'Legacy';
            } elseif ($match[1] < 6) {
                $version = 'XP';
            } elseif ($match[1] < 6.1) {
                $version = 'Vista';
            } elseif ($match[1] < 6.2) {
                $version = '7';
            } elseif ($match[1] < 6.3) {
                $version = '8';
            } else {
                $version = '10';
            }
        } elseif (preg_match("/Mac OS X ([0-9\._]+)[;\)]/", $user_agent, $match)) {
            $name = 'macOS';
            $version = strtr($match[1], '_', '.');
        } elseif (preg_match("/Linux .+; rv:([0-9\.]+);/", $user_agent, $match)) {
            $name = 'Linux';
            $version = $match[1];
        }
        return array($name, $version);
    }

    public static function browserFromUserAgent($user_agent)
    {
        if (preg_match("/Edge\/([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Microsoft Edge';
            $version = $match[1];
        } elseif (preg_match("/Chrome\/([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Chrome';
            $version = $match[1];
        } elseif (preg_match("/Safari\/([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Safari';
            $version = $match[1];
        } elseif (preg_match("/Firefox\/([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Firefox';
            $version = $match[1];
        } elseif (preg_match("/Opera[ \/]([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Opera';
            $version = $match[1];
        } elseif (preg_match("/Trident\/([0-9\.]+)/", $user_agent, $match)) {
            $name = 'Internet Explorer';
        } elseif (preg_match("/MSIE ([5678][0-9\.]+);/", $user_agent, $match)) {
            $name = 'Internet Explorer';
            if ($match[1] < 6) {
                $version = '9.0';
            } elseif ($match[1] < 7) {
                $version = '10.0';
            } else {
                $version = '11';
            }
        }
        return array($name, $version);
    }
}
