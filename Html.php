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
 * @copyright Copyright (c) 2012-2014 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Html.php 2013-07-02 15:47:15 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Html
 * @copyright  Copyright (c) 2013 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Html
{
    /**
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Empty tag list
     * @var array
     */
    static $emptyTags = array
    (
        'area'=>'', 'base'=>'', 'basefont'=>'', 'br'=>'', 'frame'=>'', 'hr'=>'',
        'img'=>'', 'input'=>'', 'link'=>'', 'meta'=>'', 'param'=>''
    );

    /**
     * CDATA Tags
     *
     * @var array
     */
    protected $_cdataTags = array
    (
        'script'=>'', 'style'=>''
    );

    /**
     * Original Source
     *
     * @var string
     */
    protected $_orgSource = '';

    /**
     * escape entity reference
     *
     * @param  string   $source
     * @return string
     */
    static public function escapeEntityReference($source) 
    {
        $regex = array("/&([a-zA-Z]+);/", "/&#([0-9]+);/");
        $replace = array("[%:%$1%:%]", "[%:%#$1%:%]");
        return preg_replace($regex, $replace, $source);
    }

    /**
     * Rewind escape elements
     *
     * @param  string   $source
     * @return string
     */
    static public function rewindEntityReference($source) 
    {
        $regex = array("/\[%:%([a-zA-Z]+)%:%\]/", "/\[%:%#([0-9]+)%:%\]/");
        $replace = array("&$1;", "&#$1;");
        return preg_replace($regex, $replace, $source);
    }

    /**
     * escape comment elements
     *
     * @param  string   $source
     * @return string
     */
    static public function escapeComment($source) 
    {
        $regex = array("/([\s]*<!--)/s", "/(-->)/");
        $replace = array("<![CDATA[$1", "$1]]>");
        return preg_replace($regex, $replace, $source);
    }

    /**
     * escape script data
     *
     * @param  string   $source
     * @param  array    $tags
     * @return void
     */
    static public function escapeCdata($source, $tags = null)
    {
        $pattern = "/" . preg_quote('<![CDATA[', '/') . "/i";
        $source = preg_replace($pattern, "", $source);
        $pattern = "/" . preg_quote(']]>', '/') . "/i";
        $source = preg_replace($pattern, "", $source);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $pattern = "/(<" . preg_quote($tag, '/') . "[^>]*>)/i";
                $source = preg_replace($pattern, "$1<![CDATA[", $source);
                $pattern = "/(<\/" . preg_quote($tag, '/') . ">)/i";
                $source = preg_replace($pattern, "]]>$1", $source);
            }
        }
    }

    /**
     * Make pagenation source
     *
     * @param number $total
     * @param number $row
     * @param number $current
     * @param string $href
     * @param string $sep
     * @param mixed $col
     * @param bool $force
     * @param mixed $step
     * @param string $prev
     * @param string $next
     * @return string
     */
    static public function pager($total, $row, $current, $href, $sep = '', $col = null, $force = false, $step = null, $prev = '', $next = '') 
    {
        $current = (int)$current;
        $sum = ceil($total / $row);
        if ($sum == 1 && $force === false) {
            return '';
        }
        if (empty($col)) {
            $col = $sum;
        }
        $start = ($current < $col) ? 1 : $current - floor($col / 2);
        $end = $start + $col - 1;
        if ($end > $sum) {
            $end = $sum;
        }
        if ($end - $start < $col) {
            $start = $end - $col + 1;
        }
        if ($start < 1) {
            $start = 1;
        }
        $links = array();
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current) {
                array_push($links, "<strong>$i</strong>");
            } else {
                $link = preg_replace("/__PAGE__/", $i, $href);
                $anchor = '<a href="' . $link . '">' . $i . '</a>';
                array_push($links, $anchor);
            }
        }
        // Link for first page
        if ($step === true && $start > 1) {
            $link = preg_replace("/__PAGE__/", 1, $href);
            $anchor = '<a href="' . $link . '" class="first">1..</a>';
            array_unshift($links, $anchor);
        }
        // Link for last page
        if ($step === true && $end < $sum) {
            $link = preg_replace("/__PAGE__/", $sum, $href);
            $anchor = '<a href="' . $link . '" class="last">..' . $sum . '</a>';
            array_push($links, $anchor);
        }
        // Link for prev page
        if (!empty($prev)) {
            if($current != 1) {
                $link = preg_replace("/__PAGE__/", $current - 1, $href);
                $anchor = '<a href="' . $link . '" class="prev">' . htmlspecialchars($prev) . '</a>';
            } else {
                $anchor = '<span class="prev">' . htmlspecialchars($prev) . '</span>';
            }
            array_unshift($links, $anchor);
        }
        // Link for prev page
        if (!empty($next)) {
            if($current != $sum) {
                $link = preg_replace("/__PAGE__/", $current + 1, $href);
                $anchor = '<a href="' . $link . '" class="next">' . htmlspecialchars($next) . '</a>';
            } else {
                $anchor = '<span class="next">' . htmlspecialchars($next) . '</span>';
            }
            array_push($links, $anchor);
        }

        if (!empty($sep)) {
            $sep = '<span class="P5-separator">' . $sep . '</span>';
        }
        return implode($sep, $links);
    }

    /**
     * decode escaped HTML tags
     *
     * @param  string   $str
     * @param  array    $allowtags
     * @return string
     */
    static public function allowtag($str, array $allowtags)
    {
        foreach ($allowtags as $tag) {
            if (empty($tag)) {
                continue;
            }
            $str = preg_replace_callback("/&lt;($tag)(.*?)&gt;/i", 'P5_Html::retag', $str);
            $str = preg_replace("/&lt;\/$tag&gt;/i", "</$tag>", $str);
        }
        return $str;
    }

    /**
     * Replace HTML tag
     *
     * @param  array    $match
     * @return string
     */
    static public function retag($match)
    {
        $tag = strtolower($match[1]);
        $attr = preg_replace("/&quot;/", '"', $match[2]);
        $slash = (isset(self::$emptyTags[$tag])) ? ' /' : '';
        return "<$tag$attr$slash>";
    }

    /**
     * Paragraph
     *
     * @param  string   $str
     * @param  boolean  $plain
     * @return string
     */
    static public function paragraph($str, $plain = false)
    {
        if (empty($str)) {
            return '';
        }
        $str = preg_replace("/(\r\n|\r)/", "\n", $str);
        $str = preg_replace("/<br[^>]*>/i", "\n", $str);
		$blocks = '(H[1-6R]|P|DIV|ADDRESS|PRE|FORM|T(ABLE|BODY|HEAD|FOOT|H|R|D)|LI|OL|UL|CAPTION|BLOCKQUOTE|CENTER|DL|DT|DD|DIR|FIELDSET|NOSCRIPT|MENU|ISINDEX|SAMP)';
        $str = preg_replace("/<\/$blocks>[\s]?<$blocks([^>]*)>/is", "</$1><$2$3>", $str);
        $str = preg_replace("/<$blocks([^>]*)>/is", "\n\n<$1$2>", $str);
        $str = preg_replace("/(^[\s]+|[\s]+$)/s", "", $str);
        $paragraphs = preg_split("/\n{2}/", $str);
        $src = '';
        $class = '';
        for ($i = 0, $len = count($paragraphs); $i < $len; $i++) {
            $paragraph = $paragraphs[$i];
            if (empty($paragraph)) {
                $paragraph = "&nbsp;";
            }
            if ($i == 0 && $plain == false) {
                $class = ' class="at-first"';
            }
            if ($i == $len - 1 && $plain == false) {
                $class = ' class="at-last"';
            }
            if ($len == 1 && $plain == false) {
                $class = ' class="at-first at-last"';
            }
            if (preg_match("/^<$blocks([^>]*)>/is", $paragraph)) {
                $src .= $paragraph;
            } else {
                $paragraph = preg_replace("/\n/", "<br />\n", $paragraph);
                $src .= "<p$class>$paragraph</p>\n";
            }
            $class = '';
        }
        return $src;
    }

    /**
     * Convert HTML to XML, Replace empty tags.
     *
     * @param string $source
     * @param bool $ishtml
     * @return string
     */
    static public function htmlToXml($source, $ishtml = false) 
    {
        if ($ishtml) {
            $source = preg_replace_callback("/<([A-Z]+)(([\s]+[^>]+)?)>/", 'self::_opTag', $source);
            $source = preg_replace_callback("/<\/([A-Z]+)>/", 'self::_clTag', $source);
        }
        foreach (self::$emptyTags as $tag => $value) {
            // continue when finded close tags.
            $pattern = "/<\/" . preg_quote($tag, '/') . ">/i";
            if (preg_match($pattern, $source)) {
                continue;
            }
            // No Attributes
            $pattern = "/<(" . preg_quote($tag, '/') . ")>/i";
            $source = preg_replace($pattern, "<$1 />", $source);
            // has Attributes
            $pattern = "/<(" . preg_quote($tag, '/') . ")[\s]+([^>]*)>/i";
            $source = preg_replace($pattern, "<$1 $2/>", $source);
        }
        $source = preg_replace("/\/\/>/", "/>", $source);
        return $source;
    }

    /** 
     * Replace open tag
     *
     * @param array $tags
     * @return string
     */
    static private function _opTag($tags) 
    {
        return "<" . strtolower($tags[1]) . $tags[2] . ">";
    }

    /** 
     * Replace close tag
     *
     * @param array $tags
     * @return string
     */
    static private function _clTag($tags) 
    {
        return "</" . strtolower($tags[1]) . ">";
    }

    /**
     * convert source encoding
     *
     * @param string $source
     * @param string $enc
     * @return string
     */
    static public function convertEncoding($source, $enc)
    {
        if (empty($enc)) {
            if (false !== $charset = self::metaCheckCharset($source)) {
                $enc = (empty($charset)) ? 'none' : $charset;
            }
        }
        switch (strtolower($enc)) {
            case 'x-sjis' :
                $enc = 'Shift_JIS';
            case 'shift_jis' :
                $encTo = 'SJIS';
                break;
            case 'gb2312' :
                $encTo = 'EUC-CN';
                break;
            case 'none' :
                $encTo = 'HTML-ENTITIES';
                $enc = '';
                break;
            default :
                $encTo = $enc;
                break;
        }
        $encTo = P5_Text::checkEncodings($encTo);
        $encFrom = mb_internal_encoding();
        if (strtolower($encTo) != $encFrom) {
            if (!empty($encTo)) {
                $source = mb_convert_encoding
                (
                    self::replaceXmlEncoding
                    (
                        self::replaceHtmlCharset($source, $enc),
                        $enc
                    ),
                    $encTo, $encFrom
                );
            }
        }
        return $source;
    }

    /**
     * Replace XML encoding
     *
     * @param string $source
     * @param string $enc
     * @return string
     */
    static public function replaceXmlEncoding($source, $enc)
    {
        $pattern = "/<\?xml[\s]+version\s*=\s*[\"']?([0-9\.]+)[\"']?[\s]+encoding=[\"']?[0-9a-z-_]+[\"']?\s*\?" . ">/i";
        $attr = (empty($enc)) ? '' : " encoding=\"{$enc}\"";
        $replace = '<?xml version="$1"' . $attr . '?' . '>';
        return preg_replace($pattern, $replace, $source);
    }

    /**
     * Replace HTML charset
     *
     * @param string $source
     * @param string $enc
     * @return string
     */
    static public function replaceHtmlCharset($source, $enc)
    {
        $pattern = "/<meta [^>]*http-equiv\s*=\s*[\"']?content-type[\"']?[^>]*?(\/?)>/i";
        $attr = (empty($enc)) ? '' : "; charset={$enc}";
        $replace = '<meta http-equiv="Content-type" ' .
                   'content="text/html' . $attr . '"$1>';
        return preg_replace($pattern, $replace, $source);
    }

    /**
     * escape script data
     *
     * @return void
     */
    protected function _escapeCdata()
    {
        foreach ($this->_cdataTags as $tag => $value) {
            // Enpty tag 
            $pattern = "/(<" . preg_quote($tag, '/') . "[^>]*)\/>/i";
            $this->_orgSource = preg_replace($pattern, "$1></$tag>", $this->_orgSource);

            $pattern = "/(<" . preg_quote($tag, '/') . "[^>]*>)/i";
            $this->_orgSource = preg_replace($pattern, "$1<![CDATA[", $this->_orgSource);

            $pattern = "/(<\/" . preg_quote($tag, '/') . ">)/i";
            $this->_orgSource = preg_replace($pattern, "]]>$1", $this->_orgSource);
        }

        $pattern = "/" . preg_quote('<![CDATA[', '/') . "[\s]*?" . preg_quote('<![CDATA[', '/') . "/is";
        $this->_orgSource = preg_replace($pattern, "<![CDATA[", $this->_orgSource);
        $pattern = "/" . preg_quote(']]>', '/') . "[\s]*?" . preg_quote(']]>', '/') . "/is";
        $this->_orgSource = preg_replace($pattern, "]]>", $this->_orgSource);
    }

    /**
     * Style attribute 
     *
     * @param array $styles
     * @param string $selector
     * @return string
     */
    static public function styleAttr($styles, $selector = null)
    {
        $arr = array();
        foreach ($styles as $key => $value) {
            if (!empty($value)) {
                $arr[] = htmlspecialchars($key) . ':' . htmlspecialchars($value);
            }
        }
        if (empty($arr)) {
            return '';
        }
        if (is_null($selector)) {
            return 'style="' . implode(';', $arr) . ';"';
        } else {
            return $selector . ' { ' . implode(';', $arr) . '; }';
        }
    }

    /**
     * check caractorset
     *
     * @param string $source
     * @return mixed
     */
    static public function metaCheckCharset($source)
    {
        $pattern = "/<meta ([^>]*)http-equiv\s*=\s*[\"']?content-type[\"']?([^>]*)(\/?)>/i";
        if (preg_match($pattern, $source, $match)) {
            foreach ($match as $reg) {
                if (preg_match("/charset\s*=\s*([0-9a-z_-]+)/i", $reg, $cs)) {
                    return $cs[1];
                }
            }
            return '';
        }
        return false;
    }
}
