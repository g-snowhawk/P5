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
 * Date class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Date
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Timestamp to string.
     *
     * @param string $format
     * @param number $timestamp
     *
     * @return string
     */
    public static function format($format, $timestamp = null)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }

        $sec = date('s', $timestamp);
        $min = date('i', $timestamp);
        $hour = date('H', $timestamp);
        $mday = date('j', $timestamp);
        $mon = date('n', $timestamp);
        $year = date('Y', $timestamp);

        // Keyword
        switch (strtolower($format)) {
            case 'sql' :
                return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $mon, $mday, $hour, $min, $sec);
            case 'gmt' :
                return gmdate('D, d M Y H:i:s T', $timestamp);
            case 'utc' :
                return sprintf('%04d-%02d-%02dT%02d:%02d:%02d+09:00', $year, $mon, $mday, $hour, $min, $sec);
            case 'long' :
                return sprintf('%04d/%02d/%02d %02d:%02d:%02d', $year, $mon, $mday, $hour, $min, $sec);
            case 'short' :
                return sprintf('%04d/%02d/%02d', $year, $mon, $mday);
        }

        return date($format, $timestamp);
    }

    /**
     * Check expire.
     *
     * @param string $date
     * @param number $expire
     * @param string $ymd
     *
     * @return bool
     */
    public static function expire($date, $expire, $ymd = 'month')
    {
        if ((int) $expire === 0) {
            return true;
        }
        $a = strtotime("+{$expire} {$ymd}", strtotime($date));

        return $a - time() > 0;
    }

    public static function wareki($format, $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = time();
        }

        $year = (int)date('Y', $timestamp);
        $gengo = '';
        $gengo_short = '';
        if ($timestamp < strtotime('1868-01-25')) {
            $gengo = '';
            $gengo_short = '';
        }
        // Meiji
        elseif ($timestamp < strtotime('1912-07-30')) {
            $gengo = mb_convert_encoding('&#26126;&#27835;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'M';
            $year -= 1867;
        }
        // Taisho
        elseif ($timestamp < strtotime('1926-12-25')) {
            $gengo = mb_convert_encoding('&#22823;&#27491;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'T';
            $year -= 1911;
        }
        // Showa
        elseif ($timestamp < strtotime('1989-0l-08')) {
            $gengo = mb_convert_encoding('&#26157;&#21644;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'S';
            $year -= 1925;
        }
        // Heisei
        elseif ($timestamp < strtotime('2019-05-01')) {
            $gengo = mb_convert_encoding('&#24179;&#25104;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'H';
            $year -= 1988;
        }
        // Reiwa
        else {
            $gengo = mb_convert_encoding('&#20196;&#21644;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'R';
            $year -= 2018;
        }
        $wareki = str_replace(['Y','y'], ['Q','q'], $format);
        $datestr = date($wareki, $timestamp);

        return str_replace(['Q','q'], ["$gengo$year","$gengo_short$year"], $datestr);
    }
}
