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
 * Session use database class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Session_Db
{
    /**
     * Database Connection Class.
     *
     * @var P5_Db
     */
    private $_db;

    /**
     * Session Name.
     *
     * @var string
     */
    private $_sessionName;

    /**
     * Open Handlar.
     *
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        $dsn = explode('/', $savePath);
        $driver = array_shift($dsn);
        $host = array_shift($dsn);
        $source = array_shift($dsn);
        $user = array_shift($dsn);
        $password = array_shift($dsn);
        $port = array_shift($dsn);
        $enc = implode('/', $dsn);
        $this->_sessionName = $sessionName;
        $this->_db = new P5_Db($driver, $host, $source, $user, $password, $port, $enc);
        $this->_db->open();

        return true;
    }

    /** 
     * Read Handlar.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function read($id)
    {
        $session_data = '';
        if (false !== $result = $this->_db->get('session_data', $this->_sessionName, 'session_id = ?', array($id))) {
            $session_data = $result;
        }

        return $session_data;
    }

    /** 
     * Write Handlar.
     *
     * @param string $id
     * @param string $session_data
     *
     * @return bool
     */
    public function write($id, $session_data)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $data = array('session_id' => $id,
                      'session_updated' => time(),
                      'session_data' => $session_data, );

        return $this->_db->replace($this->_sessionName, $data, array('session_id'));
        date_default_timezone_set($tz);
    }

    /** 
     * Destroy Handlar.
     *
     * @param string $id
     *
     * @return bool
     */
    public function destroy($id)
    {
        return $this->_db->delete($this->_sessionName, 'session_id = ?', array($id));
    }

    /** 
     * Destroy Handlar.
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /** 
     * Gc Handlar.
     *
     * @param int $maxlifetime
     *
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $mlt = time() - $maxlifetime;
        $ret = $this->_db->delete($this->_sessionName, 'session_update < ?', array($mlt));
        date_default_timezone_set($tz);

        return $ret;
    }
}
