<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */

namespace P5\Xml\Html;

/**
 * HTML form radio button class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class Radio
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Set default value.
     *
     * @param object $fmObj
     * @param object $html
     * @param object $element
     * @param mixed  $value
     */
    public static function setValue($fmObj, $html, $element, $value)
    {
        $attvalue = \P5\Xml\Html::rewindEntityReference($element->getAttribute('value'));
        $entities = mb_convert_encoding($attvalue, 'HTML-ENTITIES', mb_internal_encoding());
        $decoders = mb_convert_encoding($attvalue, mb_internal_encoding(), 'HTML-ENTITIES');
        if ($value == $attvalue || $value == $entities || $value == $decoders) {
            $element->setAttribute('checked', 'checked');
        } else {
            $element->removeAttribute('checked');
        }
    }

    /**
     * Change source Input to Preview.
     *
     * @param object $fmObj
     * @param object $html
     * @param object $form
     * @param object $element
     * @param mixed  $value
     */
    public static function preview($fmObj, $html, $form, $element, $value)
    {
        $val = $element->getAttribute('value');
        $name = $element->getAttribute('name');
        $id = $element->getAttribute('id');
        $parent = $html->getElementById($name);

        $node = P5_Xml_Dom::getParentNode($element, 'label');
        $label = '';
        if (!empty($value)) {
            if (!is_object($node)) {
                $labels = $form->getElementsByTagName('label');
                for ($l = 0, $max = $labels->length; $l < $max; ++$l) {
                    $tmp = $labels->item($l);
                    if (!empty($id) && $id === $tmp->getAttribute('for')) {
                        $node = $tmp;
                        break;
                    }
                }
            }
            if (!is_object($node)) {
                $label = $val;
            } else {
                $children = $node->childNodes;
                foreach ($children as $child) {
                    if ($child->nodeType === 3) {
                        $label .= $child->nodeValue;
                    }
                }
            }
        }

        if (empty($value) || $val == $value) {
            $str = $label;
            if (!empty($value) && $str === '') {
                $str = $val;
            }
            if (!is_object($parent)) {
                return;
            }
            $src = '<input type="hidden" name="'.$name.'" value="'.$value.'" />'.
                    '<em class="textfield">'.$str.'</em>';
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
