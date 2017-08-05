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
class P5_Xml_Dom_NodeList implements Iterator
{
    private $index = 0;
    private $items = array();
    private $nofilter = false;

    public function __construct(array $items, $nofilter = false)
    {
        $this->items = $items;
        $this->nofilter = $nofilter;
        $this->rewind();
    }

    public function __get($key)
    {
        switch ($key) {
            case 'length' :
                if ($this->nofilter === false) {
                    $this->items = array_values(array_filter($this->items, array($this, 'itemFilter')));
                }

                return count($this->items);
                break;
        }
    }

    public function item($index)
    {
        if ($this->nofilter === false) {
            $this->items = array_values(array_filter($this->items, array($this, 'itemFilter')));
        }

        return $this->items[$index];
    }

    private function itemFilter($value)
    {
        return !is_null($value->parentNode);
    }

    public function current()
    {
        return $this->items[$this->index];
    }
    public function key()
    {
        return $this->index;
    }
    public function next()
    {
        if ($this->nofilter === false) {
            $this->items = array_values(array_filter($this->items, array($this, 'itemFilter')));
        }
        ++$this->index;
    }
    public function rewind()
    {
        $this->index = 0;
    }
    public function valid()
    {
        return isset($this->items[$this->index]);
    }
}