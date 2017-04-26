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
 * FTP class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Ftp
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * FTP server address.
     *
     * @var string
     */
    protected $_host;

    /**
     * FTP server port.
     *
     * @var string
     */
    protected $_port;

    /**
     * FTP user name.
     *
     * @var string
     */
    protected $_user;

    /**
     * Passphrase for FTP user.
     *
     * @var string
     */
    protected $_passwd;

    /**
     * Default directory.
     *
     * @var string
     */
    protected $_directory;

    /**
     * PASSIV Mode.
     *
     * @var bool
     */
    protected $_pasv;

    /**
     * FTP Connection resource.
     *
     * @var string
     */
    protected $_connID;

    /**
     * Chroot.
     *
     * @var bool
     */
    private $_chroot = true;

    /**
     * Current directory.
     *
     * @var string
     */
    private $_currentDir;

    /**
     * User home directory.
     *
     * @var string
     */
    private $_root;

    /**
     * Error Message.
     *
     * @var string
     */
    private $_error;

    /**
     * timeout.
     *
     * @var int
     */
    private $_timeout;

    /**
     * Object Constructer.
     *
     * @param string $host
     * @param string $user
     * @param string $passwd
     * @param string $dir
     * @param bool   $pasv
     * @param int    $timeout
     */
    public function __construct($host, $user, $passwd, $dir = '', $port = 21, $pasv = false, $timeout = 90)
    {
        if (empty($port)) {
            $port = 21;
        }
        $this->_host = $host;
        $this->_user = $user;
        $this->_passwd = $passwd;
        $this->_directory = $dir;
        $this->_port = $port;
        $this->_pasv = $pasv;
        $this->_timeout = $timeout;
        // Connect FTP Server.
        if (false === $this->_connID = @ftp_connect($this->_host, $this->_port, $this->_timeout)) {
            throw new P5_Ftp_Exception(P5_Lang::translate('FAILURE_CONNECT_FTP'), E_USER_WARNING);
        }
    }

    /**
     * Using TLS/SSL connection.
     */
    public function secure()
    {
        // Connect FTP Server on TLS/SSL.
        if (function_exists('ftp_ssl_connect')) {
            if (false === $this->_connID = @ftp_ssl_connect($this->_host, $this->_port, $this->_timeout)) {
                throw new P5_Ftp_Exception(P5_Lang::translate('FAILURE_CONNECT_FTP'), E_USER_WARNING);
            }
        }
    }

    /**
     * Open FTP connection.
     *
     * @return bool
     */
    public function login()
    {
        if (false !== @ftp_login($this->_connID, $this->_user, $this->_passwd)) {
            if (false !== ftp_pasv($this->_connID, $this->_pasv)) {
                $this->_root = preg_replace("/\/$/", '', ftp_pwd($this->_connID));
                // Change current directory.
                if (false !== $this->cd($this->_directory)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Close FTP connection.
     *
     * @return bool
     */
    public function close()
    {
        return ftp_close($this->_connID);
    }

    /**
     * Download files.
     *
     * @param string $local_path
     * @param string $remote_path
     * @param int    $mode
     * @param int    $resumepos
     *
     * @return bool
     */
    public function get($local_file, $remote_file, $mode = FTP_BINARY, $resumepos = 0)
    {
        // File
        if ($this->is_file($remote_file)) {
            return ftp_get($this->_connID, $local_file, $remote_file, $mode, $resumepos);
        }
        // Directory
        if ($this->is_dir($remote_file)) {
            if (false === file_exists($local_file)) {
                if (false === mkdir($local_file)) {
                    return false;
                }
            }
            $remote_file = $this->realDir($remote_file);
            if (false !== $dirs = ftp_nlist($this->_connID, $remote_file)) {
                foreach ($dirs as $dir) {
                    $entry = basename($dir);
                    if ($entry == '.' || $entry == '..') {
                        continue;
                    }
                    if (false === $this->get("$local_file/$entry", "$remote_file/$entry", $mode, $resumepos)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        }
    }

    /**
     * Upload files.
     *
     * @param string $remote_file
     * @param string $local_file
     * @param int    $mode
     * @param int    $startpos
     *
     * @return bool
     */
    public function put($remote_file, $local_file, $mode = FTP_BINARY, $startpos = 0)
    {
        // File
        if (is_file($local_file)) {
            return ftp_put($this->_connID, $remote_file, $local_file, $mode, $startpos);
        }
        // Directory
        if (is_dir($local_file)) {
            if (false === $this->file_exists($remote_file)) {
                if (false === $this->mkdir($remote_file)) {
                    return false;
                }
            }
            // Recurse
            if ($dh = opendir($local_file)) {
                while (false !== $entry = readdir($dh)) {
                    if ($entry == '.' || $entry == '..') {
                        continue;
                    }
                    if (false === $this->put("$remote_file/$entry", "$local_file/$entry", $mode, $startpos)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        }
    }

    /**
     * Rename files.
     *
     * @param string $oldname
     * @param string $newname
     *
     * @return bool
     */
    public function rename($oldname, $newname)
    {
        return ftp_rename($this->_connID, $oldname, $newname);
    }

    /**
     * Remove file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return ftp_delete($this->_connID, $path);
    }

    /**
     * Remove directory.
     *
     * @param string $path
     * @param bool   $force
     *
     * @return bool
     */
    public function rmdir($path, $force = false)
    {
        // Trim slash
        $path = $this->realDir($path);
        // Recursive remove.
        if ($force !== false) {
            $dirs = ftp_nlist($this->_connID, $path);
            foreach ((array) $dirs as $dir) {
                $entry = basename($dir);
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                // Remove File
                if ($this->is_file("$path/$entry")) {
                    if (false === $this->delete("$path/$entry")) {
                        return false;
                    }
                    continue;
                }
                // Remove Directory
                if ($this->is_dir("$path/$entry")) {
                    if (false === $this->rmdir("$path/$entry", $force)) {
                        return false;
                    }
                }
            }
        }
        // Single remove.
        return @ftp_rmdir($this->_connID, $path);
    }

    /**
     * Copy file.
     *
     * @param string $source
     * @param string $dist
     * @param int    $mode
     * @param int    $resumepos
     *
     * @return bool
     */
    public function copy($source, $dist, $mode = FTP_BINARY, $resumepos = 0)
    {
        if ($source == $dist) {
            return false;
        }
        if ($this->is_dir($source)) {
            throw new P5_Ftp_Exception(P5_Lang::translate('NOT_COPY_DIRECTORY'));
        }
        if (false !== $fp = tmpfile()) {
            if (ftp_fget($this->_connID, $fp, $source, $mode, $resumepos)) {
                if (rewind($fp) && ftp_fput($this->_connID, $dist, $fp, $mode, $resumepos)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Make remote directory.
     *
     * @param string $path
     * @param bool   $recursive
     *
     * @return bool
     */
    public function mkdir($path, $recursive = false)
    {
        if ($recursive === false) {
            return @ftp_mkdir($this->_connID, $path);
        }
        // recursive
        $dirs = (array) explode('/', $this->realDir($path));
        foreach ($dirs as $dir) {
            if (false === @ftp_chdir($this->_connID, $dir)) {
                if (!preg_match("/^\.+$/", $dir)) {
                    if (false === ftp_mkdir($this->_connID, $dir)) {
                        return false;
                    }
                }
                if (false === @ftp_chdir($this->_connID, $dir)) {
                    return false;
                }
            }
        }
    }

    /**
     * Change directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function cd($path)
    {
        $dir = $this->realDir($path);
        // chroot
        if ($this->_chroot === true && preg_match("/^\//", $dir)) {
            $dir = $this->_root.'/'.preg_replace("/^\//", '', $dir);
        }

        return @ftp_chdir($this->_connID, $dir);
    }

    /**
     * current directory.
     *
     * @return string
     */
    public function pwd()
    {
        return ftp_pwd($this->_connID);
    }

    /**
     * Check Exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function file_exists($path)
    {
        $f = basename($path);
        $d = dirname($path);
        $buff = ftp_nlist($this->_connID, $d);
        foreach ((array) $buff as $line) {
            if ($line == $f || preg_match("/\/".preg_quote($f, '/').'$/i', $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * List.
     *
     * @param string $path
     *
     * @return array
     */
    public function ls($path)
    {
        $buff = ftp_rawlist($this->_connID, $path);
        // rsort($buff);
        $data = array();
        if (is_array($buff)) {
            foreach ($buff as $line) {
                $current = preg_split("/[\s]+/", $line);
                if (empty($current[8])) {
                    continue;
                }
                // Hidden dot file.
                if (substr($current[8], 0, 1) == '.') {
                    continue;
                }
                $arr = array(
                    'type' => self::type($current[0]),
                    'count' => $current[1],
                    'user' => $current[2],
                    'group' => $current[3],
                    'size' => self::size($current[4]),
                    'month' => $current[5],
                    'day' => $current[6],
                    'year' => $current[7],
                    'file' => $current[8],
                );
                array_push($data, $arr);
            }
        }

        return $data;
    }

    /**
     * Path is Link or not.
     *
     * @param string $path
     *
     * @return array
     */
    public function is_link($path)
    {
        return $this->is_file($path, 'link');
    }

    /**
     * Path is Directory or not.
     *
     * @param string $path
     *
     * @return array
     */
    public function is_dir($path)
    {
        return $this->is_file($path, 'directory');
    }

    /**
     * Path is File or not.
     *
     * @param string $path
     * @param string $type
     *
     * @return array
     */
    public function is_file($path, $type = 'file')
    {
        $f = basename($path);
        $d = dirname($path);
        $buff = ftp_rawlist($this->_connID, $d);
        foreach ((array) $buff as $line) {
            if (preg_match("/[\s]+".preg_quote($f, '/').'$/i', $line)) {
                if (self::type($line) === $type) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * filetype.
     *
     * @param string $perms
     *
     * @return string
     */
    public static function type($perms)
    {
        switch (substr($perms, 0, 1)) {
            case 'd' : return 'directory';
            case 'l' : return 'link';
            default  : return 'file';
        }
    }

    /**
     * filesize to string.
     *
     * @param float $size
     *
     * @return string
     */
    public static function size($size)
    {
        if ($size < 1024) {
            return round($size, 2).' Byte';
        } elseif ($size < pow(1024, 2)) {
            return round(($size / 1024), 2).' KB';
        } elseif ($size < pow(1024, 3)) {
            return round((($size / 1024) / 1024), 2).' MB';
        } elseif ($size < pow(1024, 4)) {
            return round(((($size / 1024) / 1024) / 1024), 2).' GB';
        }
    }

    /**
     * Real path.
     * 
     * @param string $path
     *
     * @return string
     */
    public static function realDir($path)
    {
        if (preg_match("/[^\/]$/", $path)) {
            $path .= '/';
        }

        return dirname($path.'.');
    }

    /**
     * Error Message.
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }
}
