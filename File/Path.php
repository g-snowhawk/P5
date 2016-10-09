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
 * File path class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_File_Path extends P5_File
{
    /**
     * Class Version Number.
     */
    const VERSION = '1.1.0';

    /**
     * Create new direstory.
     * 
     * @param  string   $path
     * @param  mixed    $mode
     * @return boolean
     */
    static public function mkpath($path, $mode = 0777)
    {
        $path = parent::replaceDirectorySeparator($path);
        if (file_exists($path)) return is_dir($path);
        $base = rtrim($path, DIRECTORY_SEPARATOR);
        $dirs = array();
        while (!file_exists($base)) {
            array_unshift($dirs, basename($base));
            $prev = $base;
            $base = dirname($base);
            if ($prev == $base) return false;
            if (file_exists($base) && !is_writable($base)) return false;
        }
        if ($base == DIRECTORY_SEPARATOR) $base = '';
        foreach ($dirs as $dir) {
            $base .= DIRECTORY_SEPARATOR . $dir;
            //if (false === mkdir($base, $mode)) return false;
            try {
                @mkdir($base, $mode);
            } catch(ErrorException $e) {
                if (preg_match("/File exists/", $e->getMessage())) continue;
            }
        }
        return is_dir($path);
    }
}
