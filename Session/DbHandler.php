<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016-2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5\Session;
use SessionHandlerInterface;

/**
 * Session with database class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class DbHandler implements SessionHandlerInterface
{
    private $db;
    private $user;
    private $password;
    private $encoding;
    private $session_name;

    public function __construct($user, $password, $encoding)
    {
        $this->user = $user;
        $this->password = $password;
        $this->encoding = $encoding;
    }

    public function open($save_path, $session_name): bool
    {
        $this->session_name = $session_name;

        $dsn = explode('/', $save_path);
        $driver = array_shift($dsn);
        $host = array_shift($dsn);
        $source = array_shift($dsn);
        $port = array_shift($dsn);

        $user = array_shift($dsn);
        $password = array_shift($dsn);
        $enc = implode('', $dsn);

        $host = urldecode($host);

        $this->db = new \P5\Db(
            $driver,
            $host,
            $source,
            $this->user,
            $this->password,
            $port,
            $this->encoding
        );

        return $this->db->open();
    }

    public function read($id): string
    {
        $data = $this->db->get(
            'session_data', $this->session_name, 'session_id = ?', [$id]
        );

        return $data;
    }

    public function write($id, $session_data): bool
    {
        $default_timezone = date_default_timezone_get();

        date_default_timezone_set('UTC');
        $data = [
            'session_id' => $id,
            'session_updated' => time(),
            'session_data' => $session_data,
        ];
        $result = $this->db->replace($this->session_name, $data, ['session_id']);

        date_default_timezone_set($default_timezone);

        return $result === false ? false : true;
    }

    public function destroy($id): bool
    {
        $result = $this->db->delete($this->session_name, 'session_id = ?', [$id]);

        return $result === false ? false : true;
    }

    public function close(): bool
    {
        $this->db->close();

        return true;
    }

    public function gc($maxlifetime): bool
    {
        $default_timezone = date_default_timezone_get();

        date_default_timezone_set('UTC');

        $lifetime = time() - $maxlifetime;
        $result = $this->db->delete(
            $this->session_name, 'session_update < ?', [$lifetime]
        );

        date_default_timezone_set($default_timezone);

        return $result === false ? false : true;
    }
}
