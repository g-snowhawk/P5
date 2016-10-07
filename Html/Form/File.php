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
 * @version   $Id: File.php 2012-07-31 10:35:39 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_File
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
    static public function setValue($fmObj, $html, $element, $name, $value, $sec) 
    {
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
        $name = preg_replace("/\[.*\]$/", '', $name);
        $val = '';

        if (empty($value)) {
            $value = $fmObj->FILES($name);
            if (!empty($value['name'])) {
                $destination = dirname($value['tmp_name']) . '/' . $value['name'];
                move_uploaded_file($value['tmp_name'], $destination);
                $value['tmp_name'] = $destination;
                $val = $value['name'];
                $fmObj->POST($name, serialize($value));
            }
        }

        $element->setAttribute('type', 'hidden');
        $element->setAttribute('name', 's1_attachment[' . $name . ']');
        if ($type != 'hidden') {
            $src = '<em class="textfield">' . htmlspecialchars($val) . '</em>';
            $fmObj->insertElement($html, $element, $src, 0, 1);
        }
    }
}
