<?php
/**
 * PlusFive Frameworks
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Lang.php 2012-01-17 09:48:28 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Lang
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Lang
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /** 
     * Translate Language
     *
     * @param  string   $key
     * @param  mixed    $package
     * @param  mixed    $locale
     * @return string
     */
    static public function translate($key, $package = null, $locale = null)
    {
        $caller = debug_backtrace();
        if(isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (is_null($package)) ? $caller[1]['class'] : $package;
        if(!empty($locale)) {
            $lc = $locale;
        } else {
            $lc = ($_ENV{'P5_LOCALE'}) ? $_ENV{'P5_LOCALE'} : 'En';
        }
        if(preg_match('/^.*_Plugin_(.+)$/', $pkg, $match)) {
            $name = $match[1];
            $path = 'plugins/' . preg_replace("/_/", '/', $name) . '/Lang/' . $lc . '.php';
            $package = $pkg . '_Lang_' . $lc;
            return ($result = self::words($key, $package)) ? $result : '';
        }
        while(preg_match('/_/', $pkg)) {
            $package = $pkg . '_Lang_' . $lc;
            if($result = self::words($key, $package)) {
                return $result;
            }
            $pkg = preg_replace('/_[^_]+$/', '', $pkg);
        }
        $pkg = ($pkg !== '') ? $pkg . '_' : '';
        $package = $pkg . 'Lang_' . $lc;
        return ($result = self::words($key, $package)) ? $result : '';
    }

    /** 
     * Get Array Element
     *
     * @param  string   $key
     * @param  string   $subkey
     * @param  mixed    $package
     * @return string
     */
    static public function transarray($key, $subkey = null, $package = null)
    {
        $caller = debug_backtrace();
        if(isset($caller[1]['object']) && is_object($caller[1]['object'])) {
            $class = get_class($caller[1]['object']);
        }
        $pkg = (empty($package)) ? $caller[1]['class'] : $package;
        $lc = ($_ENV{'P5_LOCALE'}) ? $_ENV{'P5_LOCALE'} : 'En';
        if(preg_match('/^.*_Plugin_(.+)$/', $pkg, $match)) {
            $name = $match[1];
            $path = 'plugins/' . preg_replace("/_/", '/', $name) . '/Lang/' . $lc . '.php';
            $package = $pkg . '_Lang_' . $lc;
            return ($result = self::words($key, $package)) ? $result : '';
        }
        while(preg_match('/_/', $pkg)) {
            $package = $pkg . '_Lang_' . $lc;
            if($result = self::words($key, $package)) {
                if(is_array($result)) {
                    if(empty($subkey)) {
                        return $result;
                    }
                    return (isset($result[$subkey])) ? $result[$subkey] : null;
                }
            }
            $pkg = preg_replace('/_[^_]+$/', '', $pkg);
        }
        $package = $pkg . '_Lang_' . $lc;
        $result = self::words($key, $package);
        if(is_array($result)) {
            if(empty($subkey)) {
                return $result;
            }
            return (isset($result[$subkey])) ? $result[$subkey] : null;
        }
    }

    /** 
     * Select Words
     *
     * @param  string   $key
     * @param  string   $package
     * @return string
     */
    static public function words($key, $package)
    {
        if(!P5_Auto_Loader::isIncludable($package)) {
            return false;
        }
        if(!class_exists($package, true)) {
            return false;
        }
        $inst = new $package();
        return $inst->$key;
    }

    /** 
     * Getter method
     *
     * @param  string   $name
     * @return string
     */
    public function __get($name) {
        $key = '_' . $name;
        if(false === property_exists($this, $key) &&
            false === property_exists(__CLASS__, $key)
        ) {
            return false;
        }
        return $this->$key;
    }
}
