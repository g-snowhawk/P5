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
 * ImageMagick class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Image_Magick
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Image output extension.
     *
     * @var  
     */
    private $_ext;

    /**
     * Image output.
     *
     * @var  
     */
    private $_out;

    /**
     * Image input.
     *
     * @var  
     */
    private $_in;

    /**
     * JPEG Quality.
     *
     * @var  
     */
    private $_jpegQuality = 99;

    /**
     * PNG Quality.
     *
     * @var  
     */
    private $_pngQuality = 0;

    /**
     * Error message.
     *
     * @var string
     */
    private $_error;

    /**
     * Object constructer.
     */
    public function __construct()
    {
    }

    /** 
     * Trimming Image.
     *
     * @param string $source
     * @param string $dest
     * @param number $width
     * @param number $height
     * @param mixed  $offsetX
     * @param mixed  $offsetY
     * @param bool   $resizable
     * @param string $force     Image file extension.
     *
     * @return mixed
     */
    public function trimming($source, $dest, $width, $height, $offsetX = '50%', $offsetY = '50%', $resizable = true, $force = '')
    {
        $size = getimagesize($source);
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) {
            $copy = null;
        }

        // Resize
        if ($resizable) {
            $src_w = ($width > $height) ? $width : '';
            $src_h = ($width > $height) ? '' : $height;
            $opt = '';
            $command = "convert -resize {$src_w}x{$src_h}$opt $source $copy";
            $result = system($command);
            $source = $copy;
            $size = getimagesize($source);
        }
        $src_w = $width;
        $src_h = $height;

        if (is_numeric($offsetX)) {
            $src_x = $offsetX;
        } else {
            $shift = (preg_match('/^([0-9]+)%$/', $offsetX, $n)) ? $n[1] / 100 : 0.5;
            if ($shift > 1) {
                $shift = 1;
            }
            $src_x = round(($size[0] - $src_w) * $shift);
        }
        if (is_numeric($offsetY)) {
            $src_y = $offsetY;
        } else {
            $shift = (preg_match('/^([0-9]+)%$/', $offsetY, $n)) ? $n[1] / 100 : 0.5;
            if ($shift > 1) {
                $shift = 1;
            }
            $src_y = round(($size[1] - $src_h) * $shift);
        }
        if ($src_x < 0) {
            $src_x = 0;
        }
        if ($src_y < 0) {
            $src_y = 0;
        }
        if ($src_x + $src_w > $size[0]) {
            $src_x = $size[0] - $src_w;
        }
        if ($src_y + $src_h > $size[1]) {
            $src_y = $size[1] - $src_h;
        }

        $repage = ($this->_ext == 'gif') ? ' +repage' : '';
        $command = "convert -crop {$width}x{$height}+$src_x+$src_y$repage $source $copy";
        $result = system($command);

        return ($result) ? false : basename($copy);
    }

    /** 
     * Image resizing.
     *
     * @param string $source
     * @param string $dest
     * @param number $width
     * @param number $height
     * @param string $force        Image file extension.
     * @param bool   $notexpansion
     *
     * @return mixed
     */
    public function resize($source, $dest, $width, $height, $force = '', $notexpansion = false)
    {
        $size = getimagesize($source);
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) {
            $copy = null;
        }

        $opt = '!';

        if (!is_numeric($width)) {
            $width = '';
            $opt = '';
        }
        if (!is_numeric($height)) {
            $height = '';
            $opt = '';
        }
        if ($notexpansion == true) {
            $opt = '>';
        }

        $command = "convert -resize {$width}x{$height}$opt $source $copy";
        $result = system($command);

        return ($result) ? false : basename($copy);
    }

    /** 
     * Fixed aspect ratio resizing.
     *
     * @param string $source
     * @param string $dest
     * @param number $longer
     * @param string $force  Image file extension.
     *
     * @return mixed
     */
    public function ratio($source, $dest, $longer, $force = '')
    {
        $size = getimagesize($source);
        if ($size[0] > $size[1]) {
            $width = $longer;
            $height = null;
        } else {
            $width = null;
            $height = $longer;
        }

        return $this->resize($source, $dest, $width, $height, $force);
    }

    /**
     * Framing Image.
     *
     * @param string $source
     * @param string $dest
     * @param number $longer
     * @param number $margin
     * @param mixed  $rgb
     * @param string $force  Image file extension.
     *
     * @return bool
     */
    public function framein($source, $dest, $longer, $margin = 0, $rgb = array('R' => 0, 'G' => 0, 'B' => 0), $force = '')
    {
        $size = getimagesize($source);
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) {
            $copy = null;
        }

        if (is_string($rgb)) {
            $isTrans = strtolower($rgb) == 'transparent';
        }

        if ($isTrans) {
            $xc = 'none';
        } else {
            if (is_array($rgb)) {
                $r = (0 == $rgb['R']) ? '00' : dechex($rgb['R']);
                $g = (0 == $rgb['G']) ? '00' : dechex($rgb['G']);
                $b = (0 == $rgb['B']) ? '00' : dechex($rgb['B']);
                $xc = '#'.$r.$g.$b;
            } else {
                if (preg_match('/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/i', $rgb, $color)) {
                    $xc = $rgb;
                }
            }
        }
        $tmp = dirname($dest).'/tmp.png';
        $command = "convert -size {$longer}x{$longer} xc:'$xc' $tmp";
        $result = system($command);

        // Resize
        $inner_size = $longer - ($margin * 2);
        if ($size[0] > $size[1]) {
            if ($size[0] > $inner_size) {
                $width = $inner_size;
                $height = '';
            }
        } else {
            if ($size[1] > $inner_size) {
                $width = '';
                $height = $inner_size;
            }
        }

        $opt = '';
        $command = "convert -resize {$width}x{$height}$opt $source $copy";
        $result = system($command);

        $command = "convert $tmp $copy -gravity center -composite $copy";
        $result = system($command);

        return ($result) ? false : basename($copy);
    }

    /**
     * Get destination path.
     *
     * @param string $dest
     * @param string $ext
     * @param string $force
     *
     * @return string
     */
    public function destinationPath($dest, $force)
    {
        if (preg_match("/(.+)\.([a-z0-9]+)$/i", $dest, $match)) {
            $dest = $match[1];
            $allowExt = array('jpg', 'jpeg', 'gif', 'png');
            if (in_array(strtolower($match[2]), $allowExt)) {
                $this->_ext = $match[2];
            }
        }
        if (!empty($force)) {
            $this->_ext = $force;
        }

        return "$dest.{$this->_ext}";
    }

    /**
     * Error message.
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }
}
