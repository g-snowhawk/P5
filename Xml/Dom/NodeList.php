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
 * @copyright Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @version   $Id: NodeList.php 2013-09-01 08:44:05 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Xml
 * @copyright  Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Xml_Dom_NodeList implements IteratorAggregate
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Length (readonly)
     *
     * @var int
     */
    private $length = 0;

    /**
     * Items
     *
     * @var array
     */
    private $_items = array();

    /** 
     * Object constructor
     *
     * @param array $items
     * @return  void
     */
    public function __construct(array $items) 
    {
        $this->_items = $items;
        $this->length = count($items);
    }

    /**
     * Getter Method
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        switch ($key) {
            case 'length' :
                foreach($this->_items as $index => $item) {
                    if (is_null($this->_items[$index]->parentNode)) {
                        array_splice($this->_items, $index, 1);
                    }
                }
                return count($this->_items);
        }
    }

    /**
     * Iterator
     *
     * @return ArrayIterator
     */
    public function getIterator() 
    {
        return new ArrayIterator($this->_items);
    }


    /**
     * item
     *
     * @param int $index
     * @return DOMNode
     */
    public function item($index)
    {
        if (is_null($this->_items[$index]->parentNode)) {
            array_splice($this->_items, $index, 1);
        }
        return $this->_items[$index];
    }
}
