<?php
/**
 * PlusFive System Frameworks
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
 * @copyright Copyright (c) 2012-2013 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Session.php 2013-07-23 13:35:05 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Session
 * @copyright  Copyright (c) 2012-2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Session
{
    /** 
     * Current version 
     */
    const VERSION = '1.0.0';

    /** 
     * Session save path
     *
     * @var string
     */
    private $_savePath = '/tmp';

    /** 
     * Session ID
     *
     * @var string
     */
    private $_sid;

    /** 
     * Session Cookie Name
     *
     * @var string
     */
    private $_sessname = 'PHPSESSID';

    /** 
     * Session Data encoding
     *
     * @var string
     */
    private $_enc = 'UTF-8';

    /** 
     * Session Cache Limitter
     *
     * @var string
     */
    private $_cachelimiter;

    /** 
     * Session Life time
     *
     * @var string
     */
    private $_lifetime;

    /** 
     * Session valid path
     *
     * @var string
     */
    private $_path;

    /** 
     * Session valid domain
     *
     * @var string
     */
    private $_domain;

    /** 
     * Session only secure connection
     *
     * @var string
     */
    private $_secure;

    /** 
     * Session only http connection
     *
     * @var boolean
     */
    private $_httponly;

    /** 
     * Session status
     *
     * @var bool
     */
    private $_status = false;

    /**
     * Use Database storage
     *
     * @var bool
     */
    private $_usedb = false;

    /**
     * Object constructor
     *
     * @param  string   $cacheLimiter   
     * @param  string   $savePath
     * @param  integer  $lifetime
     * @param  string   $path
     * @param  string   $domain
     * @param  boolean  $secure
     * @param  boolean  $httponly
     * @return void
     */
    public function __construct($cacheLimiter = 'nocache', $savePath = NULL,
                                $lifetime = 0, $path = '/', $domain = '',
                                $secure = false, $httponly = true
    ) {
        $this->_cachelimiter = $cacheLimiter;
        $this->_savePath     = P5_File::realpath($savePath);
        $this->_lifetime     = $lifetime;
        $this->_path         = preg_replace("/[\/]+$/", '/', $path);
        $this->_domain       = $domain;
        $this->_secure       = $secure;
        $this->_httponly     = $httponly;

        if (!empty($this->_savePath)) {
            if (! file_exists($this->_savePath)) {
                if (false === P5_File_Path::mkpath($this->_savePath, 0777)) {
                    // Resume routine.
                    trigger_error('Directory is not exists ' . $this->_savePath, E_USER_ERROR);
                }
            } 
            if (is_dir($this->_savePath)) session_save_path($this->_savePath);
        }
        if (! empty($this->_sid)) $this->_sid = session_id($this->_sid);
    }

    /**
     * Set session ID
     *
     * @param string $id
     * @return string
     */
    public function setSessionId($id)
    {
        $this->_sid = $id;
        return session_id($this->_sid);
    }

    /**
     * Set session name
     *
     * @param string $name
     * @return string
     */
    public function setSessionName($name)
    {
        $this->_sessname = $name;
        return session_name($name);
    }

    /**
     * Use database storage
     *
     * @param string $driver
     * @param string $host
     * @param string $port
     * @return void
     */
    public function useDatabase($driver, $host, $source, $user, $password, $port=3306, $enc='')
    {
        $this->_usedb = true;
        session_save_path("$driver/$host/$source/$user/$password/$port/$enc");
    }

    /**
     * Starting session
     *
     * @return boolean
     */
    public function start()
    {
        session_set_cookie_params($this->_lifetime, $this->_path, $this->_domain, $this->_secure, $this->_httponly);
        session_cache_limiter($this->_cachelimiter);
        if (empty($this->_sid)) {
            $this->_sid = session_id();
        }
        if (empty($this->_sessname)) {
            $this->_sessname = session_name();
        }
        if ($this->_usedb === true) {
            $handler = new P5_Session_Db();
            session_set_save_handler(array($handler, 'open'),
                                     array($handler, 'close'),
                                     array($handler, 'read'),
                                     array($handler, 'write'),
                                     array($handler, 'destroy'),
                                     array($handler, 'gc'));
            register_shutdown_function('session_write_close');
        }
        return $this->_status = session_start();
    }

    /**
     * Session Destroy
     *
     * @return void
     */
    public function destroy() 
    {
        $_SESSION = array();
        if (isset($_COOKIE[$this->_sessname])) {
            $params = session_get_cookie_params();
            setcookie($this->_sessname, '', time() - 3600, $params['path'], $params['domain']);
        }
        $sessId = session_id();
        if ($this->_status !== true) return true;
        return @session_destroy();
    }

    /**
     * Return session params
     *
     * @param   string  $name
     * @param   mixed   $value
     * @return  mixed
     */
    public function param($name, $value = null)
    {
        if (isset($value)) $_SESSION[$name] = $value;
        return (array_key_exists($name, $_SESSION)) ? $_SESSION[$name] : null;
    }

    /**
     * Set session savepath 
     *
     * @param   mixed   $path
     * @return  void
     */
    public function setSavePath($path)
    {
        $this->_savePath = $path;
    }
 
    /**
     * Set session cookiepath 
     *
     * @param   mixed   $path
     * @return  void
     */
    public function setCookiePath($path)
    {
        $this->_path = $path;
    }
 
    /**
     * Set session save domain
     *
     * @param   string  $domain
     * @return  void
     */
    public function setCookieDomain($domain)
    {
        $this->_domain = $domain;
    }

    /**
     * Set session expire
     *
     * @param   mixed   $time
     * @return  void
     */
    public function expire($time)
    {
        $this->_lifetime = $time;
    }

    /**
     * Set session Cache limiter
     *
     * @param   string  $limiter
     * @return  void
     */
    public function setChacheLimiter($limiter)
    {
        $this->_cachelimiter = $limiter;
    }

    /**
     * Set session Cache limiter
     *
     * @param   string  $limiter
     * @return  void
     */
    public function setSessionSecure($secure)
    {
        $this->_secure = $secure;
    }

    /**
     * remove session params
     *
     * @param   string   $key
     * @return  void
     */
    public function clear($key = null)
    {
        if (array_key_exists($key, $_SESSION)) unset($_SESSION[$key]);
    }

    /**
     * Change the session expire
     *
     * @return boolean
     */
    public function delay($time = 0) 
    {
        if (isset($_COOKIE[$this->_sessname])) {
            $params = session_get_cookie_params();
            $this->_lifetime = $time;
            $this->_path     = $params['path'];
            $this->_domain   = $params['domain'];
            $this->_secure   = $params['secure'];
            $this->_httponly = $params['httponly'];
            return setcookie (
                $this->_sessname, $_COOKIE[$this->_sessname], $time, 
                $params['path'], $params['domain'], 
                $params['secure'], $params['httponly']
            );
        }
    }
}
