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
 * Configuration parser class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Config_Parser
{
    /**
     * Current version
     */
    const VERSION = '1.1.0';

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
