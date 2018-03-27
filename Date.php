<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

/**
 * Date class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Date
{
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

    /**
     * Convert to wareki from timestamp
     *
     * @param string $format
     * @param int $timestamp
     *
     * @return string
     */
    public static function wareki($format, $timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = time();
        }

        $year = (int)date('Y', $timestamp);
        $gengo = '';
        $gengo_short = '';
        // Meiji
        if ($timestamp < -1812183360) {
            $gengo = mb_convert_encoding('&#26126;&#27835;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'M';
            $year -= 1867;
        }
        // Taisho
        elseif ($timestamp < -1357630440) {
            $gengo = mb_convert_encoding('&#22823;&#27491;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'T';
            $year -= 1911;
        }
        // Showa
        elseif ($timestamp < 600188400) {
            $gengo = mb_convert_encoding('&#26157;&#21644;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'S';
            $year -= 1925;
        }
        // Heisei
        else {
            $gengo = mb_convert_encoding('&#24179;&#25104;', 'UTF-8', 'HTML-ENTITIES');
            $gengo_short = 'H';
            $year -= 1988;
        }

        $wareki = str_replace(['Y','y'], ['Q','q'], $format);
        $datestr = date($wareki, $timestamp);
        return str_replace(['Q','q'], ["$gengo$year","$gengo_short$year"], $datestr);
    }
}
