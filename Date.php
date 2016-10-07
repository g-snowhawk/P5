<?php
/**
 * PlusFive Frameworks
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright Copyright (c) 2012-2031 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Date.php 2013-05-06 18:13:24 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Date
 * @copyright  Copyright (c) 2012-2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Date
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Timestamp to string
     *
     * @param string $format
     * @param number $timestamp
     * @return string
     */
    static public function format($format, $timestamp = null)
    {
        if (empty($timestamp)) $timestamp = time();

        $sec  = date('s', $timestamp);
        $min  = date('i', $timestamp);
        $hour = date('H', $timestamp);
        $mday = date('j', $timestamp);
        $mon  = date('n', $timestamp);
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
     * Check expire
     *
     * @param string $date
     * @param number $expire
     * @param string $ymd
     * @return boolean
     */
    static public function expire($date, $expire, $ymd = 'month')
    {
        if ((int)$expire === 0) return true;
        $a = strtotime("+{$expire} {$ymd}", strtotime($date));
        return $a - time() > 0;
    }
}
