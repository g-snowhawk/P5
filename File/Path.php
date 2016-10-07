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
 * @version   $Id: Path.php 2012-03-05 15:02:39 tak@ $
 */

/**
 * @category   P5
 * @package    P5_File
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_File_Path extends P5_File
{
    /**
     * Class Version Number.
     */
    const VERSION = '1.0.0';

    /**
     * Create new direstory.
     * 
     * @param  string   $path
     * @param  mixed    $mode
     * @return boolean
     */
    static public function mkpath($path, $mode = 0777)
    {
        $path = parent::replaceDirectorySeparator($path);
        if (file_exists($path)) return is_dir($path);
        $base = rtrim($path, DIRECTORY_SEPARATOR);
        $dirs = array();
        while (!file_exists($base)) {
            array_unshift($dirs, basename($base));
            $prev = $base;
            $base = dirname($base);
            if ($prev == $base) return false;
            if (file_exists($base) && !is_writable($base)) return false;
        }
        if ($base == DIRECTORY_SEPARATOR) $base = '';
        foreach ($dirs as $dir) {
            $base .= DIRECTORY_SEPARATOR . $dir;
            //if (false === mkdir($base, $mode)) return false;
            try {
                @mkdir($base, $mode);
            } catch(ErrorException $e) {
                if (preg_match("/File exists/", $e->getMessage())) continue;
            }
        }
        return is_dir($path);
    }
}
