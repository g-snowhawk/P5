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
 * @copyright Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Http.php 2012-03-05 16:22:46 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Http
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Http
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Responce header no cache controls
     *
     * @return void
     */
    static public function nocache()
    {
        self::responceHeader("Pragma", "no-cache");
        self::responceHeader("Cache-Control", "private, no-store, no-cache, must-revalidate");
        self::responceHeader("Expires", "Thu, 01 Jan 1970 00:00:00 GMT");
    }

    /**
     * HTTP Responce Header
     *
     * @param string $key
     * @param string $value
     * @param string $option
     * @return void
     */
    static public function responceHeader($key, $value, $option = '')
    {
        $val = (empty($value) && $value !== '0')  ? '' : ": $value";
        $opt = (empty($option)) ? '' : "; $option";
        header($key . $val . $opt);
    }

    /**
     * Normalize URI
     *
     * @param string $path
     * @return string 
     */
    static public function realuri($path)
    {
        $path = preg_replace("/[\/\\\]/", '/', $path);
        $path = preg_replace("/[\/\\\]+/", '/', $path);
        $path = preg_replace("/\/\.\//", '/', $path);
        while(preg_match("/\/[^\/]+\/\.\.\//", $path)) {
            $path = preg_replace("/\/[^\/]+\/\.\.\//", '/', $path);
        }
        $path = preg_replace("/(https?):\//", "$1://", $path);
        return $path;
    }

    /**
     * Get Request Method
     *
     * @return string 
     */
    static public function getMethod()
    {
        return (strtolower($_SERVER{'REQUEST_METHOD'}) == 'post') ? 'POST' : 'GET';
    }

    /**
     * Get HTTP Status code.
     *
     * @param string $url
     * @param int $timeout
     * @param string $bid
     * @param string $bpw
     * @return string 
     */
    static public function getStatus($url, $timeout = 5, $bid = null, $bpw = null)
    {
        $url = str_replace( "&amp;", "&", urldecode(trim($url)) );
        $ua  = "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1";
        $ch  = curl_init();
        curl_setopt( $ch, CURLOPT_USERAGENT,      $ua );
        curl_setopt( $ch, CURLOPT_URL,            $url );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_ENCODING,       '' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER,    true );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_TIMEOUT,        $timeout );
        curl_setopt( $ch, CURLOPT_MAXREDIRS,      10 );
        if (!is_null($bid) && !is_null($bpw)) {
            curl_setopt( $ch, CURLOPT_USERPWD, "$bid:$bpw" );
        }
        $content  = curl_exec( $ch );
        $response = curl_getinfo( $ch );
        curl_close ( $ch );
        return $response['http_code'];
    }

    /**
     * Redirect
     *
     * @return void
     */
    static public function redirect($href)
    {
        header('Location: ' . $href);
        exit;
    }
}
