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
 * Validation class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Validator
{
    /**
     * Current version
     */
    const VERSION = '1.1.0';

    /**
     * Validate
     *
     * @param  array    $chk
     * @param  mixed    $value
     * @return boolean
     */
    public function check($chk, $value)
    {
        $result = false;
        $type = $chk['type'];
        if (preg_match("/:/", $type)) list($type, $stype, $svalue) = explode(':', $type);

        if (is_array($value) && isset($chk['subkey'])) {
            $value = $value[$chk['subkey']];
        }

        switch ($type) {
            case 'mail' :
                $pattern = '^(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                           '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                           '(?:"(?:\\[^\r\n]|[^\\"])*")))\@' .
                           '(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                           '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                           '(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))$';
                if (preg_match("/$pattern/", $value)) $result = true;
                break;
            case 'date' :
                if (is_array($value)) $value = implode('/', $value);
                $value = preg_replace("/-/", "/", $value);
                if (preg_match("/^[0-9]{2,4}\/[0-9]{1,2}\/[0-9]{1,2}$/", $value)) $result = true;
                break;
            case 'tel' :
                if (is_array($value)) $value = implode('', $value);
                $value = mb_convert_kana($value, 'a', 'utf-8');
                $value = preg_replace("/-/", "", $value);
                if (preg_match("/^[0-9]{9,15}$/", $value)) $result = true;
                break;
            case 'zip' :
                if (is_array($value)) $value = implode('', $value);
                $value = mb_convert_kana($value, 'a', 'utf-8');
                $value = preg_replace("/-/", "", $value);
                if (preg_match("/^[0-9]{7}$/", $value)) $result = true;
                break;
            case 'array' :
                if ($stype == 'any') {
                    if (is_array($value) && count($value) > 0) {
                        foreach ($value as $val) {
                            if (!empty($val)) {
                                $result = true;
                                break;
                            }
                        }
                    }
                } else if ($stype == 'all') {
                    $result = true;
                    if (is_array($value) && count($value) > 0) {
                        foreach ($value as $val) {
                            if (empty($val)) {
                                $result = false;
                                break;
                            }
                        }
                    }
                } else {
                    if (! empty($value[$stype])) $result = true;
                }
                break;
            case 'equal' :
                if ($value == $stype) $result = true;
                break;
            case 'retype' :
                eval('
                    if (array_key_exists($stype, $_' . strtoupper($_SERVER['REQUEST_METHOD']) . ')) {
                        $condition = $_' . strtoupper($_SERVER['REQUEST_METHOD']) . '[$stype];
                    }
                ');
                if ($condition == $value) $result = true;
                break;
            case 'if' :
                $result = true;
                $condition = '';
                eval('
                    if (array_key_exists($stype, $_' . strtoupper($_SERVER['REQUEST_METHOD']) . ')) {
                        $condition = $_' . strtoupper($_SERVER['REQUEST_METHOD']) . '[$stype];
                    }
                ');
                if ($condition == $svalue) { 
                    $result = !empty($value);
                }
                break;
            default :
                $result = !empty($value);
        }
        return $result;
    }
}
