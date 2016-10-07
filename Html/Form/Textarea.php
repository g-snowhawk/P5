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
 * @version   $Id: Textarea.php 2012-03-05 16:06:41 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Textarea
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Setting default value
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  object   $element
     * @param  string   $value
     * @return void
     */
    static public function setValue($fmObj, $html, $element, $value)
    {
        $value = preg_replace("/\r\n/", "\n", $value);
        $node = $element->ownerDocument->createTextNode($value);
        $element->appendChild($node);
    }

    /**
     * Replace preview elements
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  object   $element
     * @param  string   $name
     * @param  string   $value
     * @return void
     */
    static public function preview($fmObj, $html, $element, $name, $value) 
    {
        $parent = $element->parentNode;
        $src = '<input type="hidden" name="' . $name . '" />';
        $node = $fmObj->insertElement($html, $element, $src);
        if (is_array($node)) {
            $node[0]->setAttribute('value', $value);
        } else {
            if(method_exists($node, 'setAttribute')) {
                $node->setAttribute('value', $value);
            }
        }
        $value = preg_replace("/(\r\n|\r|\n)/", "<br />", htmlspecialchars($value));
        $src = '<em class="textbox">' . $value . '</em>';
        $fmObj->insertElement($html, $element, $src);
        $parent->removeChild($element);
    }
}
