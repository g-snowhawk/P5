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
 * @copyright Copyright (c) 2014 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Exception.php 2014-09-28 12:29:20 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Xml
 * @copyright  Copyright (c) 2014 PlusFive. (http://www.plus-5.com)
 * @version    $Id: Exception.php 2014-09-28 12:29:20 tak@ $
 * @license    GNU General Public License
 */
class P5_Xml_Dom_Exception extends ErrorException
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * object constructer
     *
     * @param string $message
     * @param int $code
     * @param Exception $previous
     * @return void
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
