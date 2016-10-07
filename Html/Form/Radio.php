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
 * @version   $Id: Radio.php 2012-03-05 15:55:29 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Radio
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
     * @param  mixed    $value
     * @return void
     */
    static public function setValue($fmObj, $html, $element, $value)
    {
        $attvalue = P5_Html::rewindEntityReference($element->getAttribute('value'));
        $entities = mb_convert_encoding($attvalue, 'HTML-ENTITIES', mb_internal_encoding());
        $decoders = mb_convert_encoding($attvalue, mb_internal_encoding(), 'HTML-ENTITIES');
        if ($value == $attvalue || $value == $entities || $value == $decoders) {
            $element->setAttribute('checked', 'checked');
        } else {
            $element->removeAttribute('checked');
        }
    }

    /**
     * Change source Input to Preview
     *
     * @param  object   $fmObj
     * @param  object   $html
     * @param  object   $form
     * @param  object   $element
     * @param  mixed    $value
     * @return void
     */
    static public function preview($fmObj, $html, $form, $element, $value)
    {
        $val = $element->getAttribute('value');
        $name = $element->getAttribute('name');
        $id = $element->getAttribute('id');
        $parent = $html->getElementById($name);

        $node = P5_Xml_Dom::getParentNode($element, 'label');
        $label = '';
        if(!empty($value)) {
            if(!is_object($node)) {
                $labels = $form->getElementsByTagName('label');
                for($l = 0, $max = $labels->length; $l < $max; $l++) {
                    $tmp = $labels->item($l);
                    if (!empty($id) && $id === $tmp->getAttribute('for')) {
                        $node = $tmp;
                        break;
                    }
                }
            }
            if(!is_object($node)) {
                $label = $val;
            } else {
                $children = $node->childNodes;
                foreach($children as $child) {
                    if($child->nodeType === 3) {
                        $label .= $child->nodeValue;
                    }
                }
            }
        }

        if (empty($value) || $val == $value) {
            $str = $label;
            if(!empty($value) && $str === '') {
                $str = $val;
            }
            if(!is_object($parent)) {
                return;
            }
            $src  = '<input type="hidden" name="' . $name . '" value="' . $value . '" />' .
                    '<em class="textfield">' . $str . '</em>';
            $children = $parent->childNodes;
            while ($parent->hasChildNodes()) {
                $parent->removeChild($parent->firstChild);
            }
            $html->appendChild($src, $parent);
        } else {
            if (is_object($parent)) {
                $element->parentNode->removeChild($element);
            }
        }
    }
}
