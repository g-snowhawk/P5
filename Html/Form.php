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
 * HTML form class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Html_Form
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    private $post = array();
    private $get = array();

    /**
     * Object constructer.
     *
     * @param bool $notspace
     */
    public function __construct($notspace = false)
    {
        foreach ($_GET as $key => $value) {
            $this->get[$key] = P5_Text::convert(filter_input(INPUT_GET, $key));
            if ($notspace) {
                $this->get[$key] = $this->notWhitespaceOnly($this->get[$key]);
            }
        }
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $this->post[$key] = P5_Text::convert(filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY));
                if ($notspace) {
                    foreach ($this->post[$key] as $n => $value) {
                        $this->post[$key][$n] = $this->notWhitespaceOnly($value);
                    }
                }
                continue;
            }
            $this->post[$key] = P5_Text::convert(filter_input(INPUT_POST, $key));
            if ($notspace) {
                $this->post[$key] = $this->notWhitespaceOnly($this->post[$key]);
            }
        }
    }

    private function notWhitespaceOnly($str)
    {
        $tmp = mb_convert_kana($str, 's');

        return (preg_match('/^[\s]+$/s', $tmp)) ? '' : $str;
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
                $method = strtolower(P5_Environment::server('REQUEST_METHOD'));
                return (empty($method)) ? 'get' : $method;
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
     * Alias to param.
     *
     * @param mixed $name
     * @param mixed $value
     * @param bool  $kill
     *
     * @return mixed
     */
    public function params($name = null, $value = null, $kill = false)
    {
        return self::param($name, $value, $kill);
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
     * Getting form action.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     *
     * @return mixed
     */
    public function getAction(P5_Html_Source $html, $id)
    {
        $form = $html->getElementById($id);
        if (is_object($form)) {
            return $form->getAttribute('action');
        }

        return;
    }

    /**
     * Setting form action.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param string         $action
     * @param bool           $force
     */
    public function setAction(P5_Html_Source $html, $id, $action, $force = false)
    {
        $form = $html->getElementById($id);
        if (is_object($form)) {
            $def = $form->getAttribute('action');
            if (!empty($def) && $force == false) {
                return;
            }
            if ($def != $action) {
                $form->setAttribute('action', $action);
            }
        }
    }

    /**
     * Setting form enctype.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param string         $enctype
     */
    public function setEnctype(P5_Html_Source $html, $id, $enctype = 'application/x-www-form-urlencoded')
    {
        $form = $html->getElementById($id);
        if (is_object($form)) {
            $inputs = $form->getElementsByTagName('input');
            foreach ($inputs as $input) {
                if (strtolower($input->getAttribute('type')) == 'file') {
                    $enctype = 'multipart/form-data';
                    break;
                }
            }
            $def = $form->getAttribute('enctype');
            if ($def != $enctype) {
                $form->setAttribute('enctype', $enctype);
            }
        }
    }

    /**
     * Setting form method.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param string         $method
     */
    public function setMethod(P5_Html_Source $html, $id, $method)
    {
        $form = $html->getElementById($id);
        if (is_object($form)) {
            $def = $form->getAttribute('method');
            if (empty($def)) {
                if ($def != $method) {
                    $form->setAttribute('method', $method);
                }
            }
        }
    }

    /**
     * default form data.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param mixed          $force
     * @param mixed          $skip
     */
    public function setDefaultValue(P5_Html_Source $html, $id, $force = null, $skip = null)
    {
        if (is_null($skip)) {
            $skip = array();
        }
        $sec = 0;
        $prevName = '';
        $form = $html->getElementById($id);
        if (!is_object($form)) {
            return;
        }

        // input 
        $nodelist = $form->getElementsByTagName('input');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Type
            $type = $element->getAttribute('type');
            if ($type == 'button' || $type == 'submit' || $type == 'reset') {
                continue;
            }

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = $this->getValue($name, $force);
            if ($prevName != $name) {
                $sec = 0;
            } else {
                ++$sec;
            }

            P5_Html_Form_Input::setValue($this, $html, $element, $type, $name, $value, $sec);

            $prevName = $name;
        }

        // textarea
        $nodelist = $form->getElementsByTagName('textarea');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            # Getting Value
            $value = $this->getValue($name, $force);

            P5_Html_Form_Textarea::setValue($this, $html, $element, $value, $sec);
        }

        // select
        $nodelist = $form->getElementsByTagName('select');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = $this->getValue($name, $force);

            P5_Html_Form_Select::setValue($this, $html, $element, $name, $value, $sec);
        }
    }

    /**
     * Replace input to preview.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param mixed          $force
     * @param mixed          $skip
     */
    public function replacePreview(P5_Html_Source $html, $id, $force = null, $skip = null)
    {
        if (is_null($skip)) {
            $skip = array();
        }

        $sec = array();
        $form = $html->getElementById($id);
        if (!is_object($form)) {
            return;
        }

        $checkboxes = array();
        $radiobutton = array();

        // input
        $nodelist = $form->getElementsByTagName('input');
        $len = $nodelist->length - 1;
        for ($i = $len; $i >= 0; --$i) {
            $element = $nodelist->item($i);
            // Getting Type
            $type = $element->getAttribute('type');
            if ($type === 'button' || $type === 'submit' || $type === 'reset') {
                continue;
            }

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = $this->getValue($name, $force, true);

            // Checkbox
            if ($type === 'checkbox') {
                $name = preg_replace("/\[.*\]$/", '', $name);
                if (!isset($checkboxes[$name])) {
                    $checkboxes[$name]['value'] = $value;
                    $checkboxes[$name]['element'] = $element;
                }
                continue;
            }

            // Radio Button
            if ($type === 'radio') {
                if (!isset($radiobutton[$name]) ||
                   $value === $element->getAttribute('value')
                ) {
                    $radiobutton[$name]['value'] = $value;
                    $radiobutton[$name]['element'] = $element;
                }
                continue;
            }

            P5_Html_Form_Input::preview($this, $html, $form, $element, $type, $name, $value, $sec);
        }

        // Checkbox
        foreach ($checkboxes as $name => $unit) {
            P5_Html_Form_Input::preview($this, $html, $form, $unit['element'], 'checkbox', $name, $unit['value'], $sec);
        }

        // Radio Button
        foreach ($radiobutton as $name => $unit) {
            P5_Html_Form_Input::preview($this, $html, $form, $unit['element'], 'radio', $name, $unit['value'], $sec);
        }

        // textarea
        $nodelist = $form->getElementsByTagName('textarea');
        for ($i = $nodelist->length - 1; $i >= 0; --$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = $this->getValue($name, $force);

            P5_Html_Form_Textarea::preview($this, $html, $element, $name, $value, $sec);
        }

        // select
        $nodelist = $form->getElementsByTagName('select');
        for ($i = $nodelist->length - 1; $i >= 0; --$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = $this->getValue($name, $force);

            P5_Html_Form_Select::preview($this, $html, $form, $element, $name, $value, $sec);
        }
    }

    /**
     * get form data.
     *
     * @param string $name
     * @param mixed  $force
     *
     * @return mixed
     */
    public function getValue($name, $force = null)
    {
        $data = (strtolower($_SERVER['REQUEST_METHOD']) == 'post') ? 'POST' : 'GET';
        if (!empty($force)) {
            $data = (strtolower($force) == 'post') ? 'POST' : 'GET';
        }
        if (preg_match("/^(.+)\[(.*)\]$/", $name, $match)) {
            $name = $match[1];
            $key = $match[2];
        }
        // Value
        $value = $this->$data($name);

        return $value;
    }

    /**
     * insert hidden element.
     *
     * @param P5_Html_Source $html
     * @param string         $id
     * @param string         $name
     * @param mixed          $value
     * @param string         $br
     */
    public function insertHidden(P5_Html_Source $html, $id, $name, $value, $br = '')
    {
        $element = (is_object($id)) ? $id : $html->getElementById($id);
        if (!is_object($element)) {
            return;
        }

        $inputs = $element->getElementsByTagName('input');

        foreach ($inputs as $input) {
            if ($input->getAttribute('name') === $name) {
                $exists = 1;
                break;
            }
        }
        if (isset($exists)) {
            $input->setAttribute('value', $value);
        } else {
            $src = $br.'<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            $newElement = $this->insertElement($html, $element, $src, 1);
            if (method_exists($newElement, 'item')) {
                foreach ($newElement as $node) {
                    $node->setAttribute('value', $value);
                }
            } else {
                if (method_exists($newElement, 'setAttribute')) {
                    $newElement->setAttribute('value', $value);
                }
            }
        }
    }

    /**
     * Insert form elements.
     *
     * @param P5_Html_Source $html
     * @param object         $element
     * @param mixed          $src
     * @param mixed          $noparent
     * @param mixed          $isBefore
     *
     * @return mixed
     */
    public function insertElement(P5_Html_Source $html, $element, $src, $noparent = null, $isBefore = null)
    {
        $parent = ($noparent) ? $element : $element->parentNode;
        $node = $html->importChild($src);
        if (is_array($node) || method_exists($node, 'item')) {
            foreach ($node as $child) {
                if ($isBefore) {
                    $parent->insertBefore($child, $element);
                } else {
                    $parent->appendChild($child);
                }
            }
        } else {
            if ($isBefore) {
                $parent->insertBefore($node, $element);
            } else {
                $parent->appendChild($node);
            }
        }

        return $node;
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

        $prefs = P5_Html_Form_Lang_Ja::PREFS();

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
