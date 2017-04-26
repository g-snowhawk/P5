<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
include_once 'PHPExcel.php';

/**
 * Excel class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Excel extends PHPExcel
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * PHPExcel_IOFactory class instance.
     *
     * var PHPExcel_IOFactory
     */
    private $_writer;

    /**
     * PHPExcel_IOFactory class instance.
     *
     * var PHPExcel_IOFactory
     */
    private $_reader;

    /**
     * Active Sheet.
     *
     * var object
     */
    private $_sheet;

    /**
     * book.
     *
     * var object
     */
    private $_book;

    /**
     * Object constructor.
     *
     * return void
     */
    public function __construct($ver = 'Excel2007')
    {
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
        $cacheSettings = array('dir' => '/tmp');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $this->_writer = PHPExcel_IOFactory::createWriter($this, $ver);
        $this->_reader = PHPExcel_IOFactory::createReader($ver);
        parent::__construct();
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
        $key = "_$key";
        if (true === property_exists($this, $key)) {
            return $this->$key;
        }

        return;
    }

    /**
     * Load File.
     *
     * @param string $path
     *
     * @return bool
     */
    public function load($path)
    {
        $this->_book = $this->_reader->load($path);
    }

    /**
     * Save File.
     *
     * @param string $path
     *
     * @return bool
     */
    public function save($path)
    {
        return $this->_writer->save($path);
    }

    /**
     * set active sheet.
     *
     * @param int $index
     */
    public function setActiveSheetIndex($index)
    {
        if (is_object($this->_book)) {
            $this->_book->setActiveSheetIndex($index);
            $this->_sheet = $this->_book->getActiveSheet();
        } else {
            parent::setActiveSheetIndex($index);
            $this->_sheet = $this->getActiveSheet();
        }
    }

    /**
     * set value by cellname.
     *
     * @param int   $cell
     * @param mixed $value
     */
    public function setCellValue($cell, $value)
    {
        $this->_sheet->setCellValue($cell, $value);
    }

    /**
     * set value by column and row.
     *
     * @param int   $col
     * @param int   $row
     * @param mixed $value
     */
    public function setCellValueByColumnAndRow($col, $row, $value)
    {
        $this->_sheet->setCellValueByColumnAndRow($col, $row, $value);
    }

    /**
     * cell range.
     * 
     * @param int $colFrom
     * @param int $rowFrom
     * @param int $colTo
     * @param int $rowTo
     *
     * @return 
     */
    public function getRange($colFrom, $rowFrom, $colTo, $rowTo)
    {
        return PHPExcel_Cell::stringFromColumnIndex($colFrom).$rowFrom.':'.
               PHPExcel_Cell::stringFromColumnIndex($colTo).$rowTo;
    }

    /**
     * background color.
     *
     * @param object $cells
     * @param string $color
     */
    public function setBackGroundColor($cells, $color)
    {
        $fill = $this->_sheet->getStyle($cells)->getFill();
        $fill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $fill->getStartColor()->setRGB($color);
    }

    /**
     * border.
     *
     * @param string $pos
     * @param int    $type
     * @param string $color
     * @param object $cells
     */
    public function setBorder($cells, $pos, $type, $color)
    {
        $border = $this->_sheet->getStyle($cells)->getBorders()->$pos();
        $border->setBorderStyle($type);
        $border->getColor()->setRGB($color);
    }

    /**
     * alignment.
     *
     * @param object $cells
     * @param string $orient
     * @param int    $style
     */
    public function setAlign($cells, $orient = 'h', $style)
    {
        $align = $this->_sheet->getStyle($cells)->getAlignment();
        if ($orient === 'h') {
            $align->setHorizontal($style);
        } else {
            $align->setVertical($style);
        }
    }

    /**
     * font.
     *
     * @param object $cells
     * @param int    $size
     * @param string $name
     * @param string $color
     * @param bool   $bold
     * @param bool   $italic
     * @param bool   $underline
     * @param bool   $strikethrough
     */
    public function setFont($cells, $size = 10, $name = '', $color = '', $bold = false, $italic = false, $underline = false, $strikethrough = false)
    {
        $font = $this->_sheet->getStyle($cells)->getFont();
        $arr = array(
            'bold' => $bold,
            'italic' => $bold,
            'underline' => $underline,
            'strike' => $strikethrough,
        );
        $arr['size'] = $size;
        if (!empty($name)) {
            $arr['name'] = $name;
        }
        if (!empty($color)) {
            $arr['color'] = array('rgb' => $color);
        }
        $font->applyFromArray($arr);
    }

    /**
     * Parse row data.
     *
     * @param object $row
     *
     * @return array
     */
    public function parse($row)
    {
        $data = array();
        foreach ($row->getCellIterator() as $cell) {
            if (!is_null($cell)) {
                $data[] = $this->getCellValue($cell);
            }
        }

        return $data;
    }

    /**
     * Cell test.
     *
     * @param object $cell
     *
     * @return string
     */
    public function getCellValue($cell)
    {
        if (is_null($cell)) {
            return false;
        }
        $value = '';
        $valueCell = $cell->getValue();
        if (is_object($valueCell)) {
            $rte = $valueCell->getRichTextElements();
            foreach ($rte as $val) {
                $value .= $val->getText();
            }
        } else {
            if (!empty($valueCell)) {
                $value = $valueCell;
            }
        }

        return $value;
    }
}
