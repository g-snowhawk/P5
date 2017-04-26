<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5\Session;

/**
 * Session with database class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Db
{
    /**
     * Database connection class.
     *
     * @var \P5\Db
     */
    private $db;

    /**
     * Session Name.
     *
     * @var string
     */
    private $session_name;

    /**
     * Open Handlar.
     *
     * @param string $save_path
     * @param string $session_name
     *
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        $dsn = explode('/', $save_path);
        $driver = array_shift($dsn);
        $host = array_shift($dsn);
        $source = array_shift($dsn);
        $user = array_shift($dsn);
        $password = array_shift($dsn);
        $port = array_shift($dsn);
        $enc = implode('/', $dsn);
        $this->session_name = $session_name;
        $this->db = new \P5\Db($driver, $host, $source, $user, $password, $port, $enc);
        $this->db->open();

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
        if (false !== $result = $this->db->get('session_data', $this->session_name, 'session_id = ?', [$id])) {
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
        $default_timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $data = [
            'session_id' => $id,
            'session_updated' => time(),
            'session_data' => $session_data,
        ];
        $ret = $this->db->replace($this->session_name, $data, ['session_id']);
        date_default_timezone_set($default_timezone);

        return $ret;
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
        return $this->db->delete($this->session_name, 'session_id = ?', [$id]);
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
        $default_timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $mlt = time() - $maxlifetime;
        $ret = $this->db->delete($this->session_name, 'session_update < ?', [$mlt]);
        date_default_timezone_set($default_timezone);

        return $ret;
    }
}
