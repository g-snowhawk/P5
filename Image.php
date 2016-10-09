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
 * PHP Version < 5.5
 * imageflip constant
 */
if (!defined('IMG_FLIP_HORIZONTAL')) { define('IMG_FLIP_HORIZONTAL', 1); }
if (!defined('IMG_FLIP_VERTICAL')) { define('IMG_FLIP_VERTICAL', 2); }
if (!defined('IMG_FLIP_BOTH')) { define('IMG_FLIP_BOTH', 3); }

/**
 * Image class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Image
{
    /**
     * Current version
     */
    const VERSION = '1.1.0';

    /**
     * Image output extension
     *
     * @var string
     */
    private $_ext;

    /**
     * Image MIME Type
     *
     * @var string
     */
    private $_mime;

    /**
     * Image output
     *
     * @var resource
     */
    private $_out;

    /**
     * Image input
     *
     * @var resource
     */
    private $_in;

    /**
     * JPEG Quality
     *
     * @var integer
     */
    private $_jpegQuality = 99;

    /**
     * PNG Quality
     *
     * @var integer
     */
    private $_pngQuality = 0;

    /**
     * Error message
     *
     * @var string
     */
    private $_error;

    /**
     * Object constructer
     *
     * @return void
     */
	public function __construct()
	{ }

    /** 
     * Trimming Image.
     *
     * @param  string   $source
     * @param  string   $dest
     * @param  number   $width
     * @param  number   $height
     * @param  mixed    $offsetX
     * @param  mixed    $offsetY
     * @param  boolean  $resizable
     * @param  mixed    $force          Image file extension.
     * @return mixed
     */
    public function trimming($source, $dest, $width, $height, $offsetX = '50%', $offsetY = '50%', $resizable = true, $force = null)
    {
        
        $size = getimagesize($source);
        if (false === $this->readImage($source, $size[2])) return false;
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) $copy = NULL;

        // Resize
        if ($resizable) {
            $a = $width / $height;
            $b = $size[0] / $size[1];
            if ($a > $b) {
                $src_w = $size[0];
                $src_h = round($size[0] / $a);
            } else {
                $src_w = round($size[1] * $a);
                $src_h = $size[1];
            }
        } else {
            $src_w = $width;
            $src_h = $height;
        }

        $dest_x = 0;
        $dest_y = 0;

        if (is_numeric($offsetX)) {
            $src_x = $offsetX;
        } else {
            $shift = (preg_match("/^([0-9]+)%$/", $offsetX, $n)) ? $n[1] / 100 : 0.5;
            if ($shift > 1) $shift = 1;
            $src_x = round(($size[0] - $src_w) * $shift);
        }
        if (is_numeric($offsetY)) {
            $src_y = $offsetY;
        } else {
            $shift = (preg_match("/^([0-9]+)%$/", $offsetY, $n)) ? $n[1] / 100 : 0.5;
            if ($shift > 1) $shift = 1;
            $src_y = round(($size[1] - $src_h) * $shift);
        }
        if ($src_x < 0) $src_x = 0;
        if ($src_y < 0) $src_y = 0;
        if ($src_x + $src_w > $size[0]) $src_x = $size[0] - $src_w;
        if ($src_y + $src_h > $size[1]) $src_y = $size[1] - $src_h;

        $this->newImage($width, $height);
        if ($resizable) {
            imagecopyresampled($this->_out, $this->_in, $dest_x, $dest_y, $src_x, $src_y, $width, $height, $src_w, $src_h);
        } else {
            imagecopy($this->_out, $this->_in, $dest_x, $dest_y, $src_x, $src_y, $width, $height);
        }
        $result = $this->writeImage($copy);
        imagedestroy($this->_out);
        imagedestroy($this->_in);
        return ($result) ? basename($copy) : false;
    }

    /** 
     * Image resizing.
     *
     * @param  string   $source
     * @param  string   $dest
     * @param  number   $width
     * @param  number   $height
     * @param  mixed    $force          Image file extension.
     * @param  boolean  $notexpansion
     * @return mixed
     */
    public function resize($source, $dest, $width, $height, $force = null, $notexpansion = false)
    {
        $size = getimagesize($source);
        if (false === $this->readImage($source, $size[2])) return false;
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) $copy = NULL; 

        // Resize
        $src_w = $size[0];
        $src_h = $size[1];

        if (!is_numeric($height)) {
            if ($notexpansion == true && $size[0] <= $width) {
                if ($copy) return  copy($source, $copy);
                echo file_get_contents($source); return;
            }
            $height = round($size[1] * ($width / $size[0]));
        }
        if (!is_numeric($width)) {
            if ($notexpansion == true && $size[1] <= $height) {
                if ($copy) return copy($source, $copy); 
                echo file_get_contents($source); return;
            }
            $width = round($size[0] * ($height / $size[1]));
        }

        $dest_x = 0;
        $dest_y = 0;
        $src_x = 0;
        $src_y = 0;

        $this->newImage($width, $height);
        imagecopyresampled($this->_out, $this->_in, $dest_x, $dest_y, $src_x, $src_y,
                           $width, $height, $src_w, $src_h);
        $result = $this->writeImage($copy);
        imagedestroy($this->_out);
        imagedestroy($this->_in);
        return ($result) ? basename($copy) : false;
    }

    /** 
     * Fixed aspect ratio resizing.
     *
     * @param  string   $source
     * @param  string   $dest
     * @param  number   $longer
     * @param  mixed    $force      Image file extension.
     * @return mixed
     */
    public function ratio($source, $dest, $longer, $force = null)
    {
        $size = getimagesize($source);
        if ($size[0] > $size[1]) {
            $width  = $longer;
            $height = NULL;
        } else {
            $width  = NULL;
            $height = $longer;
        }
        return $this->resize($source, $dest, $width, $height, $force);
    }

    /**
     * Framing Image.
     *
     * @param  string   $source
     * @param  string   $dest
     * @param  number   $longer
     * @param  number   $margin
     * @param  mixed    $rgb
     * @param  mixed    $force      Image file extension.
     * @return boolean
     */
    public function framein($source, $dest, $longer, $margin = 0, $rgb = array('R' => 0, 'G' => 0, 'B' => 0), $force = null) 
    {
        $size = getimagesize($source);
        if (! $this->readImage($source, $size[2])) return false;
        $copy = $this->destinationPath($dest, $force);
        if (empty($dest)) $copy = NULL;

        $isTrans = strtolower($rgb) == 'transparent';

        // Resize
        $inner_size = $longer - ($margin * 2);
        $src_w = $size[0];
        $src_h = $size[1];
        if ($size[0] > $size[1]) {
            if ($size[0] > $inner_size) {
                $width  = $inner_size;
                $height = round($size[1] * ($inner_size / $size[0]));
            }
        } else {
            if ($size[1] > $inner_size) {
                $width  = round($size[0] * ($inner_size / $size[1]));
                $height = $inner_size;
            }
        }
        $dest_x = round(($longer - $width)  / 2);
        $dest_y = round(($longer - $height) / 2);
        $src_x = 0;
        $src_y = 0;

        $this->newImage($longer, $longer);

        if (!$isTrans || preg_match("/jp*g/i", $this->_ext)) {
            if (!is_array($rgb)) {
                if (preg_match("/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/i", $rgb, $color)) {
                    $rgb = array('R' => hexdec($color[1]),
                                 'G' => hexdec($color[2]),
                                 'B' => hexdec($color[3]));
                } else {
                    $rgb = array('R' => 0, 'G' => 0, 'B' => 0);
                }
            }
            $background = imagecolorallocate($this->_out, $rgb['R'], $rgb['G'], $rgb['B']);
            imagefilledrectangle($this->_out, 0, 0, $longer, $longer, $background);
        }

        imagecopyresampled($this->_out, $this->_in, $dest_x, $dest_y, $src_x, $src_y, $width, $height, $src_w, $src_h);
        $result = $this->writeImage($copy);
        imagedestroy($this->_out);
        imagedestroy($this->_in);
        return ($result) ? basename($copy) : false;
    }

    /**
     * Check trancparency background.
     *
     * @param  string   $source
     * @param  number   $width
     * @param  number   $height
     * @return boolean
     */
    public function checkTransparency($source, $width, $height)
    {
        $transparency = NULL;
        for($sx = 0; $sx < $width; $sx++) {
            for($sy = 0; $sy < $height; $sy++) {
                $rgb   = imagecolorat($source, $sx, $sy);
                $index = imagecolorsforindex($source, $rgb);
                if($index["alpha"] !== 0){
                    $transparency = $index;
                    break;
                }
            }
            if($transparency !== NULL) break;
        }
        return $transparency;
    }

    /**
     * Create New Image.
     *
     * @param  number   $width
     * @param  number   $height
     * @return boolean
     */
    public function newImage($width, $height) 
    {
        $this->_out = imagecreatetruecolor($width, $height);
        imagecolortransparent($this->_out, imagecolorat($this->_out, 0, 0));
    }

    /**
     * Reading Image from source file.
     *
     * @param  string   $source
     * @param  number   $imagetype
     * @return boolean
     */
    public function readImage($source, $imagetype) 
    {
        switch ($imagetype) {
            case 1 : $this->_in = imagecreatefromgif($source);  
                     $this->_ext = "gif";
                     $this->_mime = "image/gif";
                     break;
            case 2 : $this->_in = imagecreatefromjpeg($source);
                     $this->_ext = "jpg"; 
                     $this->_mime = "image/jpeg";
                     break;
            case 3 : $this->_in = imagecreatefrompng($source);
                     $this->_ext = "png"; 
                     $this->_mime = "image/png";
                     break;
            default: $this->_error = P5_Lang::translate('OUT_OF_MIME');
                     return false;
        }
        return is_resource($this->_in);
    }

    /**
     * Write Image to File or STDOUT.
     *
     * @param  string   $path
     * @return boolean
     */
    public function writeImage($path)
    {
        $dir = dirname($path);
        if (is_writable($dir)) {
            switch ($this->_mime) {
                case 'image/gif'  : return imagegif ($this->_out, $path); 
                case 'image/jpeg' : return imagejpeg($this->_out, $path, $this->_jpegQuality); 
                case 'image/png'  : return imagepng ($this->_out, $path, $this->_pngQuality); 
                default           : throw new P5_Exception("{$this->_mime} isn't handled.");
            }
        } else throw new P5_Exception("$dir isn't writable.");
        return false;
    }

    /**
     * Get destination path
     *
     * @param  string   $dest       
     * @param  string   $ext
     * @param  mixed    $force
     * @return string
     */
    public function destinationPath($dest, $force)
    {
        if (preg_match("/(.+)\.([a-z0-9]+)$/i", $dest, $match)) {
            $dest = $match[1];
            $allowExt = array('jpg', 'jpeg', 'gif', 'png');
            if (in_array(strtolower($match[2]), $allowExt)) {
                $this->_ext  = $match[2];
            }
        }
		if (!is_null($force)) $this->_ext = $force;
        $ext = (empty($this->_ext)) ? '' : '.' . $this->_ext;
        return "$dest$ext";
    }

    /**
     * Error message
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }

    /**
     * Correct orientation
     *
     * @param string $file  Path to image file
     * @return void
     */
    static public function orientation($file)
    {
        if (empty($file)) return;
        $exif = false;
        try {
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($file);
            }
        } catch (ErrorException $e) {
            // Error Handler here.
            return;
        }
        if ($exif === false) return;
        $source = imagecreatefromjpeg($file);
        if (!isset($exif['Orientation'])) return;
        switch ($exif['Orientation']) {
            case 8 :
                $source = imagerotate($source, 90, 0);
                break;
            case 7 :
                $source = imagerotate($source, 90, 0);
                if (function_exists('imageflip')) imageflip($source, IMG_FLIP_HORIZONTAL);
                else self::imageflip($source, IMG_FLIP_HORIZONTAL);
                break;
            case 6 :
                $source = imagerotate($source, 270, 0);
                break;
            case 5 :
                $source = imagerotate($source, 270, 0);
                if (function_exists('imageflip')) imageflip($source, IMG_FLIP_HORIZONTAL);
                else self::imageflip($source, IMG_FLIP_HORIZONTAL);
                break;
            case 4 :
                if (function_exists('imageflip')) imageflip($source, IMG_FLIP_VERTICAL);
                else self::imageflip($source, IMG_FLIP_VERTICAL);
                break;
            case 3 :
                $source = imagerotate($source, 180, 0);
                break;
            case 2 :
                if (function_exists('imageflip')) imageflip($source, IMG_FLIP_HORIZONTAL);
                else self::imageflip($source, IMG_FLIP_HORIZONTAL);
                break;
            default :
                return;
        }
        imagejpeg($source, $file, 100);
    }

    /**
     * flip image
     *
     * @param resource $image
     * @param integer $mode
     * @return boolean
     */
    static public function imageflip(&$image, $mode)
    {
        $width  = imagesx($image);
        $height = imagesy($image);
        $flip = imagecreatetruecolor($width, $height);
        if($mode == IMG_FLIP_VERTICAL) {
            if (false === imagecopyresampled($flip, $image, 0, 0, 0, $height - 1, $width, $height, $width, -1 * $height))
                return false;
        }
        if($mode == IMG_FLIP_HORIZONTAL) {
            if (false === imagecopyresampled($flip, $image, 0, 0, $width - 1, 0, $width, $height, -1 * $width, $height))
                return false;
        }
        if($mode == IMG_FLIP_BOTH) {
            $flip = imagerotate($image, 180, 0);
        }
        $image = $flip;
        return true;
    }
}
