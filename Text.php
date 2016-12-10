<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
define('UTF32_BIG_ENDIAN_BOM', chr(0x00).chr(0x00).chr(0xFE).chr(0xFF));
define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE).chr(0x00).chr(0x00));
define('UTF16_BIG_ENDIAN_BOM', chr(0xFE).chr(0xFF));
define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE));
define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));

/**
 * Text class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Text
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Text wrapping.
     *
     * @param string $text
     * @param number $col
     * @param string $delimiter
     * @param string $enc
     *
     * @return string
     */
    public static function wrap($text, $col, $delimiter, $enc = 'utf-8')
    {
        $tmp = preg_split("/(\r\n|\r|\n)/", $text);
        $line = '';
        foreach ($tmp as $str) {
            $bytes = 0;
            for ($i = 0, $len = mb_strlen($str, $enc); $i < $len; ++$i) {
                $char = mb_substr($str, $i, 1, $enc);
                $bytes += mb_strwidth($char, $enc);
                if ($bytes > $col) {
                    $line .= $delimiter;
                    $bytes = mb_strwidth($char, $enc);
                }
                $line .= $char;
            }
            $line .= $delimiter;
        }
        $pattern = '/'.preg_quote($delimiter, '/').'$/';

        return preg_replace($pattern, '', $line);
    }

    /**
     * convert encoding.
     * 
     * @param string $str
     * @param string $encodingTo
     * @param string $encodingFrom
     *
     * @return string
     */
    public static function convert($str, $encodingTo = 'utf-8', $encodingFrom = null)
    {
        if (empty($encodingTo)) {
            $encodingTo = mb_internal_encoding();
        }
        $defaultSetting = mb_detect_order();
        mb_detect_order('ASCII, JIS, UTF-16, UTF-8, EUC-JP, SJIS-WIN, SJIS');
        if (is_array($str)) {
            try {
                mb_convert_variables($encodingTo, mb_detect_order(), $str);
            } catch (ErrorException $e) {
                // Fixed PHP7.0 Bugs
                if (stripos($e->getMessage(), 'Cannot handle recursive references') === false) {
                    return false;
                }
                foreach ($str as $n => $value) {
                    $str[$n] = self::convert($value, $encodingTo, $encodingFrom);
                }
            }
        } else {
            if (is_null($encodingFrom)) {
                $encodingFrom = preg_replace('/BOM$/', '', self::detect_encoding($str));
            }
            if (!empty($encodingTo) && !empty($encodingFrom) && $encodingTo != $encodingFrom) {
                $str = mb_convert_encoding($str, $encodingTo, $encodingFrom);
            }
        }
        // rewind setting
        mb_detect_order($defaultSetting);

        return $str;
    }

    /**
     * detect encoding.
     *
     * @param string $str
     *
     * @return string
     */
    public static function detect_encoding($str)
    {
        // Unicode
        $encoding = self::detect_utf_encoding($str);
        if (!empty($encoding)) {
            return $encoding;
        }

        $defaultSetting = mb_detect_order();
        mb_detect_order('ASCII, JIS, UTF-16BE, UTF-16LE, UTF-16, UTF-8, EUC-JP, SJIS');
        $encoding = mb_detect_encoding($str, mb_detect_order(), true);
        // rewind setting
        mb_detect_order($defaultSetting);

        return $encoding;
    }

    /**
     * implode array.
     * 
     * @return string
     */
    public static function implode()
    {
        $asset = func_get_args();
        $sep = array_shift($asset);

        return implode($sep, array_filter($asset));
    }

    /**
     * explode string.
     * 
     * @return array
     */
    public static function explode()
    {
        $asset = func_get_args();
        $sep = array_shift($asset);

        return preg_split("/[\s]*".preg_quote($sep, '/')."[\s]*/", $asset[0]);
    }

    /**
     * Detect UTF Encoding.
     * 
     * @param string $str
     *
     * @return string
     */
    public static function detect_utf_encoding($str)
    {
        $f2 = substr($str, 0, 2);
        $f3 = substr($str, 0, 3);
        $f4 = substr($str, 0, 3);

        if ($f3 == UTF8_BOM) {
            return 'UTF-8BOM';
        } elseif ($f4 == UTF32_BIG_ENDIAN_BOM) {
            return 'UTF-32BE';
        } elseif ($f4 == UTF32_LITTLE_ENDIAN_BOM) {
            return 'UTF-32LE';
        } elseif ($f2 == UTF16_BIG_ENDIAN_BOM) {
            return 'UTF-16BE';
        } elseif ($f2 == UTF16_LITTLE_ENDIAN_BOM) {
            return 'UTF-16LE';
        }
    }

    /**
     * Removing Unicode BOM.
     *
     * @param string $str
     * @param string $bom
     *
     * @return string
     */
    public static function removeBOM($str, $bom = UTF8_BOM)
    {
        return preg_replace('/^'.preg_quote($bom, '/').'/', '', $str);
    }

    /**
     * convert string to boolean.
     *
     * @param string $str
     *
     * @return bool
     */
    public static function convertBoolean($str)
    {
        $trues = array('true', 'yes', 'on', '1');

        return in_array(strtolower($str), $trues);
    }

    /**
     * HTML specialchars.
     *
     * @param string $str
     *
     * @return string
     */
    public static function htmlspecialchars($str)
    {
        $str = preg_replace('/&([a-z]+);/', '{$amp}'.'$1;', $str);
        $str = preg_replace('/&#([0-9]+);/', '{$amp}#'.'$1;', $str);
        $str = htmlspecialchars($str);
        $str = preg_replace('/'.preg_quote('{$amp}', '/').'/', '&', $str);

        return $str;
    }

    /**
     * supported encodings.
     *
     * @param string $enc
     *
     * @return mixed
     */
    public static function checkEncodings($enc)
    {
        $encodings = mb_list_encodings();
        foreach ($encodings as $encoding) {
            if (strtoupper($encoding) === strtoupper($enc)) {
                return $encoding;
            }
        }

        return false;
    }

    /**
     * check empty variables.
     *
     * @var mixed
     *
     * @return bool
     */
    public static function is_blank($var)
    {
        if ($var === 0 || $var === '0.0' || $var === '0') {
            return false;
        }

        return empty($var);
    }
}
