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
 * @version   $Id: Validate.php 2012-03-05 17:03:29 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Validator
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Validator
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Validate
     *
     * @param  array    $chk
     * @param  mixed    $value
     * @return boolean
     */
    public function check($chk, $value)
    {
        $result = false;
        $type = $chk['type'];
        if (preg_match("/:/", $type)) list($type, $stype, $svalue) = explode(':', $type);

        if (is_array($value) && isset($chk['subkey'])) {
            $value = $value[$chk['subkey']];
        }

        switch ($type) {
            case 'mail' :
                $pattern = '^(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                           '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                           '(?:"(?:\\[^\r\n]|[^\\"])*")))\@' .
                           '(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                           '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                           '(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))$';
                if (preg_match("/$pattern/", $value)) $result = true;
                break;
            case 'date' :
                if (is_array($value)) $value = implode('/', $value);
                $value = preg_replace("/-/", "/", $value);
                if (preg_match("/^[0-9]{2,4}\/[0-9]{1,2}\/[0-9]{1,2}$/", $value)) $result = true;
                break;
            case 'tel' :
                if (is_array($value)) $value = implode('', $value);
                $value = mb_convert_kana($value, 'a', 'utf-8');
                $value = preg_replace("/-/", "", $value);
                if (preg_match("/^[0-9]{9,15}$/", $value)) $result = true;
                break;
            case 'zip' :
                if (is_array($value)) $value = implode('', $value);
                $value = mb_convert_kana($value, 'a', 'utf-8');
                $value = preg_replace("/-/", "", $value);
                if (preg_match("/^[0-9]{7}$/", $value)) $result = true;
                break;
            case 'array' :
                if ($stype == 'any') {
                    if (is_array($value) && count($value) > 0) {
                        foreach ($value as $val) {
                            if (!empty($val)) {
                                $result = true;
                                break;
                            }
                        }
                    }
                } else if ($stype == 'all') {
                    $result = true;
                    if (is_array($value) && count($value) > 0) {
                        foreach ($value as $val) {
                            if (empty($val)) {
                                $result = false;
                                break;
                            }
                        }
                    }
                } else {
                    if (! empty($value[$stype])) $result = true;
                }
                break;
            case 'equal' :
                if ($value == $stype) $result = true;
                break;
            case 'retype' :
                eval('
                    if (array_key_exists($stype, $_' . strtoupper($_SERVER['REQUEST_METHOD']) . ')) {
                        $condition = $_' . strtoupper($_SERVER['REQUEST_METHOD']) . '[$stype];
                    }
                ');
                if ($condition == $value) $result = true;
                break;
            case 'if' :
                $result = true;
                $condition = '';
                eval('
                    if (array_key_exists($stype, $_' . strtoupper($_SERVER['REQUEST_METHOD']) . ')) {
                        $condition = $_' . strtoupper($_SERVER['REQUEST_METHOD']) . '[$stype];
                    }
                ');
                if ($condition == $svalue) { 
                    $result = !empty($value);
                }
                break;
            default :
                $result = !empty($value);
        }
        return $result;
    }
}
