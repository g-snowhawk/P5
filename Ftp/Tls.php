<?php
/**
 * This file is part of P5 Framework
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
/**
 * Ftp TLS class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Ftp_Tls extends P5_Ftp
{
    /** 
     * Current version
     */
    const VERSION = '1.1.0';
    
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
