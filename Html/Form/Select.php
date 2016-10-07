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
 * @version   $Id: Select.php 2013-08-27 10:44:40 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html_Form_Select
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    static public function setValue($fmObj, $html, $element, $name, $value) 
    {
        if (is_array($value)) {
            if (preg_match("/.+\[([a-zA-Z0-9_\-]+)\]/", $name, $match)) {
                if (array_key_exists($match[1], $value)) {
                    $value = $value[$match[1]];
                } else {
                    return;
                }
            }
        }

        // options
        $options = $element->getElementsByTagName('option');

        for($j = 0, $max = $options->length; $j < $max; $j++) {
            $val = $value;
            $opt = $options->item($j);
            if (false === $opt->hasAttribute('value')) {
                $optValue = $opt->nodeValue;
            } else {
                $optValue = $opt->getAttribute('value');
            }
            $attvalue = P5_Html::rewindEntityReference($optValue);
            $entities = mb_convert_encoding($attvalue, 'HTML-ENTITIES', mb_internal_encoding());
            $decoders = mb_convert_encoding($attvalue, mb_internal_encoding(), 'HTML-ENTITIES');
            if ((is_array($val) && (in_array($attvalue, $val) || in_array($entities, $val) || in_array($decoders, $val))) ||
                ($attvalue == $val || $entities == $val || $decoders == $val)
            ) {
                $opt->setAttribute('selected', 'selected');
            } else {
                $opt->removeAttribute('selected');
            }
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
     * @param  mixed    $sec
     * @return void
     */
    static public function preview($fmObj, $html, $form, $element, $name, $value, $sec)
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
            if (preg_match("/.+\[([a-zA-Z0-9]+)\]/", $name, $match)) $num = $match[1];
            $value = $value[$num];
        }

        $opts = $element->getElementsByTagName('option');
        $label = $value;
        foreach ($opts as $opt) {
            $oVal = $opt->getAttribute('value');
            if (!empty($oVal) && $oVal == $value) {
                $label = $opt->firstChild->nodeValue;
                break;
            }
        }
        $parent = $element->parentNode;
        // replace
        $src = '<input type="hidden" ' . 'name="' . $name . '" />';
        $node = $fmObj->insertElement($html, $element, $src);
        if (is_array($node)) {
            $node[0]->setAttribute('value', $value);
        } else {
            if(method_exists($node, 'setAttribute')) {
                $node->setAttribute('value', $value);
            }
        }
        //$src = '<em class="textfield">' . $value . '</em>';
        $src = '<em class="textfield">' . $label. '</em>';
        $fmObj->insertElement($html, $element, $src);
        $parent->removeChild($element);
    }
}
