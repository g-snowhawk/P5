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
 * @copyright Copyright (c) 2016 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Lf2crlf.php 2016-06-03 08:39:14 tak@ $
 */

/**
 * Convert LF to CRLF for stream filter
 *
 * @category   P5
 * @package    P5_Lf2crlf
 * @copyright  Copyright (c) 2016 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Lf2crlf extends php_user_filter
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    function filter($in, $out, &$consumed, $closing)
    {
        while($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = mb_convert_encoding(
                preg_replace("/(?<!\r)\n/", "\r\n", $bucket->data),
                $this->params['to'],
                mb_internal_encoding()
            );
            $consumed += strlen($bucket->data);
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}
