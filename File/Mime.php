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
 * Mime type class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_File_Mime
{
    /**
     * Mime types.
     *
     * @var array
     */
    private static $_types = array(
        'bmp' => 'image/bmp',
        'css' => 'text/css',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'swf' => 'application/x-shockwave-flash',
        'tiff' => 'image/tiff',
        'txt' => 'text/plain',
    );

    /**
     * Check mime by extension.
     *
     * @param string $key
     *
     * @return string
     */
    public static function type($key)
    {
        return (isset(self::$_types[$key])) ? self::$_types[$key]
                                            : 'application/octet-stream';
    }
}
