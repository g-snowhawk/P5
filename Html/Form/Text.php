<?php
/**
 * This file is part of P5 Framework
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
/**
 * HTML form textbox class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Html_Form_Text
{
    /**
     * Current version
     */
    const VERSION = '1.1.0';

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
