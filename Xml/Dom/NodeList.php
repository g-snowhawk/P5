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
 * XML DOM custom  Nodelist.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Xml_Dom_NodeList implements IteratorAggregate
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Length (readonly).
     *
     * @var int
     */
    private $length = 0;

    /**
     * Items.
     *
     * @var array
     */
    private $_items = array();

    /** 
     * Object constructor.
     *
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->_items = $items;
        $this->length = count($items);
    }

    /**
     * Getter Method.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        switch ($key) {
            case 'length' :
                foreach ($this->_items as $index => $item) {
                    if (is_null($this->_items[$index]->parentNode)) {
                        array_splice($this->_items, $index, 1);
                    }
                }

                return count($this->_items);
        }
    }

    /**
     * Iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_items);
    }

    /**
     * item.
     *
     * @param int $index
     *
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
