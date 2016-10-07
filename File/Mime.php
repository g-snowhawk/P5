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
 * @version   $Id: File.php 2012-03-05 14:58:04 tak@ $
 */

/**
 * @category   P5
 * @package    P5_File
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_File_Mime
{
    /**
     * Mime types
     *
     * @var array
     */
    static private $_types = array (
        'bmp'  => 'image/bmp',
        'css'  => 'text/css',
        'gif'  => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'js'   => 'text/javascript',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'swf'  => 'application/x-shockwave-flash',
        'tiff' => 'image/tiff',
        'txt'  => 'text/plain',
    );

    /**
     * Check mime by extension
     *
     * @param  string   $key
     * @return string
     */
    static public function type($key)
    {
        return (isset(self::$_types[$key])) ? self::$_types[$key] 
                                            : 'application/octet-stream';
    }
}
