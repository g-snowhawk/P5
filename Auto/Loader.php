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
 * Auto loading class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Auto_Loader
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Register given function as __autoload() implementation.
     *
     * @return bool
     */
    public static function register()
    {
        return spl_autoload_register(array('P5_Auto_Loader', '_autoLoad'));
    }

    /**
     * class auto load.
     *
     * @param string $className
     *
     * @return mixed
     */
    private static function _autoLoad($className)
    {
        if (empty($className)) {
            return;
        }
        if (class_exists($className)) {
            return;
        }
        if (false === self::isIncludable($className)) {
            return;
        }
        if (false !== $path = self::convertNameToPath($className, true)) {
            include_once $path;

            return;
        }
        throw new Exception("$path is not found in ".implode(PATH_SEPARATOR, $dirs));
    }

     /**
      * Check class file exists.
      *
      * @param string $className
      *
      * @return mixed
      */
     public static function isIncludable($className)
     {
         if (empty($className)) {
             return false;
         }
         if (false === $path = self::convertNameToPath($className, true)) {
             return false;
         }

         return true;
     }

    /**
     * Convert ClassName to Path.
     *
     * @param string $name
     * @param bool   $fullpath
     *
     * @return string
     */
    public static function convertNameToPath($name, $fullpath = false)
    {
        // Plugin path
        if (false !== strpos($name, 'Plugin_')) {
            $arr = explode('_', $name);
            $index = array_search('Plugin', $arr) + 1;
            array_splice($arr, 0, $index);
            array_push($arr, preg_replace("/^.+\-/", '', end($arr)));
            if (in_array('Lang', $arr)) {
                $index = array_search('Lang', $arr) - 1;
                array_splice($arr, $index, 1);
            }
            array_unshift($arr, 'plugins');
            $name = implode('_', $arr);
        }
        $path = preg_replace('/[_\\\]/', DIRECTORY_SEPARATOR, $name).'.php';

        // Search include path.
        if ($fullpath !== false) {
            $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
            foreach ($dirs as $dir) {
                $file = $dir.DIRECTORY_SEPARATOR.$path;
                if (file_exists($file)) {
                    return $file;
                }
                $file = preg_replace('/\.php$/', '.class.php', $file);
                if (file_exists($file)) {
                    return $file;
                }
            }

            return false;
        }

        return $path;
    }
}
