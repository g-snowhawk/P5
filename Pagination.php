<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

/**
 * Multi language translator.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Pagination
{
    private $current_page;
    private $max_per_page;
    private $total_pages;
    private $link_count;
    private $link_format;
    private $link_start;
    private $link_end;
    private $suffix_separator = '';
    private $inited = false;

    /**
     * Object constructor.
     */
    public function __construct()
    {
    }

    /**
     * Clone this class.
     */
    public function __clone()
    {
        $this->current_page = null;
        $this->max_per_page = null;
        $this->total_pages = null;
        $this->link_count = null;
        $this->link_format = null;
        $this->link_start = null;
        $this->link_end = null;
        $this->suffix_separator = '';
        $this->inited = false;
    }

    /**
     * Initialize.
     *
     * @param int $total
     * @param int $rows
     * @param int $linkcount
     */
    public function init($total, $rows, $link_count = 0)
    {
        if ($this->inited === true) {
            return;
        }
        $this->total_pages = ceil($total / $rows);
        $this->max_per_pages = $rows;
        $this->link_count = $link_count;
        $this->current_page = 1;
        $this->link_start = $this->start();
        $this->link_end = $this->end();
        $this->inited = true;
    }

    /**
     * Update initialized flag.
     *
     * @param bool $flag
     */
    public function setInited($boolean)
    {
        $this->inited = (bool)$boolean;
    }

    /**
     * Reference already initialized.
     *
     * @return bool
     */
    public function isInited()
    {
        return $this->inited;
    }

    /**
     * Modify current page number.
     *
     * @param int $page_number
     *
     * @return int
     */
    public function setCurrentPage($page_number)
    {
        return $this->current_page = $page_number;
    }

    /**
     * Modify link format.
     *
     * @param string $format
     *
     * @return int
     */
    public function setLinkFormat($format)
    {
        return $this->link_format = $format;
    }

    /**
     * Reference to current page number.
     *
     * @return int
     */
    public function current()
    {
        return $this->current_page;
    }

    /**
     * Reference to previous page number.
     *
     * @return int
     */
    public function prev()
    {
        return $this->current_page - 1;
    }

    /**
     * Reference to next page number.
     *
     * @return int
     */
    public function next()
    {
        return $this->current_page + 1;
    }

    /**
     * Reference to pages total count.
     *
     * @return int
     */
    public function total()
    {
        return $this->total_pages;
    }

    /**
     * Reference to link format.
     *
     * @return int
     */
    public function format()
    {
        return $this->link_format;
    }

    /**
     * Reference to navigation start number.
     *
     * @return int
     */
    public function start()
    {
        $start = 1;
        if ($this->link_count > 0) {
            $start = $this->current_page - floor($this->link_count / 2);
            $end = $this->end();
            if ($end - $start < $this->link_count) {
                $start = $end - $this->link_count + 1;
            }
            if ($start < 1) {
                $start = 1;
            }
        }

        return $start;
    }

    /**
     * Reference to navigation end number.
     *
     * @return int
     */
    public function end()
    {
        $end = $this->total_pages;
        if ($this->link_count > 0) {
            $end = $this->start() + $col - 1;
            if ($end > $this->total_pages) {
                $end = $this->total_pages;
            }
        }

        return $end;
    }

    /**
     * link range.
     *
     * @return array
     */
    public function range()
    {
        return range($this->link_start, $this->link_end);
    }

    public function reset($total)
    {
        $this->total_pages = ceil($total / $this->max_per_pages);
    }

    /**
     * modify suffix.
     *
     * @param string $suffix
     *
     * @return string
     */
    public function setSuffix($suffix)
    {
        return $this->suffix_separator = $suffix;
    }

    /**
     * Page suffix for filename.
     *
     * @param int $page_number  Page number
     * @param int $min          suffix filter
     *
     * @return string
     */
    public function suffix($page_number, $min = 1)
    {
        return ($page_number > $min) ? $this->suffix_separator.$page_number : '';
    }
}
