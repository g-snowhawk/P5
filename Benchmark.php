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
 * @copyright Copyright (c) 2015 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Benchmark.php 2015-07-06 06:17:12 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Benchmark
 * @copyright  Copyright (c) 2015 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Benchmark
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Start time
     *
     * @var double
     */
    private $_start;

    /**
     * Object constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_start = microtime(true);
    }

    /**
     * Object destructor
     *
     * @return void
     */
    public function __destruct()
    {
        echo "\n", microtime(true) - $this->_start, "\n";
    }
}
