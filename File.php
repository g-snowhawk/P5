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
 * @copyright Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @version   $Id: File.php 2012-03-05 15:07:05 tak@ $
 */

/**
 * @category   P5
 * @package    P5_File
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_File
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Writting File 
     *
     * @param string $file
     * @param string $source
     * @param string $mode      File open mode
     * @return bool
     */
    static public function write($file, $source, $mode = 'w+b')
    {
        if (!is_writable(dirname($file))) {
            return false;
        }
        if (false !== $fh = fopen($file, $mode)) {
            if (false !== rewind($fh)) {
                if (false !== fwrite($fh, $source)) {
                    fflush($fh);
                    ftruncate($fh, ftell($fh));
                    return fclose($fh);
                }
            }
        }
        return false;
    }

    /**
     * Reading File 
     *
     * @param string $file
     * @return boolean
     */
    static public function read($file)
    {
        if (!file_exists($file)) {
            return;
        }
        $contents = '';
        if ($fh = fopen($file, 'rb')) {
            while (!feof($fh)) {
                $contents .= fread($fh, 8192);
            }
        }
        return $contents;
    }

    /**
     * Removing file as matching filter.
     *
     * @param string $dir
     * @param string $filter
     * @return bool
     */
    static public function rm($dir, $filter)
    {
        if (!is_dir($dir)) {
            return true;
        }
        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if ($file != "." && $file != "..") {
                    if (preg_match($filter, $file)) {  
                        unlink("$dir/$file");
                    }
                }
            }
        }
        return closedir($dh);
    }

    /**
     * Copying directories.
     *
     * @param string $dir
     * @param string $dest
     * @param number $mode
     * @param bool $recursive
     * @return bool
     */
    static public function copydir($dir, $dest, $mode = 0777, $recursive = false)
    {
        if (! is_dir($dest)) {
            if (false === P5_File_Path::mkpath($dest, $mode)) {
                return false;
            }
        }
        if ($dh = opendir($dir)) {
            while($file = readdir($dh)) {
                if ($file != '.' && $file != '..') {
                    $path = $dir . '/' . $file;
                    $new  = $dest . '/' . $file;
                    if(is_dir($path)) {
                        self::copydir($path, $new, $mode, $recursive);
                    } else {
                        if (false === copy($path, $new)) {
                            return false;
                        }
                    }
                }
            }
            closedir($dh);
            return true;
        }
        return false;
    }

    /**
     * Removing directories.
     *
     * @param string $dir
     * @param bool $recursive
     * @return bool
     */
    static public function rmdirs($dir, $recursive = false)
    {
        if (is_dir($dir)) {
            if ($recursive === true) {
                $dh = opendir($dir);
                while($file = readdir($dh)) {
                    if ($file != '.' && $file != '..') {
                        $path = $dir . '/' . $file;
                        if(is_dir($path)) {
                            self::rmdirs($path, $recursive);
                        } else {
                            unlink($path);
                        }
                    }
                }
                closedir($dh);
            }
            return rmdir($dir);
        }
        return true;
    }

    /**
     * Correct file path.
     *
     * @param string $path
     * @param mixed $separator
     * @return string
     */
    static public function realpath($path, $separator = null)
    {
        $isunc = (preg_match('/^\\\\/', $path)) ? true : false;
        $path = preg_replace('/[\/\\\]/', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        $path = preg_replace('/\/\.\//', '/', $path);
        $path = preg_replace('/\/[^\/]+\/\.\.\//', '/', $path);
        if (DIRECTORY_SEPARATOR == '/') {  // UNIX
            $path = preg_replace("/^[a-z]{1}:/i", "", $path);
        } else {  // Windows
            if ($isunc === true) {
                $path = DIRECTORY_SEPARATOR . $path;
            }
        }
        if (empty($separator)) {
            $separator = DIRECTORY_SEPARATOR;
        }
        return self::replaceDirectorySeparator($path, $separator);
    }

    /**
     * Replacing directory separator.
     *
     * @param  string   $path
     * @return string
     */
    static public function replaceDirectorySeparator($path, $separator = null) 
    {
        $pattern = '/(' . preg_quote('\\', '/') . '|' . preg_quote('/', '/') . ')/';
        if (empty($separator)) {
            $separator = DIRECTORY_SEPARATOR;
        }
        return preg_replace($pattern, $separator, $path);
    }

    /**
     * Getting file size.
     *
     * @param float $byte
     * @param int $dp       number of desimal place
     * @param bool $si
     * @return string
     */
    static public function size($byte = 0, $dp = 2, $si = true)
    {
        if ($si === true) {
            if ($byte < pow(10,  3)) return $byte . ' Byte';
            if ($byte < pow(10,  6)) return round($byte / pow(10,  3), $dp) . ' KB';
            if ($byte < pow(10,  9)) return round($byte / pow(10,  6), $dp) . ' MB';
            if ($byte < pow(10, 12)) return round($byte / pow(10,  9), $dp) . ' GB';
            if ($byte < pow(10, 15)) return round($byte / pow(10, 12), $dp) . ' TB';
            if ($byte < pow(10, 18)) return round($byte / pow(10, 15), $dp) . ' PB';
            if ($byte < pow(10, 21)) return round($byte / pow(10, 18), $dp) . ' EB';
            if ($byte < pow(10, 24)) return round($byte / pow(10, 21), $dp) . ' ZB';
            return round($byte / pow(10, 24), $dp) . ' YB';
        }
        if ($byte < pow(2, 10)) return $byte . ' Byte';
        if ($byte < pow(2, 20)) return round($byte / pow(2, 10), $dp) . ' KiB';
        if ($byte < pow(2, 30)) return round($byte / pow(2, 20), $dp) . ' MiB';
        if ($byte < pow(2, 40)) return round($byte / pow(2, 30), $dp) . ' GiB';
        if ($byte < pow(2, 50)) return round($byte / pow(2, 40), $dp) . ' TiB';
        if ($byte < pow(2, 60)) return round($byte / pow(2, 50), $dp) . ' PiB';
        if ($byte < pow(2, 70)) return round($byte / pow(2, 60), $dp) . ' EiB';
        if ($byte < pow(2, 80)) return round($byte / pow(2, 70), $dp) . ' ZiB';
        return round($byte / pow(2, 80), $dp) . ' YiB';
    }

    /**
     * Getting MIME Type
     *
     * @param  string   $path
     * @return string
     */
    static public function mime($path)
    {
        $tmp = explode('.', $path);
        $ext = array_pop($tmp);
        $mime = P5_File_Mime::type(strtolower($ext));
        return (empty($mime)) ? 'application/octet-stream' : $mime;
    }

    /**
     * Check existing file.
     *
     * @param string $path
     * @return bool
     */
    static public function fileExists($path)
    {
        if (!preg_match("/^([a-z]:|\/)/i", $path)) {
            return file_exists($path);
        }
        $inc_dirs = explode(PATH_SEPARATOR, ini_get('open_basedir'));
        if (empty($inc_dirs)) {
            return file_exists($path);
        }
        foreach ($inc_dirs as $dir) {
            $pattern = "/^" . preg_quote($dir, '/') . "/i";
            if (preg_match($pattern, $path)) {
                return file_exists($path);
            }
        }
        return false;
    }

    /**
     * Temporary Directory path.
     *
     * @return string
     */
    static public function tmpdir()
    {
        $dir = ini_get('upload_tmp_dir');
        if (empty($dir)) {
            $dir = sys_get_temp_dir();
        }
        return $dir;
    }
}
