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
 * @version   $Id: Text.php 2012-04-20 15:24:00 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Text
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Set default value
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  object   $element
     * @param  string   $name
     * @param  mixed    $value
     * @param  mixed    $sec
     * @return void
     */
    static public function setValue ($fmObj, $html, $element, $name, $value, $sec)
    {
        if (is_array($value)) {
            if (preg_match("/.+\[([a-zA-Z0-9\-_]+)\]/", $name, $match)) $sec = $match[1];
            if (array_key_exists($sec, $value)) {
                $value = $value[$sec];
            } else {
                return;
            }
        }
        $element->setAttribute('value', $value);
    }

    /**
     * Change source Input to Preview
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  object   $form
     * @param  object   $element
     * @param  string   $type
     * @param  string   $name
     * @param  mixed    $value
     * @param  mixed    $sec
     * @return void
     */
    static public function preview($fmObj, $html, $form, $element, $type, $name, $value, $sec)
    {
        if (is_array($value)) {
            $num = 0;
            // ???
            if (preg_match("/(.+)\[\]/", $name, $match)) {
                if (isset($sec[$match[1]])) {
                    $num = $sec[$match[1]]++;
                } else {
                    $sec[$match[1]] = 1;
                }
            }
            if (preg_match("/.+\[([a-zA-Z0-9\-_]+)\]/", $name, $match)) $num = $match[1];
            $value = $value[$num];
        }
        $element->setAttribute('value', htmlspecialchars($value));
        $element->setAttribute('type', 'hidden');
        if ($type != 'hidden') {
            $src = '<em class="textfield">' . htmlspecialchars($value) . '</em>';
            $fmObj->insertElement($html, $element, $src, 0, 1);
        }
    }
}
