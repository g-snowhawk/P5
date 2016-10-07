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
 * @copyright Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Array.php 2013-04-11 10:53:35 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Array
 * @copyright  Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Array
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Swap elements
     *
     * @param string $needle
     * @param array $array
     * @param bool $push
     * @return mixed
     */
    static public function swap($needle, array $array, $push = false)
    {
        if ($index = array_search($needle, $array)) {
            $element = $array[$index];
            unset($array[$index]);
            if ($push) array_push($array, $element);
            else array_unshift($array, $element);
            return array_values($array);
        }
        return $array;
    }

    /**
     * Hash to Array
     *
     * @param array $array
     * @return array
     */
    static public function values(array $array)
    {
        $ret = array();
        foreach ($array as $key => $value) {
            array_push($ret, $value);
        }
        return $ret;
    }

    /**
     * Check hash
     *
     * @param array $array
     * @return bool
     */
    static public function is_hash(&$array) 
    {
        if (!is_array($array)) return false;
        foreach($array as $key => $value) {
            if (!is_int($key)) return true;
        }
        return false;
    }

    /**
     * Repeat same value
     *
     * @param mixed $value
     * @param int $repeat
     * @return array
     */
    static public function createRepeater($value, $repeat)
    {
        $arr = array();
        for($i = 0; $i < $repeat; $i++) {
            array_push($arr, $value);
        }
        return $arr;
    }
}
