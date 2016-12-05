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
 * Session class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Session
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /** 
     * Session save path.
     *
     * @var string
     */
    private $_savePath = '/tmp';

    /** 
     * Session ID.
     *
     * @var string
     */
    private $_sid;

    /** 
     * Session Cookie Name.
     *
     * @var string
     */
    private $_sessname = 'PHPSESSID';

    /** 
     * Session Data encoding.
     *
     * @var string
     */
    private $_enc = 'UTF-8';

    /** 
     * Session Cache Limitter.
     *
     * @var string
     */
    private $_cachelimiter;

    /** 
     * Session Life time.
     *
     * @var string
     */
    private $_lifetime;

    /** 
     * Session valid path.
     *
     * @var string
     */
    private $_path;

    /** 
     * Session valid domain.
     *
     * @var string
     */
    private $_domain;

    /** 
     * Session only secure connection.
     *
     * @var string
     */
    private $_secure;

    /** 
     * Session only http connection.
     *
     * @var bool
     */
    private $_httponly;

    /** 
     * Session status.
     *
     * @var bool
     */
    private $_status = false;

    /**
     * Use Database storage.
     *
     * @var bool
     */
    private $_usedb = false;

    /**
     * Object constructor.
     *
     * @param string $cacheLimiter
     * @param string $savePath
     * @param int    $lifetime
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     */
    public function __construct($cacheLimiter = 'nocache', $savePath = null,
                                $lifetime = 0, $path = '/', $domain = '',
                                $secure = false, $httponly = true
    ) {
        $this->_cachelimiter = $cacheLimiter;
        $this->_savePath = P5_File::realpath($savePath);
        $this->_lifetime = $lifetime;
        $this->_path = preg_replace("/[\/]+$/", '/', $path);
        $this->_domain = $domain;
        $this->_secure = $secure;
        $this->_httponly = $httponly;

        if (!empty($this->_savePath)) {
            if (!file_exists($this->_savePath)) {
                if (false === P5_File_Path::mkpath($this->_savePath, 0777)) {
                    // Resume routine.
                    trigger_error('Directory is not exists '.$this->_savePath, E_USER_ERROR);
                }
            }
            if (is_dir($this->_savePath)) {
                session_save_path($this->_savePath);
            }
        }
        if (!empty($this->_sid)) {
            $this->_sid = session_id($this->_sid);
        }
    }

    /**
     * Set session ID.
     *
     * @param string $id
     *
     * @return string
     */
    public function setSessionId($id)
    {
        $this->_sid = $id;

        return session_id($this->_sid);
    }

    /**
     * Set session name.
     *
     * @param string $name
     *
     * @return string
     */
    public function setSessionName($name)
    {
        $this->_sessname = $name;

        return session_name($name);
    }

    /**
     * Use database storage.
     *
     * @param string $driver
     * @param string $host
     * @param string $port
     */
    public function useDatabase($driver, $host, $source, $user, $password, $port = 3306, $enc = '')
    {
        $this->_usedb = true;
        session_save_path("$driver/$host/$source/$user/$password/$port/$enc");
    }

    /**
     * Starting session.
     *
     * @return bool
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
     * Session Destroy.
     */
    public function destroy()
    {
        $_SESSION = array();
        if (isset($_COOKIE[$this->_sessname])) {
            $params = session_get_cookie_params();
            setcookie($this->_sessname, '', time() - 3600, $params['path'], $params['domain']);
        }
        $sessId = session_id();
        if ($this->_status !== true) {
            return true;
        }

        return @session_destroy();
    }

    /**
     * Return session params.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    public function param($name, $value = null)
    {
        if (isset($value)) {
            $_SESSION[$name] = $value;
        }

        return (isset($_SESSION[$name])) ? $_SESSION[$name] : null;
    }

    /**
     * Set session savepath.
     *
     * @param mixed $path
     */
    public function setSavePath($path)
    {
        $this->_savePath = $path;
    }

    /**
     * Set session cookiepath.
     *
     * @param mixed $path
     */
    public function setCookiePath($path)
    {
        $this->_path = $path;
    }

    /**
     * Set session save domain.
     *
     * @param string $domain
     */
    public function setCookieDomain($domain)
    {
        $this->_domain = $domain;
    }

    /**
     * Set session expire.
     *
     * @param mixed $time
     */
    public function expire($time)
    {
        $this->_lifetime = $time;
    }

    /**
     * Set session Cache limiter.
     *
     * @param string $limiter
     */
    public function setChacheLimiter($limiter)
    {
        $this->_cachelimiter = $limiter;
    }

    /**
     * Set session Cache limiter.
     *
     * @param string $limiter
     */
    public function setSessionSecure($secure)
    {
        $this->_secure = $secure;
    }

    /**
     * remove session params.
     *
     * @param string $key
     */
    public function clear($key = null)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Change the session expire.
     *
     * @return bool
     */
    public function delay($time = 0)
    {
        if (isset($_COOKIE[$this->_sessname])) {
            $params = session_get_cookie_params();
            $this->_lifetime = $time;
            $this->_path = $params['path'];
            $this->_domain = $params['domain'];
            $this->_secure = $params['secure'];
            $this->_httponly = $params['httponly'];

            return setcookie(
                $this->_sessname, $_COOKIE[$this->_sessname], $time,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }
}
