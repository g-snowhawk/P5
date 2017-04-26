<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5\Html;

/**
 * Methods for form management.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Form
{
    private $post = [];
    private $get = [];

    /**
     * Object constructer.
     */
    public function __construct()
    {
        foreach ($_GET as $key => $value) {
            $this->get[$key] = \P5\Text::convert(filter_input(INPUT_GET, $key));
        }
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $this->post[$key] = \P5\Text::convert(filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY));
                continue;
            }
            $this->post[$key] = \P5\Text::convert(filter_input(INPUT_POST, $key));
        }
    }

    /**
     * Getter Method.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        switch ($key) {
            case 'method' :
                return strtolower(\P5\Environment::server('REQUEST_METHOD'));
                break;
        }
    }

    /**
     * Super Globals.
     *
     * @param mixed $name
     * @param mixed $value
     * @param bool  $kill
     *
     * @return mixed
     */
    public function post($name = null, $value = null, $kill = false)
    {
        if (is_null($name)) {
            return $this->post;
        }
        $name = preg_replace('/\[.*$/', '', $name);
        if ($kill === true) {
            unset($this->post[$name]);

            return;
        }
        if (isset($value)) {
            $this->post[$name] = $value;
        }

        return (array_key_exists($name, $this->post)) ? $this->post[$name] : null;
    }

    /**
     * Super Globals.
     *
     * @param mixed $name
     * @param mixed $value
     * @param bool  $kill
     *
     * @return mixed
     */
    public function get($name = null, $value = null, $kill = false)
    {
        if (is_null($name)) {
            return $this->get;
        }
        $name = preg_replace('/\[.*$/', '', $name);
        if ($kill === true) {
            unset($this->get[$name]);

            return;
        }
        if (isset($value)) {
            $this->get[$name] = $value;
        }

        return (array_key_exists($name, $this->get)) ? $this->get[$name] : null;
    }

    /**
     * Super Globals.
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function files($name = null)
    {
        if (empty($name)) {
            return $_FILES;
        }

        return (array_key_exists($name, $_FILES)) ? $_FILES[$name] : null;
    }

    /**
     * form data.
     *
     * @param mixed $name
     * @param mixed $value
     * @param bool  $kill
     *
     * @return mixed
     */
    public function param($name = null, $value = null, $kill = false)
    {
        $method = $this->method;
        if (false === method_exists($this, $method)) {
            return;
        }

        return $this->$method($name, $value, $kill);
    }

    /**
     * make Pref selector.
     *
     * @param string $name
     * @param string $selected
     * @param bool   $optonly
     *
     * @return string
     */
    public static function prefSelector($name, $selected, $optonly = 0, $label = '')
    {
        $src = '';
        if (!$optonly) {
            $src .= '<select name="'.$name.'" id="'.$name.'">'."\n";
        }

        $prefs = Form\Lang\Ja::PREFS();

        if (!empty($label)) {
            $src .= '<option value="">'.$label.'</option>';
        }

        foreach ($prefs as $pref) {
            $src .= '<option value="'.$pref.'"';
            if ($pref == $selected) {
                $src .= ' selected="selected"';
            }
            $src .= '>'.$pref.'</option>'."\n";
        }
        if (!$optonly) {
            $src .= '</select>'."\n";
        }

        return $src;
    }

    /**
     * key is exists.
     *
     * @param string $key
     * @param string $method
     *
     * @return bool
     */
    public function keyExists($key, $method = 'post')
    {
        if (strtolower($method) != 'post') {
            return array_key_exists($key, $_GET);
        }

        return array_key_exists($key, $_POST);
    }
}
