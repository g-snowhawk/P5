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
 * @copyright Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Parser.php 2012-03-05 14:46:32 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Config_Parser
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Config_Parser
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Default block name
     */
    const DEFAULTNS = 'global';

    /**
     * Configure data array
     *
     * @var array
     */
    private $_cnf;

    /**
     * Object Constructer
     *
     * @param  string  $inifile
     */
    public function __construct($inifile)
    {
        $inifile = P5_File::realpath($inifile);
        $this->_cnf = parse_ini_file($inifile, true);
    }

    /**
     * Configuration data
     *
     * @param  string  $arg
     * @return mixed
     */
    public function param($arg = null, $value = null)
    {
        if (is_null($arg)) return $this->_cnf;

        $arg = strtolower($arg);

        $keys = explode(':', $arg);

        if (count($keys) < 2) {
            $key = $keys[0];
            $blockName = self::DEFAULTNS;
        } else {
            $key = $keys[1];
            $blockName = $keys[0];
        }

        if ($value) $this->_cnf[$blockName][$key] = $value;

        return (
            array_key_exists($blockName, $this->_cnf) 
            && array_key_exists($key, $this->_cnf[$blockName])
        ) ? $this->_cnf[$blockName][$key] : null;
    }
}
?>
