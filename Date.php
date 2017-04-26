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
}
