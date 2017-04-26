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
 * Multi language translator.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Lang
{
    /** 
     * Getter method.
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        if (false === property_exists($this, $name) && false === property_exists(__CLASS__, $name)) {
            return false;
        }

        return $this->$key;
    }

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
        if (is_null($package)) {
            $caller = debug_backtrace();
            if (isset($caller[1]['object']) && is_object($caller[1]['object'])) {
                $class = get_class($caller[1]['object']);
            }
            $package = $caller[1]['class'];
        }
        if (empty($locale)) {
            $locale = ($_ENV{'P5_LOCALE'}) ? $_ENV{'P5_LOCALE'} : 'En';
        }
        if (preg_match('/^.*\\\\Plugin\\\\(.+)$/', $package, $match)) {
            $name = $match[1];
            $path = 'plugins/'.str_replace('\\', '/', $name).'/Lang/'.$locale.'.php';
            $package = $package.'\\Lang\\'.$locale;

            return self::words($package, $key);
        }
        while (strpos($package, '\\') !== false) {
            if ($result = self::words($package.'\\Lang\\'.$locale, $key)) {
                return $result;
            }
            $package = preg_replace('/\\\\[^\\\\]+$/', '', $package);
        }
        $package = ($package !== '') ? '\\'.$package.'\\' : '';
        $package = $package.'Lang\\'.$locale;

        return self::words($package, $key);
    }

    /** 
     * Select Words.
     *
     * @param string $package
     * @param string $key
     *
     * @return string
     */
    public static function words($package, $key)
    {
        if (!Auto\Loader::isIncludable($package)) {
            return false;
        }
        if (!class_exists($package, true)) {
            return false;
        }
        $inst = new $package();

        return (property_exists($inst, $key)) ? $inst->$key : '';
    }
}
