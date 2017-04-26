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
 * Session Class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Session
{
    /** 
     * Session save path.
     *
     * @var string
     */
    private $save_path = '/tmp';

    /** 
     * Session ID.
     *
     * @var string
     */
    private $sid;

    /** 
     * Session Cookie Name.
     *
     * @var string
     */
    private $session_name = 'PHPSESSID';

    /** 
     * Session Cache Limitter.
     *
     * @var string
     */
    private $cachelimiter;

    /** 
     * Session Life time.
     *
     * @var string
     */
    private $lifetime;

    /** 
     * Session valid path.
     *
     * @var string
     */
    private $path;

    /** 
     * Session valid domain.
     *
     * @var string
     */
    private $domain;

    /** 
     * Session only secure connection.
     *
     * @var string
     */
    private $secure;

    /** 
     * Session only http connection.
     *
     * @var bool
     */
    private $httponly;

    /** 
     * Session status.
     *
     * @var bool
     */
    private $status = false;

    /**
     * Use Database storage.
     *
     * @var bool
     */
    private $usedb = false;

    /**
     * Object constructor.
     *
     * @param string $cacheLimiter
     * @param string $save_path
     * @param int    $lifetime
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     */
    public function __construct($cacheLimiter = 'nocache', $save_path = null,
                                $lifetime = 0, $path = '/', $domain = '',
                                $secure = false, $httponly = true
    ) {
        $this->cachelimiter = $cacheLimiter;
        $this->save_path = File::realpath($save_path);
        $this->lifetime = $lifetime;
        $this->path = rtrim($path, '/');
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httponly = $httponly;

        if (!empty($this->save_path)) {
            if (!file_exists($this->save_path)) {
                if (false === mkdir($this->save_path, 0777, true)) {
                    throw new \Exception('Session save path is not exists '.$this->save_path);
                }
            }
            if (!is_dir($this->save_path)) {
                throw new \Exception("Session save path `{$this->save_path}' is not directory");
            }
            session_save_path($this->save_path);
        }
        if (!empty($this->sid)) {
            $this->sid = session_id($this->sid);
        }
        session_register_shutdown();
    }

    /**
     * Set session ID.
     *
     * @param string $id
     *
     * @return string
     */
    public function setID($id)
    {
        $this->sid = $id;

        return session_id($this->sid);
    }

    /**
     * Set session name.
     *
     * @param string $name
     *
     * @return string
     */
    public function setName($name)
    {
        $this->session_name = $name;

        return session_name($name);
    }

    /**
     * Use database storage.
     *
     * @param string $driver
     * @param string $host
     * @param string $port
     */
    public function useDatabase($driver, $host, $source, $user, $password, $port = 3306, $encoding = '')
    {
        $this->usedb = true;
        session_save_path("$driver/$host/$source/$user/$password/$port/$encoding");
    }

    /**
     * Starting session.
     *
     * @return bool
     */
    public function start()
    {
        session_set_cookie_params($this->lifetime, $this->path, $this->domain, $this->secure, $this->httponly);
        session_cache_limiter($this->cachelimiter);
        if (empty($this->sid)) {
            $this->sid = session_id();
        }
        if (empty($this->session_name)) {
            $this->session_name = session_name();
        }
        if ($this->usedb === true) {
            $handler = new \P5\Session\Db();
            session_set_save_handler([$handler, 'open'],
                                     [$handler, 'close'],
                                     [$handler, 'read'],
                                     [$handler, 'write'],
                                     [$handler, 'destroy'],
                                     [$handler, 'gc']);
            register_shutdown_function('session_write_close');
        }

        return $this->status = session_start();
    }

    /**
     * Session Destroy.
     */
    public function destroy()
    {
        $_SESSION = [];
        if (isset($_COOKIE[$this->session_name])) {
            $params = session_get_cookie_params();
            setcookie($this->session_name, '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        $sessId = session_id();
        if ($this->status !== true) {
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

        return (array_key_exists($name, $_SESSION)) ? $_SESSION[$name] : null;
    }

    /**
     * Set session savepath.
     *
     * @param mixed $path
     */
    public function setSavePath($path)
    {
        $this->save_path = $path;
    }

    /**
     * Set session cookiepath.
     *
     * @param mixed $path
     */
    public function setCookiePath($path)
    {
        $this->path = $path;
    }

    /**
     * Set session cookiepath.
     *
     * @param mixed $path
     */
    public function getCookiePath($path)
    {
        return $this->path;
    }

    /**
     * Set session save domain.
     *
     * @param string $domain
     */
    public function setCookieDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Get session save domain.
     *
     * @param string $domain
     */
    public function getCookieDomain($domain)
    {
        return $this->domain;
    }

    /**
     * Set session expire.
     *
     * @param mixed $time
     */
    public function expire($time)
    {
        $this->lifetime = $time;
    }

    /**
     * Set session Cache limiter.
     *
     * @param string $limiter
     */
    public function setChacheLimiter($limiter)
    {
        $this->cachelimiter = $limiter;
    }

    /**
     * Set session Cache limiter.
     *
     * @param string $limiter
     */
    public function setSessionSecure($secure)
    {
        $this->secure = $secure;
    }

    /**
     * remove session params.
     *
     * @param string $key
     */
    public function clear($key = null)
    {
        if (array_key_exists($key, $_SESSION)) {
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
        if (isset($_COOKIE[$this->session_name])) {
            $params = session_get_cookie_params();
            $this->lifetime = $time;
            $this->path = $params['path'];
            $this->domain = $params['domain'];
            $this->secure = $params['secure'];
            $this->httponly = $params['httponly'];

            return setcookie(
                $this->session_name, $_COOKIE[$this->session_name], $time,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }
}
