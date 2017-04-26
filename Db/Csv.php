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
 * See the P5_Text.
 */
require_once 'P5/Text.php';

/**
 * See the P5_File.
 */
require_once 'P5/File.php';

/**
 * Database CSV class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Db_Csv extends P5_Text
{
    /** 
     * Object Constructor.
     *
     * @param string $enc
     */
    public function __construct($enc = null)
    {
    }

    /**
     * Read the CSV file.
     *
     * @param string $file
     *
     * @return mixed
     */
    public function read($file)
    {
        $mirror = array();
        $cnt = 0;
        $su = 0;
        $temp = '';
        if (file_exists($file)) {
            $fp = fopen($file, 'rb');
            while (!feof($fp)) {
                $line = fgets($fp);
                $line = preg_replace("/(\r\n|\r)/", "\n", $line);
                $temp .= $line;
                $su += preg_match_all('/"/', $line, $count);
                if (($su % 2) === 0) {
                    if (!empty($temp)) {
                        $mirror[$cnt++] = $temp;
                    }
                    $su = 0;
                    $temp = '';
                    continue;
                }
            }
            fclose($fp);
        }

        return $mirror;
    }

    /**
     * Parse the CSV branch.
     *
     * @param string $str
     * @param string $sep
     * @param string $quot
     * @param bool   $whitespace
     *
     * @return mixed
     */
    public function parse($str, $sep = ',', $quot = '"', $whitespace = false)
    {
        $result = array();

        $str = trim($str, "\r\n");
        $str = str_replace(array("\r\n", "\r"), "\n", $str);

        // escape character
        //$str = str_replace('\\'.$quot, $quot.$quot, $str);

        $tmp = explode($sep, $str);
        $element = array_shift($tmp);
        foreach ($tmp as $chars) {
            if (substr_count($element, $quot) % 2 === 0) {
                $result[] = preg_replace('/^\"(.*)\"$/s', '$1',
                    str_replace($quot.$quot, $quot, $element), 1);
                $element = $chars;
                continue;
            }
            $element .= $sep.$chars;
        }
        $result[] = preg_replace('/^\"(.*)\"$/s', '$1',
            str_replace($quot.$quot, $quot, $element), 1);

        return $result;
    }

    /**
     * Parse the CSV branch (Legacy function).
     *
     * @param string $str
     * @param string $sep
     * @param string $quot
     * @param bool   $whitespace
     *
     * @return mixed
     */
    public function regacy_parse($str, $sep = ',', $quot = '"', $whitespace = false)
    {
        $result = array();

        // delete whitespace
        $str = preg_replace("/^[\s]+/", '', $str);

        $str = str_replace(array("\r\n", "\r"), "\n", $str);
        $str = rtrim($str);

        preg_match_all('/./m', $str, $chars);

        $count = 0;
        $quote_count = 0;
        $is_quote = false;
        foreach ($chars[0] as $char) {
            if ($char === $quot) {
                ++$quote_count;
                if ($is_quote === false) {
                    $is_quote = true;
                    continue;
                }
            }
            if ($char == $sep && $quote_count % 2 == 0) {
                if (!isset($result[$count])) {
                    $result[$count] = '';
                }
                ++$count;
                $is_quote = false;
                continue;
            }
            if ($whitespace === true) {
                if (preg_match("/[\s]+/", $char) && $quote_count % 2 == 0) {
                    continue;
                }
            }
            if (!isset($result[$count])) {
                $result[$count] = '';
            }
            if ($char === $quot && $quote_count % 2 == 0) {
                $is_quote = false;
                continue;
            }
            $result[$count] .= $char;
            $is_quote = false;
        }

        return $result;
    }

    /**
     * Data pickup.
     *
     * @param string $fields
     * @param mixed  $data
     * @param string $pattern
     *
     * @return mixed
     */
    public function pickup($fields, $data, $pattern = null)
    {
        if (is_array($data)) {
            $data = array_values(preg_grep($pattern, $data));
            $$pickupData = $data[0];
        } else {
            $$pickupData = $data;
        }
        $field = self::parse(trim($fields));
        $unit = self::parse(trim($$pickupData));
        foreach ($field as $key => $value) {
            $result[$value] = $unit[$key];
        }

        return $result;
    }

    /**
     * Make the CSV branch.
     *
     * @param array  $cols
     * @param string $sep
     * @param string $quot
     * @param bool   $whitespace
     * @param bool   $force
     *
     * @return string
     */
    public function makeBranch(array $cols, $sep = ',', $quot = '"', $whitespace = false, $force = false)
    {
        $tmp = array();
        foreach ($cols as $col) {
            if (is_null($col)) {
                //array_push($tmp, '');
                $tmp[] = '';
                continue;
            }
            //array_push($tmp, $this->escape($col, $sep, $quot, $whitespace, $force));
            $tmp[] = $this->escape($col, $sep, $quot, $whitespace, $force);
        }

        return implode($sep, $tmp);
    }

    /**
     * Escape for CSV string.
     *
     * @param string $str
     * @param string $sep
     * @param string $quot
     * @param bool   $whitespace
     * @param bool   $force
     *
     * @return string
     */
    public function escape($str, $sep = ',', $quot = '"', $whitespace = false, $force = false)
    {
        if (isset($str)) {
            //$pattern = "/\\".$quot."/";
            //$str = preg_replace($pattern, $quot.$quot, $str);
            $str = str_replace($quot, $quot.$quot, $str);
            $ws = ($whitespace === true) ? ' ' : '';
            $pattern = '/[\\'.$quot.$sep."\r\n".$ws.']+/';
            if (preg_match($pattern, $str) ||
                strpos($str, '0') === 0 ||
                $force === true
            ) {
                $str = $quot.$str.$quot;
            }
        }

        return (is_null($str) && $force) ? '' : $str;
    }

    /**
     * Strip the Quotation.
     *
     * @param string $str
     * @param string $quot
     *
     * @return string
     */
    public function stripQuote($str, $quot = '"')
    {
        $pattern = '/^'.preg_quote($quot, '/').'(.+)'.preg_quote($quot, '/').'$/s';
        if (preg_match($pattern, $str, $match)) {
            return $match[1];
        }

        return $str;
    }
}
