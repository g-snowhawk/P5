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
 * Language class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Lang
{
    /** 
     * Translate Language.
     *
     * @param string $key
     * @param mixed  $package
     * @param mixed  $locale
     *
     * @return string
     */
    public static function translate($key, $package = null, $locale = null)
    {
        $locale = self::localeFromEnv($locale);
        $lc = (!empty($locale)) ? $locale : 'En';

        $caller = debug_backtrace();
        if (isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (is_null($package)) ? $caller[1]['class'] : $package;

        if ($pkg === '') {
            return self::words($key, "Lang_$lc");
        }

        $dirs = explode('_', $pkg);
        while ($dirs) {
            if (end($dirs) === 'Plugin') {
                $dir = array_pop($dirs);
            }
            $package = implode('_', $dirs).'_Lang_'.$lc;
            if ($result = self::words($key, $package)) {
                return $result;
            }
            $dir = array_pop($dirs);
        }

        // Top Level Package
        $package = 'Lang_'.$lc;
        if ($result = self::words($key, $package)) {
            return $result;
        }

        return '';
    }

    /** 
     * Get Array Element.
     *
     * @param string $key
     * @param string $subkey
     * @param mixed  $package
     *
     * @return string
     */
    public static function transarray($key, $subkey = null, $package = null, $locale = null)
    {
        $locale = self::localeFromEnv($locale);
        $lc = (!empty($locale)) ? $locale : 'En';

        $caller = debug_backtrace();
        if (isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (is_null($package)) ? $caller[1]['class'] : $package;

        if ($pkg === '') {
            if ($result = self::words($key, "Lang_$lc")) {
                if (is_array($result)) {
                    if (empty($subkey)) {
                        return $result;
                    }

                    return (isset($result[$subkey])) ? $result[$subkey] : null;
                }
            }
        }

        $dirs = explode('_', $pkg);
        while ($dirs) {
            if (end($dirs) === 'Plugin') {
                $dir = array_pop($dirs);
            }
            $package = implode('_', $dirs).'_Lang_'.$lc;
            if ($result = self::words($key, $package)) {
                if (is_array($result)) {
                    if (empty($subkey)) {
                        return $result;
                    }

                    return (isset($result[$subkey])) ? $result[$subkey] : null;
                }
            }
            array_pop($dirs);
        }

        return;
    }

    /** 
     * Select Words.
     *
     * @param string $key
     * @param string $package
     *
     * @see P5_Auto_Loader::isIncludable
     *
     * @return string
     */
    protected static function words($key, $package)
    {
        if (!P5_Auto_Loader::isIncludable($package)) {
            return false;
        }
        if (!class_exists($package, true)) {
            return false;
        }
        $inst = new $package();

        return $inst->$key;
    }

    /** 
     * Getter method.
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        $key = '_'.$name;
        if (false === property_exists($this, $key) &&
            false === property_exists(__CLASS__, $key)
        ) {
            return false;
        }

        return $this->$key;
    }

    private static function localeFromEnv($locale)
    {
        if (is_null($locale)) {
            $locale = (isset($_ENV['P5_LOCALE'])) ? $_ENV['P5_LOCALE'] : getenv('P5_LOCALE');
        }

        return self::UpperCamelCase($locale);
    }

    private static function lowerCamelCase($str)
    {
        return preg_replace_callback(
            '/[-_]([a-z])/',
            function($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($str)
        );
    }

    private static function upperCamelCase($str)
    {
        return ucfirst(self::lowerCamelCase($str));
    }
}
