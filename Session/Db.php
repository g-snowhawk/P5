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
 * @version   $Id: Db.php 2014-09-04 10:00:03 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Session
 * @copyright  Copyright (c) 2014 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Session_Db
{
    /**
     * Database Connection Class
     *
     * @var P5_Db
     */
    private $_db;

    /**
     * Session Name
     *
     * @var string
     */
    private $_sessionName;

    /**
     * Open Handlar
     *
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     */
    function open($savePath, $sessionName) 
    {
        $dsn = explode('/', $savePath);
        $driver   = array_shift($dsn);
        $host     = array_shift($dsn);
        $source   = array_shift($dsn);
        $user     = array_shift($dsn);
        $password = array_shift($dsn);
        $port     = array_shift($dsn);
        $enc      = implode('/',$dsn);
        $this->_sessionName = $sessionName;
        $this->_db = new P5_Db($driver, $host, $source, $user, $password, $port, $enc);
        $this->_db->open();
        return true;
    }

    /** 
     * Read Handlar 
     *
     * @param string $id
     * @return mixed
     */
    function read($id) 
    {
        $session_data = '';
        if (false !== $result = $this->_db->get('session_data', $this->_sessionName, 'session_id = ?', array($id))) {
            $session_data = $result;
        }
        return $session_data;
    }

    /** 
     * Write Handlar 
     *
     * @param string $id
     * @param string $session_data
     * @return bool
     */
    function write($id, $session_data) 
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $data = array('session_id' => $id,
                      'session_updated' => time(),
                      'session_data' => $session_data);
        return $this->_db->replace($this->_sessionName, $data, array('session_id'));
        date_default_timezone_set($tz);
    }

    /** 
     * Destroy Handlar 
     *
     * @param string $id
     * @return bool
     */
    function destroy($id) 
    {
        return $this->_db->delete($this->_sessionName, 'session_id = ?', array($id));
    }

    /** 
     * Destroy Handlar 
     *
     * @return bool
     */
    function close() 
    {
        return true;
    }

    /** 
     * Gc Handlar 
     *
     * @param int $maxlifetime
     * @return bool
     */
    function gc($maxlifetime) 
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $mlt = time() - $maxlifetime;
        $ret = $this->_db->delete($this->_sessionName, 'session_update < ?', array($mlt));
        date_default_timezone_set($tz);
        return $ret;
    }
}
