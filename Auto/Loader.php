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
 * @version   $Id: Loader.php 2012-01-17 09:20:11 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Auto_Loader
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Auto_Loader 
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Register given function as __autoload() implementation
     *
     * @return boolean
     */
    static public function register()
    {
        return spl_autoload_register(array('P5_Auto_Loader', '_autoLoad'));
    }

    /**
     * class auto load
     *
     * @param  string  $className
     * @return mixed
     */
    static private function _autoLoad($className) 
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
            include_once($path);
            return;
        }
        throw new Exception("$path is not found in " . implode(PATH_SEPARATOR, $dirs));
    }

    /**
     * Check class file exists.
     *
     * @param string $className
     * @return mixed
     */
     static public function isIncludable($className) 
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
      * Convert ClassName to Path
      *
      * @param string $name
      * @param bool $fullpath
      * @return string
      */
    static public function convertNameToPath($name, $fullpath = false)
    {
        // Plugin path
        if (false !== strpos($name, 'Plugin_')) {
            $arr = explode('_', $name);
            $index = array_search('Plugin', $arr) + 1;
            array_splice($arr, 0, $index);
            array_push($arr, preg_replace("/^.+\-/", "", end($arr)));
            if (in_array('Lang', $arr)) {
                $index = array_search('Lang', $arr) - 1;
                array_splice($arr, $index, 1);
            }
            array_unshift($arr, 'plugins');
            $name = implode('_', $arr);
        }
        $path  = preg_replace('/_/', DIRECTORY_SEPARATOR, $name) . '.php';

        // Search include path.
        if ($fullpath !== false) {
            $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
            foreach ($dirs as $dir) {
                $file = $dir . DIRECTORY_SEPARATOR . $path;
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
