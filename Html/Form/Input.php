<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
/**
 * HTML form input class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Html_Form_Input
{
    /**
     * Current version.
     */
    const VERSION = '1.00';

    /**
     * Set default value.
     *
     * @param object $fmObj
     * @param object $html
     * @param object $element
     * @param string $name
     * @param mixed  $value
     * @param mixed  $sec
     */
    public static function setValue($fmObj, $html, $element, $type, $name, $value, $sec)
    {
        if ($type === 'radio') {
            P5_Html_Form_Radio::setValue($fmObj, $html, $element, $value);
        } elseif ($type === 'checkbox') {
            P5_Html_Form_Checkbox::setValue($fmObj, $html, $element, $name, $value, $sec);
        } elseif ($type === 'File') {
            P5_Html_Form_File::setValue($fmObj, $html, $element, $name, $value, $sec);
        } elseif ($type === 'date') {
            $value = (empty($value)) ? null : date('Y-m-d', strtotime($value));
            P5_Html_Form_Text::setValue($fmObj, $html, $element, $name, $value, $sec);
        } elseif ($type === 'datetime') {
            $value = (empty($value)) ? null : date('Y-m-d\TH:i:s', strtotime($value));
            P5_Html_Form_Text::setValue($fmObj, $html, $element, $name, $value, $sec);
        } else {
            P5_Html_Form_Text::setValue($fmObj, $html, $element, $name, $value, $sec);
        }
    }

    /**
     * Change source Input to Preview.
     *
     * @param object $fmObj
     * @param object $html
     * @param string $name
     * @param mixed  $value
     */
    public static function preview($fmObj, $html, $form, $element, $type, $name, $value, $sec)
    {
        if ($type === 'radio') {
            P5_Html_Form_Radio::preview($fmObj, $html, $form, $element, $value);
        } elseif ($type === 'checkbox') {
            P5_Html_Form_Checkbox::preview($fmObj, $html, $form, $element, $name, $value);
        } elseif ($type === 'file') {
            P5_Html_Form_File::preview($fmObj, $html, $form, $element, $type, $name, $value, $sec);
        } else {
            P5_Html_Form_Text::preview($fmObj, $html, $form, $element, $type, $name, $value, $sec);
        }
    }
}
