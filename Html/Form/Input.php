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
 * @version   $Id: Input.php 2012-03-05 17:10:40 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Input
{
    /**
     * Current version
     */
    const VERSION = "1.00";

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
    static public function setValue($fmObj, $html, $element, $type, $name, $value, $sec) 
    {
        if ($type == 'radio') {
            P5_Html_Form_Radio::setValue($fmObj, $html, $element, $value);
        } elseif ($type == 'checkbox') {
            P5_Html_Form_Checkbox::setValue($fmObj, $html, $element, $name, $value, $sec);
        } elseif ($type == 'File') {
            P5_Html_Form_File::setValue($fmObj, $html, $element, $name, $value, $sec);
        } else {
            P5_Html_Form_Text::setValue($fmObj, $html, $element, $name, $value, $sec);
        }
    }

    /**
     * Change source Input to Preview
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  string   $name
     * @param  mixed    $value
     * @return void
     */
    static public function preview($fmObj, $html, $form, $element, $type, $name, $value, $sec)
    {
        if ($type == 'radio') {
            P5_Html_Form_Radio::preview($fmObj, $html, $form, $element, $value);
        } elseif ($type == 'checkbox') {
            P5_Html_Form_Checkbox::preview($fmObj, $html, $form, $element, $name, $value);
        } elseif ($type == 'file') {
            P5_Html_Form_File::preview($fmObj, $html, $form, $element, $type, $name, $value, $sec);
        } else {
            P5_Html_Form_Text::preview($fmObj, $html, $form, $element, $type, $name, $value, $sec);
        }
    }
}
