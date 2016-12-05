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
     * Current version.
     */
    const VERSION = '1.1.0';

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
        $caller = debug_backtrace();
        if (isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (is_null($package)) ? $caller[1]['class'] : $package;
        if (!empty($locale)) {
            $lc = $locale;
        } else {
            $lc = ($_ENV{'P5_LOCALE'}) ? $_ENV{'P5_LOCALE'} : 'En';
        }
        if (preg_match('/^.*_Plugin_(.+)$/', $pkg, $match)) {
            $name = $match[1];
            $path = 'plugins/'.preg_replace('/_/', '/', $name).'/Lang/'.$lc.'.php';
            $package = $pkg.'_Lang_'.$lc;

            return ($result = self::words($key, $package)) ? $result : '';
        }
        while (preg_match('/_/', $pkg)) {
            $package = $pkg.'_Lang_'.$lc;
            if ($result = self::words($key, $package)) {
                return $result;
            }
            $pkg = preg_replace('/_[^_]+$/', '', $pkg);
        }
        $pkg = ($pkg !== '') ? $pkg.'_' : '';
        $package = $pkg.'Lang_'.$lc;

        return ($result = self::words($key, $package)) ? $result : '';
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
    public static function transarray($key, $subkey = null, $package = null)
    {
        $caller = debug_backtrace();
        if (isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (empty($package)) ? $caller[1]['class'] : $package;
        $lc = ($_ENV{'P5_LOCALE'}) ? $_ENV{'P5_LOCALE'} : 'En';
        if (preg_match('/^.*_Plugin_(.+)$/', $pkg, $match)) {
            $name = $match[1];
            $path = 'plugins/'.preg_replace('/_/', '/', $name).'/Lang/'.$lc.'.php';
            $package = $pkg.'_Lang_'.$lc;

            return ($result = self::words($key, $package)) ? $result : '';
        }
        while (preg_match('/_/', $pkg)) {
            $package = $pkg.'_Lang_'.$lc;
            if ($result = self::words($key, $package)) {
                if (is_array($result)) {
                    if (empty($subkey)) {
                        return $result;
                    }

                    return (isset($result[$subkey])) ? $result[$subkey] : null;
                }
            }
            $pkg = preg_replace('/_[^_]+$/', '', $pkg);
        }
        $package = $pkg.'_Lang_'.$lc;
        $result = self::words($key, $package);
        if (is_array($result)) {
            if (empty($subkey)) {
                return $result;
            }

            return (isset($result[$subkey])) ? $result[$subkey] : null;
        }
    }

    /** 
     * Select Words.
     *
     * @param string $key
     * @param string $package
     *
     * @return string
     */
    public static function words($key, $package)
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
}
