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
 * Benchmark class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Benchmark
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Start time.
     *
     * @var float
     */
    private $_start;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $this->_start = microtime(true);
    }

    /**
     * Object destructor.
     */
    public function __destruct()
    {
        echo "\n", microtime(true) - $this->_start, "\n";
    }
}
