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
 * Authentication class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Auth
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Password database.
     *
     * @var string
     */
    private $_source;

    /**
     * Database object.
     *
     * @var object
     */
    private $_db;

    /**
     * Authentication flag.
     *
     * @var boolean
     */
    private $_auth;

    /**
     * Error message.
     *
     * @var string
     */
    private $_error;

    /**
     * Object constructer
     *
     * @param string $source
     * @param P5_Db $db
     * @return void
     */
    public function __construct($source, P5_Db $db)
    {
        $this->_source = $source;
        $this->_db = $db;
    }

    /**
     * Check User/Password
     *
     * @param  string   $uname
     * @param  string   $upass
     * @return boolean
     */
    public function check($uname, $upass)
    {
        if (empty($uname)) return false;
        $this->_auth = false;
        if (file_exists($this->_source)) {
            $this->authByFile($uname, $upass);
        } else {
            $this->authByDb($uname, $upass);
        }
        return $this->_auth;
    }

    /**
     * Authorize by password file.
     *
     * @param  string   $uname
     * @param  string   $upass
     * @return void
     */
    public function authByFile($uname, $upass)
    {
        $fh  = new P5_File;
        $inc = $fh->read($this->_source);
        $pattern = "/(^|[\s]+)" . $uname . ":([^:\s]+)/s";
        if (preg_match($pattern, $inc, $match)) {
            $password = trim($match[2]);
            if (crypt($upass, $password) == $password) {
                $this->_auth = true;
            } else {
                $this->_error = P5_Lang::translate('PASSWORD_MISSMATCH');
            }
        } else {
            $this->_error = "`$uname' " . P5_Lang::translate('USER_UNDEFINED');
        }
    }

    /**
     * Authorize by password database.
     *
     * @param  string   $uname
     * @param  string   $upass
     * @return void
     */
    public function authByDb($uname, $upass)
    {
        $userColumn   = 'uname';
        $passwdColumn = 'upass';

        $sql = "SELECT `$passwdColumn`" .
               " FROM `" . $this->_source . "`" .
               " WHERE `$userColumn`=" . $this->_db->quote($uname);

        if ($this->_db->query($sql)) {
            $check = $this->_db->fetchColumn(0);
            if ($check) {
                if (sha1($upass) == $check) {
                    $this->_auth = true;
                } else {
                    $this->_error = P5_Lang::translate('PASSWORD_MISSMATCH');
                }
            } else {
                $this->_error = P5_Lang::translate('USER_UNDEFINED');
            }
        } else {
            if ($this->_db->errorCode() == '42S02') {
                $this->_error = 'Database is Broken.';
            } else {
                $this->_error = 'SQL Error!';
            }
        }
    }

    /**
     * Create password.
     *
     * @param int $figure
     * @param int $nums
     * @param int $chrs
     * @return string
     */
    static public function createpassword($figure = 8, $nums = 0, $chrs = 0, $numonly = false)
    {
        $seed['alp'] = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $seed['num'] = str_split('0123456789');
        $seed['chr'] = str_split('#$-=?@[]_');
        $password = '';
        if ($numonly) {
            for($i = 0; $i < $figure; $i++) {
                $n = array_rand($seed['num']);
                $password .= $seed['num'][$n];
            }
            return $password;
        }
        for($i = 0; $i < $figure; $i++) {
            if ($nums <= 0) unset($seed['num']);
            if ($chrs <= 0) unset($seed['chr']);
            if ($figure - $i <= $nums + $chrs) unset($seed['alp']);
            $keys = array_keys($seed);
            $n = array_rand($keys);
            $key = $keys[$n];
            if ($key == 'num') $nums--;
            if ($key == 'chr') $chrs--;
            $n = array_rand($seed[$key]);
            $password .= $seed[$key][$n];
        }
        return $password;
    }

    /**
     * Authentication result.
     *
     * @return boolean
     */
    public function failure()
    {
        return !$this->_auth;
    }

    /**
     * Error message.
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }
}
?>
