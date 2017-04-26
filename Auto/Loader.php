<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5\Auto;

/**
 * Auto loader class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Loader
{
    /**
     * Default file extension.
     *
     * @var string
     */
    private static $fileExtension = '.php';

    /**
     * Include path.
     *
     * @var mixed
     */
    private static $includePath = null;

    /**
     * Default namespace.
     *
     * @var mixed
     */
    private static $namespace = null;

    /**
     * Namespace separator.
     *
     * @var string
     */
    private static $namespaceSeparator = '\\';

    /**
     * Set the namespace.
     *
     * @param string $ns
     */
    public static function setNameSpace($ns)
    {
        self::$namespace = $ns;
    }

    /**
     * Register given function as __autoload() implementation.
     *
     * @return bool
     */
    public static function register()
    {
        return spl_autoload_register('self::autoLoad');
    }

    /**
     * auto loader.
     *
     * @param string $className
     *
     * @return mixed
     */
    private static function autoLoad($className)
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
        if ($path = self::convertNameToPath($className, true)) {
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
        $path = '';
        $namespace = '';
        if (false !== ($lastNsPos = strripos($name, self::$namespaceSeparator))) {
            $namespace = substr($name, 0, $lastNsPos);
            $name = substr($name, $lastNsPos + 1);
            $path = str_replace(self::$namespaceSeparator, DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
        }
        $path .= str_replace('_', DIRECTORY_SEPARATOR, $name).self::$fileExtension;

        // Search include path.
        if ($fullpath !== false) {
            $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
            foreach ($dirs as $dir) {
                $file = $dir.DIRECTORY_SEPARATOR.$path;
                if (realpath($file)) {
                    return $file;
                }
                $file = preg_replace('/\.php$/', '.class.php', $file);
                if (realpath($file)) {
                    return $file;
                }
            }

            return false;
        }

        return $path;
    }
}
