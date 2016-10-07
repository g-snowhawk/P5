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
 * @version   $Id: Checkbox.php 2013-08-27 10:50:15 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Checkbox 
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
        //$pattern = $element->getAttribute('value');
        $attvalue = P5_Html::rewindEntityReference($element->getAttribute('value'));
        $entities = mb_convert_encoding($attvalue, 'HTML-ENTITIES', mb_internal_encoding());
        $decoders = mb_convert_encoding($attvalue, mb_internal_encoding(), 'HTML-ENTITIES');
        if (P5_Array::is_hash($value)) {
            if (preg_match("/.+\[([a-zA-Z0-9\-_]+)\]/", $name, $match)) {
                $sec = $match[1];
                $value = (isset($value[$sec])) ? $value[$sec] : NULL;
            }
        }
        if ((is_array($value) && (in_array($attvalue, $value) || in_array($entities, $value) || in_array($decoders, $value))) ||
            ($attvalue == $value || $entities == $value || $decoders == $value)
        ) {
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
     * @param  string   $name
     * @param  mixed    $value
     * @return void
     */
    static public function preview($fmObj, $html, $form, $element, $name, $value)
    {
        $name = preg_replace("/\[.*\]$/", '', $name);
        $container = $html->getElementById($name);
        $src = '';
        if (!is_array($value)) $container = $container->parentNode;
        if (is_object($container)) {
            $separator = $container->getAttribute('separator');
            if (empty($separator)) $separator = '<br />';
            $src = '<' . $container->nodeName . ' id="' . $name . '">';
            if (is_array($value)) {
                $src .= '<em class="textfield">' . implode($separator, $value) . '</em>';
                foreach ($value as $val) {
                    $src .= '<input type="hidden"' .
                            ' name="' . $name . '[]"' .
                            ' value="' . $val . '"' .
                            '/>';
                }
            } else {

                $label = '';
                $node = P5_Xml_Dom::getParentNode($element, 'label');
                if(!empty($value)) {
                    if(!is_object($node)) {
                        $id = $element->getAttribute('id');
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

                $src .= '<em class="textfield">' . $label . '</em>';
                $src .= '<input type="hidden"' .
                        ' name="' . $name . '"' .
                        ' value="' . $value . '"' .
                        '/>';
            }
            $src .= '</' . $container->nodeName . '>';
            if (is_array($value)) {
                $fmObj->insertElement($html, $container, $src, 0, 1);
                $container->parentNode->removeChild($container);
            } else {
                $html->replaceChild($src, $container);
            }
        }
    }
}
