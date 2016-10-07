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
 * @version   $Id: Tls.php 2014-02-27 08:55:22 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Ftp
 * @copyright  Copyright (c) 2014 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Ftp_Tls extends P5_Ftp
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';
    
    /**
     * Object Constructer
     *
     * @param string $host
     * @param string $user
     * @param string $passwd
     * @param string $dir
     * @param bool $pasv
     * @return void
     */
    function __construct($host, $user, $passwd, $dir, $port = 0, $pasv = false) 
    {
        if (empty($port)) {
            $port = 21;
        }
        $this->_host      = $host;
        $this->_user      = $user;
        $this->_passwd    = $passwd;
        $this->_directory = $dir;
        $this->_port      = $port;
        $this->_pasv      = $pasv;
        // Connect FTP Server.
        if (false === $this->_connID = @ftp_ssl_connect($this->_host, $this->_port)) {
            throw new P5_Ftp_Exception(P5_Lang::translate('FAILURE_CONNECT_FTP'), E_USER_WARNING);
        }
    }
}
