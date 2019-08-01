<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016-2019 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */
namespace P5\Xml {

use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * HTML source to DOM class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class Html extends Dom
{
    /**
     * Name space.
     */
    const NAMESPACE_URI = 'http://www.plus-5.com/xml';

    /**
     * Empty tag list.
     *
     * @var array
     */
    public static $emptyTags = array(
        'area' => '', 'base' => '', 'basefont' => '', 'br' => '',
        'frame' => '', 'hr' => '', 'img' => '', 'input' => '',
        'link' => '', 'meta' => '', 'param' => '',
    );

    /**
     * CDATA Tags.
     *
     * @var array
     */
    protected $_cdataTags = array(
        'script' => '', 'style' => '',
    );

    /**
     * Original Characterset.
     *
     * @var string
     */
    private $charset = '';

    /**
     * Original Source.
     *
     * @var string
     */
    protected $orgSource = '';

    /**
     * CDATA Tags.
     *
     * @var array
     */
    protected $cdataTags = array('script', 'style');

    /**
     * XSS Protection.
     *
     * @var bool
     */
    protected $xssProtection = 1;

    /**
     * Template file path
     *
     * @var string
     */
    public $template_path;

    /**
     * Object constructor.
     *
     * @param mixed $template
     * @param bool  $ishtml
     */
    public function __construct($template, $ishtml = false)
    {
        $source = (is_file($template)) ? file_get_contents($template) : $template;

        // Append Namespace for P5 tags.
        if (preg_match('/<P5:[^>]+>/', $source)) {
            $ns = self::NAMESPACE_URI;
            if (preg_match('/<(html|dummy)/i', $source)) {
                $source = preg_replace('/<(html|dummy)/i', "<$1 xmlns:P5=\"$ns\"", $source);
            } elseif (preg_match('/<(\?xml|!doctype)/i', $source)) {
                $source = preg_replace('/<([a-z0-9:]+)/i', "<$1 xmlns:P5=\"$ns\"", $source, 1);
            } else {
                $source = "<dummy xmlns:P5=\"$ns\">$source</dummy>";
            }
        }

        $source = $this->setCharset($source);
        $source = self::htmlToXml($source, $ishtml);
        $this->orgSource = $source;
        $this->inEscapeCdata();

        // Call parent constructor.
        parent::__construct($this->orgSource);

        self::setIdAttrs($this->dom);

        $this->dom->registerNodeClass('DOMElement', 'HTMLElement');
    }

    /**
     * Append X-UA-Compatible.
     *
     * @param object $html
     */
    public function insertXUACompatible($meta = false)
    {
        $content = '';
        if (preg_match("/MSIE ([0-9\.]+);/", \P5\Environment::server('HTTP_USER_AGENT'), $ver)) {
            $version = (int) $ver[1];
            if ($version >= 7) {
                $content .= 'IE7';
            }
            if ($version >= 8) {
                $content .= '; IE8';
            }
            if ($version >= 9) {
                $content .= '; IE9';
            }
        }
        if (!empty($content)) {
            if ($meta == true) {
                $this->insertMetaData('', $content, 'X-UA-Compatible');
            } else {
                \P5\Http::responseHeader('X-UA-Compatible', $content);
            }
        }
    }

    /**
     * DOMDocument to string.
     *
     * @param mixed  $noDecl
     * @param mixed  $noDtd
     * @param bool   $noFormat
     * @param string $enc
     * @return string
     */
    public function toString($noDecl = null, $noDtd = null, $noFormat = false, $enc = null)
    {
        $html = '';
        $rootNode = $this->dom->documentElement;
        if (!is_object($rootNode)) {
            return $html;
        }
        switch ($rootNode->nodeName) {
            case 'html' :
                $this->moveHeaderElements();
            case 'rdf'  :
            case 'rss'  :
            case 'feed' :
                $rootNode->removeAttributeNS(self::NAMESPACE_URI, 'P5');
                if (empty($this->dom->encoding)) {
                    $this->dom->encoding = 'UTF-8';
                }
                $html = $this->dom->saveHTML();
                break;
            default :
                $rootNodes = $this->dom->childNodes;
                if (is_object($rootNodes)) {
                    foreach ($rootNodes as $node) {
                        if ($node->nodeType === XML_ELEMENT_NODE) {
                            $node->removeAttributeNS(self::NAMESPACE_URI, 'P5');
                            if ($node->nodeName === 'dummy') {
                                $children = $node->childNodes;
                                foreach ($children as $child) {
                                    $html .= $this->dom->saveXML($child);
                                }
                                continue;
                            }
                        }
                        $html .= $this->dom->saveXML($node);
                    }
                }
        }

        $source = self::rewindEntityReference($html);

        if ($noFormat === false) {
            $formatted = new \P5\Html\Format();
            $source = $formatted->start($source);
        }

        if (is_null($enc)) {
            return $source;
        }

        return \P5\Html::convertEncoding($source, $enc);
    }

    /**
     * Get elements.
     *
     * @param string $id
     * @param string $attr
     * @return mixed
     */
    public function getElementById($id, $attr = 'id')
    {
        return $this->dom->getElementById($id);
    }

    public function querySelector($query, $parent = null)
    {
        $node_list = $this->querySelectorAll($query, $parent);

        return $node_list->item(0);
    }

    public function querySelectorAll($query, $parent = null)
    {
        $xpath = new DOMXPath($this->dom);

        if (empty($parent)) {
            $parent = $this->dom->documentElement;
        }

        return $xpath->query($query, $parent);
    }

    /**
     * Getting HTML element by tag name.
     *
     * @param string $name
     * @param object $parent
     * @return mixed
     */
    public function getElementByName($name, $parent = null)
    {
        return $this->querySelector('.//*[@name="'.$name.'"]' , $parent);
    }

    /**
     * Getting HTML element by tag name.
     *
     * @param string $name
     * @param object $parent
     * @return mixed
     */
    public function getElementsByName($name, $parent = null)
    {
        return $this->querySelectorAll('.//*[@name="'.$name.'"]' , $parent);
    }

    /**
     * Get elements by classname.
     *
     * @param string $id
     * @param object $id
     * @return mixed
     */
    public function getElementsByClassName($class, $parent = null)
    {
        $query = sprintf('.//*[contains(@class,"%s")]',$class);
        $tmp = $this->querySelectorAll($query, $parent);
        $nodes = array();
        foreach ($tmp as $node) {
            $attribute = $node->getAttribute('class');
            $classes = explode(' ', $attribute);
            if (in_array($class, $classes)) {
                $nodes[] = $node;
            }
        }

        return new \P5\Xml\Dom\NodeList($nodes);
    }

    /**
     * get elements by attirbute.
     *
     * @param object $node
     * @param string $attr
     * @param string $value
     * @param array  $arr
     */
    private function getElementsByAttr($node, $attr, $value, &$arr)
    {
        return $this->querySelectorAll('.//*[@'.$attr.'="'.$value.'"]' , $node);
    }

    /**
     * Insert base tag.
     *
     * @param string $url
     * @return bool
     */
    public function insertBaseTag($url)
    {
        $head = $this->dom->getElementsByTagName('head')->item(0);
        if (!is_object($head)) {
            return;
        }
        $base = $this->dom->createElement('base');
        $base->setAttribute('href', $url);

        return $head->insertBefore($base, $head->firstChild);
    }

    /**
     * Insert meta tag.
     *
     * @param string $name
     * @param string $content
     * @param string $attr
     * @return bool
     */
    public function insertMetaData($name, $content, $httpEquiv = '')
    {
        $head = $this->dom->getElementsByTagName('head')->item(0);
        if (!is_object($head)) {
            return;
        }

        $after = null;
        $key = '';
        $meta = $this->getElementsByTagName('meta');
        if ($meta->length === 0) {
            $meta = $this->dom->createElement('meta');
            $meta->setAttribute('http-equiv', 'Content-Type');
            $meta->setAttribute('content', 'text/html');
            $head->insertBefore($meta, $head->firstChild);
            $meta = $this->getElementsByTagName('meta');
        }
        for ($i = 0; $i < $meta->length; ++$i) {
            if (empty($name) && !empty($httpEquiv)) {
                $key = 'http-equiv';
                $value = $httpEquiv;
                if ($meta->item($i)->getAttribute($key)) {
                    $after = $meta->item($i);
                    if ($after->getAttribute($key) == $httpEquiv) {
                        return $after->setAttribute('content', $content);
                    }
                }
            } else {
                $key = 'name';
                $value = $name;
                if ($meta->item($i)->getAttribute($key)) {
                    $after = $meta->item($i);
                    if ($after->getAttribute($key) == $name) {
                        return $after->setAttribute('content', $content);
                    }
                }
            }
        }

        if ($key) {
            $meta = $this->dom->createElement('meta');
            $meta->setAttribute($key, $value);
            $meta->setAttribute('content', $content);
        }

        if (!is_object($after)) {
            $after = $head->getElementsByTagName('title')->item(0);
        } else {
            $after = $after->nextSibling;
        }

        return $head->insertBefore($meta, $after);
    }

    /**
     * Insert link tag.
     *
     * @param string $href
     * @param string $rel   (optional)
     * @param string $rev   (optioanl)
     * @param array  $attrs (optioanl)
     * @return bool
     */
    public function insertLink($href, $rel = null, $rev = null, $attrs = array())
    {
        if (is_object($href)) {
            $link = $href;
        } else {
            $link = $this->dom->createElement('link');
            if (!empty($rel)) {
                $link->setAttribute('rel',  $rel);
            }
            if (!empty($rev)) {
                $link->setAttribute('rev',  $rev);
            }
            foreach ($attrs as $key => $value) {
                $link->setAttribute($key, $value);
            }
            $link->setAttribute('href', $href);
        }

        $head = $this->dom->getElementsByTagName('head')->item(0);
        if (!is_object($head)) {
            return;
        }

        $refNode = $head->getElementsByTagName('link');
        if (!$refNode) {
            $title = $head->getElementsByTagName('title')->item(0);
            $refNode = $title->nextSibling;
        } else {
            foreach ($refNode as $element) {
                if ($element->getAttribute('href') === $href) {
                    return $element;
                }
            }
            $i = (int) $refNode->length - 1;
            $lastChild = $refNode->item($i);
            $refNode = $lastChild->nextSibling;
            if (is_object($refNode)) {
                return $head->insertBefore($link, $refNode);
            }
        }

        return $head->appendChild($link);
    }

    /**
     * Insert script tags.
     *
     * @param string $src
     * @param mixed  $index
     * @return bool
     */
    public function insertScript($src, $index = null, $place = '')
    {
        $tag = (stripos($place, 'body') !== false) ? 'body' : 'head';
        $parent = $this->dom->getElementsByTagName($tag)->item(0);
        if (!is_object($parent)) {
            return false;
        }

        $nodes = $this->importChild($src);

        $scripts = $parent->getElementsByTagName('script');
        if ($scripts->length > 0) {
            $refNode = (is_null($index)) ? $scripts->item($scripts->length - 1)->nextSibling
                                         : $scripts->item($index);
        }

        $error = 0;
        if (isset($refNode) && is_object($refNode)) {
            foreach ($nodes as $node) {
                if (!$parent->insertBefore($node, $refNode)) {
                    ++$error;
                }
            }
        } else {
            foreach ($nodes as $node) {
                if (!$parent->appendChild($node)) {
                    ++$error;
                }
            }
        }

        return $error == 0;
    }

    /**
     * DOMDocument to string.
     *
     * @return string
     */
    public function coat()
    {
        $_blockLevelTags = array(
            'address',
            'blockquote',
            'center',
            'div',
            'dl', 'dt', 'dd',
            'fieldset',
            'form',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'hr',
            'li',
            'noframes',
            'ol',
            'p',
            'pre',
            'table', 'tr', 'td',
            'ul',
        );
        $html = '';

        $elm = $this->dom->getElementsByTagName('html');
        if ($elm && $elm->item(0)) {
            // NooP
        } else {
            $rootNodes = $this->dom->childNodes;
            if (is_object($rootNodes)) {
                foreach ($rootNodes as $node) {
                    if ($node->nodeType == XML_ELEMENT_NODE) {
                        $node->removeAttributeNS(self::NAMESPACE_URI, 'P5');
                        if ($node->nodeName === 'dummy') {
                            $children = $node->childNodes;
                            $inner = '';
                            foreach ($children as $child) {
                                if (in_array(strtolower($child->nodeName), $_blockLevelTags)) {
                                    if (!empty($inner)) {
                                        $inner = preg_replace("/(\r\n|\r|\n)$/", '', $inner);
                                        $html .= preg_replace("/(\r\n|\r|\n)/", '<br />$1', $inner).'</p>';
                                        $inner = '';
                                    }
                                    $html .= $this->dom->saveXML($child);
                                } else {
                                    if (empty($inner)) {
                                        $inner = preg_replace("/^(\r\n|\r|\n)/", '', $this->dom->saveXML($child), 1);
                                        $inner = '<p>'.$inner;
                                        continue;
                                    }
                                    $inner .= $this->dom->saveXML($child);
                                }
                            }
                            if (!empty($inner)) {
                                $html .= $inner.'</p>';
                                $inner = '';
                            }
                            continue;
                        }
                    }
                    $html .= $this->dom->saveXML($node);
                }
            }
        }

        return $html;
    }

    /**
     * Get characterset.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Set Original Characterset.
     *
     * @param string $source
     */
    public function setCharset($source)
    {
        $this->charset = '';
        $pattern = "/<meta ([^>]*)http-equiv\s*=\s*[\"']?content-type[\"']?([^>]*)(\/?)>/i";
        $replace = '<meta http-equiv="Content-type" content="text/html;charset=UTF-8"$3>';
        if (preg_match($pattern, $source, $match)) {
            foreach ($match as $reg) {
                if (preg_match("/charset\s*=\s*([0-9a-z_-]+)/i", $reg, $cs)) {
                    if (strtolower($cs[1]) !== 'utf-8' && strtolower($cs[1]) !== 'utf') {
                        $this->charset = $cs[1];
                        $source = preg_replace($pattern, $replace, $source);
                        break;
                    } else {
                        $this->charset = 'UTF-8';
                    }
                }
            }
        }

        return $source;
    }

    /**
     * change caracterset.
     *
     * @param string $charset
     */
    public function changeCharset($charset)
    {
        $meta = $this->dom->getElementsByTagName('meta');
        foreach ($meta as $node) {
            if ($node->hasAttribute('http-equiv') &&
                strtolower($node->getAttribute('http-equiv')) === 'content-type'
            ) {
                $attr = ($charset !== '') ? "; charset={$charset}" : '';
                $node->setAttribute('content', "text/html{$attr}");
                $this->charset = $charset;
            }
        }
    }

    /** 
     * Require Public Idenfifer Callback to PregReplace.
     *
     * @param array $args
     * @return string
     */
    public function setPi($args)
    {
        $unit = preg_split("/\s+/", $args[1]);
        $systemId = '';
        if (preg_match("/HTML\s+PUBLIC\s+\"([^\"]+)\"/i", $args[1], $unit)) {
            switch (strtolower($unit[1])) {
                case '-//w3c//dtd html 4.01//en' :
                    $systemId = 'http://www.w3.org/TR/html4/strict.dtd';
                    break;
                case '-//w3c//dtd html 4.01 transitional//en' :
                    $systemId = 'http://www.w3.org/TR/html4/loose.dtd';
                    break;
                case '-//w3c//dtd html 4.01 frameset//en' :
                    $systemId = 'http://www.w3.org/TR/html4/frameset.dtd';
                    break;
                case '-//w3c//dtd xhtml 1.0 strict//en' :
                    $systemId = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';
                    break;
                case '-//w3c//dtd xhtml 1.0 transitional//en' :
                    $systemId = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';
                    break;
                case '-//w3c//dtd xhtml 1.0 frameset//en' :
                    $systemId = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd';
                    break;
                case '-//w3c//dtd xhtml 1.1//en' :
                    $systemId = 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';
                    break;
                default :
                    $systemId = '';
            }
        }

        return '<!DOCTYPE '.$args[1].' "'.$systemId.'">';
    }

    /** 
     * Insert Hidden Element.
     *
     * @param string $formID
     * @param string $name
     * @param string $value
     */
    public function insertHidden($formID, $name, $value = '')
    {
        $form = $this->getElementById($formID);
        if (!is_object($form)) {
            return;
        }

        $hidden = null;
        $inputs = $form->getElementsByTagName('input');
        foreach ($inputs as $input) {
            if ($input->getAttribute('name') === $name
                && false === strpos($name, '[]')
            ) {
                $hidden = $input;
                break;
            }
        }
        if (!is_null($hidden)) {
            $hidden->setAttribute('value', $value);
        } else {
            $src = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            $hidden = $this->appendChild($src, $form);
        }

        return $hidden;
    }

    /**
     * Move Head Element in Body.
     * 
     * return void
     */
    public function moveHeaderElements()
    {
        $tags = array('style', 'link', 'meta', 'title', 'script');
        $body = $this->dom->getElementsByTagName('body')->item(0);
        $head = $this->dom->getElementsByTagName('head')->item(0);
        foreach ($tags as $tag) {
            $elements = $body->getElementsByTagName($tag);
            $exists = $head->getElementsByTagName($tag);
            for ($i = 0, $max = $elements->length; $i < $max; ++$i) {
                $element = $elements->item(0);
                if ($tag === 'script') {
                    if (true !== $element->hasAttribute('src') || $element->getAttribute('class') === 'stay') {
                        continue;
                    }
                }
                if (is_object($exists) && $exists->length > 0) {
                    $last = $exists->item(($exists->length - 1))->nextSibling;
                    if (is_object($last)) {
                        $head->insertBefore($element, $last);
                        continue;
                    }
                    $head->appendChild($element);
                }
            }
        }
    }

    /**
     * escape script data.
     */
    protected function inEscapeCdata()
    {
        foreach ($this->cdataTags as $tag) {
            // Enpty tag 
            $pattern = '/(<'.preg_quote($tag, '/')."[^>]*)\/>/i";
            $this->orgSource = preg_replace($pattern, "$1></$tag>", $this->orgSource);

            $pattern = '/(<'.preg_quote($tag, '/').'[^>]*>)/i';
            $this->orgSource = preg_replace($pattern, '$1<![CDATA[', $this->orgSource);

            $pattern = "/(<\/".preg_quote($tag, '/').'>)/i';
            $this->orgSource = preg_replace($pattern, ']]>$1', $this->orgSource);
        }

        $pattern = '/'.preg_quote('<![CDATA[', '/')."[\s]*?".preg_quote('<![CDATA[', '/').'/is';
        $this->orgSource = preg_replace($pattern, '<![CDATA[', $this->orgSource);
        $pattern = '/'.preg_quote(']]>', '/')."[\s]*?".preg_quote(']]>', '/').'/is';
        $this->orgSource = preg_replace($pattern, ']]>', $this->orgSource);
    }

    /**
     * Set XSS Protection.
     *
     * @param int $value
     */
    public function setXssProtection($value = 1)
    {
        $this->xssProtection = (int) $value;
    }

    /**
     * Using getElementById.
     * 
     * @param object $node
     * @param string $attr
     */
    public static function setIdAttrs($node, $attr = 'id')
    {
        if (method_exists($node, 'item')) {
            foreach ($node as $cn) {
                self::setIdAttrs($cn, $attr);
            }
        } else {
            if ($node->hasAttributes()) {
                if ($node->hasAttribute($attr)) {
                    // Important
                    if (false === $node->getAttributeNode($attr)->isID()) {
                        $node->setIdAttribute($attr, true);
                    }
                }
            }
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $cn) {
                    self::setIdAttrs($cn, $attr);
                }
            }
        }
    }

    /**
     * Append child node.
     *
     * @param mixed  $node    Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function appendChild($node, $refNode)
    {
        $new = parent::appendChild($node, $refNode);
        if (is_object($new)) {
            self::setIdAttrs($new);
        }

        return $new;
    }

    /**
     * Replace child node.
     *
     * @param mixed  $node    Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function replaceChild($node, $refNode)
    {
        $new = parent::replaceChild($node, $refNode);
        if (is_object($new)) {
            self::setIdAttrs($new);
        }

        return $new;
    }

    /**
     * Insert child node.
     *
     * @param mixed  $node    Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function insertBefore($node, DOMNode $refNode)
    {
        $new = parent::insertBefore($node, $refNode);
        if (is_object($new)) {
            self::setIdAttrs($new);
        }

        return $new;
    }

    /**
     * Insert after child node.
     *
     * @param mixed  $node    Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function insertAfter($node, DOMNode $refNode)
    {
        $new = parent::insertAfter($node, $refNode);
        if (is_object($new)) {
            self::setIdAttrs($new);
        }

        return $new;
    }

    /**
     * Body Element.
     *
     * preturn mixed
     */
    public function body()
    {
        return $this->dom->getElementsByTagName('body')->item(0);
    }

    /**
     * HTML Header Element.
     *
     * preturn mixed
     */
    public function head()
    {
        return $this->dom->getElementsByTagName('head')->item(0);
    }

    /**
     * HTML Element.
     *
     * preturn mixed
     */
    public function html()
    {
        return $this->dom->documentElement;
    }

    /**
     * Convert HTML to XML, Replace empty tags.
     *
     * @param string $source
     * @param bool   $ishtml
     *
     * @return string
     */
    public static function htmlToXml($source, $ishtml = false)
    {
        if ($ishtml) {
            $source = preg_replace_callback(
                "/<([A-Z]+)(([\s]+[^>]+)?)>/",
                'self::openTag',
                $source
            );
            $source = preg_replace_callback(
                "/<\/([A-Z]+)>/",
                'self::closeTag',
                $source
            );
        }

        $enc = 'UTF-8';
        foreach (self::$emptyTags as $tag => $value) {
            $quoted = preg_quote($tag, '/');
            $pattern = '/<('.$quoted.'(\s+[^>]*)?)>/i';
            $source = preg_replace($pattern, '<$1/>', $source);

            $offset = 0;
            $matched = array();
            $needle = "</$tag>";
            $shift = mb_strlen($needle, $enc);
            while (false !== $pos = mb_strpos(
                $source, $needle, $offset, $enc
            )) {
                $offset = $pos + $shift;
                $matched[] = $pos;
            }

            $diff = 0;
            foreach ($matched as $match) {
                $len = mb_strlen($source, $enc);
                $offset = ($match - $diff) - $len;
                $sep = mb_strrpos($source, "<$tag", $offset, $enc);

                $front = mb_substr($source, 0, $sep, $enc);
                $back = mb_substr($source, $sep, NULL, $enc);

                $pattern = '/<('.$quoted.')([^>]*)?\/>/is';
                $back = preg_replace($pattern, '<$1$2>', $back, 1);

                $source = $front.$back;

                $diff += $len - mb_strlen($source, $enc);
            }
        }
        $source = preg_replace("/[\/]+>/", '/>', $source);

        $pattern = "/(<.+?\s+)(async|checked|disabled|readonly|required|reversed|seamless|selected|loop|hidden|open|scoped|multiple|defer|ismap)((?!\s*=).*?".">)/";
        while (preg_match($pattern, $source, $match)) {
            $source = preg_replace($pattern, "$1$2=\"$2\"$3", $source);
        }

        return $source;
    }

    /** 
     * Replace open tag.
     *
     * @param array $tags
     *
     * @return string
     */
    private static function openTag($tags)
    {
        return '<'.strtolower($tags[1]).$tags[2].'>';
    }

    /** 
     * Replace close tag.
     *
     * @param array $tags
     *
     * @return string
     */
    private static function closeTag($tags)
    {
        return '</'.strtolower($tags[1]).'>';
    }

    /**
     * escape entity reference.
     *
     * @param string $source
     *
     * @return string
     */
    public static function escapeEntityReference($source)
    {
        $regex = array('/&([a-zA-Z]+);/', '/&#([0-9]+);/');
        $replace = array('%amp%$1%;%', '%amp%#$1%;%');

        return preg_replace($regex, $replace, $source);
    }

    /**
     * Rewind escape elements.
     *
     * @param string $source
     *
     * @return string
     */
    public static function rewindEntityReference($source)
    {
        $regex = array("/%amp%([a-zA-Z]+)%;%/", "/%amp%#([0-9]+)%;%/");
        $replace = array('&$1;', '&#$1;');

        return preg_replace($regex, $replace, $source);
    }

    /**
     * escape comment elements.
     *
     * @param string $source
     *
     * @return string
     */
    public static function escapeComment($source)
    {
        $regex = array("/([\s]*<!--)/s", '/(-->)/');
        $replace = array('<![CDATA[$1', '$1]]>');

        return preg_replace($regex, $replace, $source);
    }

    /**
     * escape script data.
     *
     * @param string $source
     * @param array  $tags
     */
    public static function escapeCdata($source, $tags = null)
    {
        $pattern = '/'.preg_quote('<![CDATA[', '/').'/i';
        $source = preg_replace($pattern, '', $source);
        $pattern = '/'.preg_quote(']]>', '/').'/i';
        $source = preg_replace($pattern, '', $source);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $pattern = '/(<'.preg_quote($tag, '/').'[^>]*>)/i';
                $source = preg_replace($pattern, '$1<![CDATA[', $source);
                $pattern = "/(<\/".preg_quote($tag, '/').'>)/i';
                $source = preg_replace($pattern, ']]>$1', $source);
            }
        }

        return $source;
    }

    /**
     * default form data.
     *
     * @param P5\Html\Source $html
     * @param string         $id
     * @param mixed          $force
     * @param mixed          $skip
     */
    public static function setDefaultValue(
        \P5\Html\Form $request,
        $html,
        $id = null,
        $force = null,
        $skip = null
    ) {
        if (is_null($skip)) {
            $skip = array();
        }
        $sec = 0;
        $prevName = '';

        switch (get_class($html)) {
        case __CLASS__:
            $form = $html->getElementById($id);
            break;
        case 'HTMLElement':
            if (strtolower($html->nodeName) === 'form') {
                $form = $html;
            }
            break;
        }
        if (!is_object($form)) {
            return;
        }

        // input 
        $nodelist = $form->getElementsByTagName('input');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Type
            $type = $element->getAttribute('type');
            if ($type == 'button' || $type == 'submit' || $type == 'reset') {
                continue;
            }

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = self::getRequestValue($request, $name, $force);
            if ($prevName != $name) {
                $sec = 0;
            } else {
                ++$sec;
            }

            \P5\Xml\Html\Input::setValue($request, $html, $element, $type, $name, $value, $sec);

            $prevName = $name;
        }

        // textarea
        $nodelist = $form->getElementsByTagName('textarea');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            # Getting Value
            $value = self::getRequestValue($request, $name, $force);

            \P5\Xml\Html\Textarea::setValue($request, $html, $element, $value, $sec);
        }

        // select
        $nodelist = $form->getElementsByTagName('select');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            $element = $nodelist->item($i);

            // Getting Name
            $name = $element->getAttribute('name');
            if (in_array($name, $skip)) {
                continue;
            }

            // Getting Value
            $value = self::getRequestValue($request, $name, $force);

            \P5\Xml\Html\Select::setValue($request, $html, $element, $name, $value, $sec);
        }
    }

    /**
     * get form data.
     *
     * @param string $name
     * @param mixed  $force
     *
     * @return mixed
     */
    private static function getRequestValue($request, $name, $force = null)
    {
        $data = (strtolower($_SERVER['REQUEST_METHOD']) == 'post') ? 'POST' : 'GET';
        if (!empty($force)) {
            $data = (strtolower($force) == 'post') ? 'POST' : 'GET';
        }
        if (preg_match("/^(.+)\[(.*)\]$/", $name, $match)) {
            $name = $match[1];
            $key = $match[2];
        }
        // Value
        $value = $request->$data($name);

        return $value;
    }

    /**
     * Make pagenation source.
     *
     * @param number $total
     * @param number $row
     * @param number $current
     * @param string $href
     * @param string $sep
     * @param mixed  $col
     * @param bool   $force
     * @param mixed  $step
     * @param string $prev
     * @param string $next
     *
     * @return string
     */
    public static function pager($total, $row, $current, $href, $sep = '', $col = null, $force = false, $step = null, $prev = '', $next = '')
    {
        $current = (int) $current;
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
        for ($i = $start; $i <= $end; ++$i) {
            if ($i == $current) {
                array_push($links, "<strong>$i</strong>");
            } else {
                $link = preg_replace('/__PAGE__/', $i, $href);
                $anchor = '<a href="'.$link.'">'.$i.'</a>';
                array_push($links, $anchor);
            }
        }
        // Link for first page
        if ($step === true && $start > 1) {
            $link = preg_replace('/__PAGE__/', 1, $href);
            $anchor = '<a href="'.$link.'" class="first">1..</a>';
            array_unshift($links, $anchor);
        }
        // Link for last page
        if ($step === true && $end < $sum) {
            $link = preg_replace('/__PAGE__/', $sum, $href);
            $anchor = '<a href="'.$link.'" class="last">..'.$sum.'</a>';
            array_push($links, $anchor);
        }
        // Link for prev page
        if (!empty($prev)) {
            if ($current != 1) {
                $link = preg_replace('/__PAGE__/', $current - 1, $href);
                $anchor = '<a href="'.$link.'" class="prev">'.htmlspecialchars($prev).'</a>';
            } else {
                $anchor = '<span class="prev">'.htmlspecialchars($prev).'</span>';
            }
            array_unshift($links, $anchor);
        }
        // Link for prev page
        if (!empty($next)) {
            if ($current != $sum) {
                $link = preg_replace('/__PAGE__/', $current + 1, $href);
                $anchor = '<a href="'.$link.'" class="next">'.htmlspecialchars($next).'</a>';
            } else {
                $anchor = '<span class="next">'.htmlspecialchars($next).'</span>';
            }
            array_push($links, $anchor);
        }

        if (!empty($sep)) {
            $sep = '<span class="P5-separator">'.$sep.'</span>';
        }

        return implode($sep, $links);
    }
}

}

namespace {

class HTMLElement extends DOMElement
{
    public function classListAdd(...$name)
    {
        $class = [];
        if ($this->hasAttribute('class')) {
            $existing_class = explode(
                ' ', $this->getAttribute('class')
            );
            $class = $existing_class;
        }
        $class = array_merge($class, $name);

        $this->setClassList($class);
    }

    public function classListRemove(...$name)
    {
        if ($this->hasAttribute('class')) {
            $existing_class = explode(
                ' ', $this->getAttribute('class')
            );
            $class = array_diff($existing_class, $name);
            $this->setClassList($class);
        }
    }

    public function classListToggle($name, $force = null)
    {
        $response = $force;
        $class = [];
        if ($this->hasAttribute('class')) {
            $existing_class = explode(
                ' ', $this->getAttribute('class')
            );
            $class = $existing_class;
        }

        if (is_null($force)) {
            if (in_array($name, $class)) {
                $class = array_diff($class, array($name));
                $response = true;
            } else {
                $class[] = $name;
                $response = false;
            }
        } elseif ($force) {
            $class[] = $name;
        } else {
            $class = array_diff($class, array($name));
        }

        $this->setClassList($class);

        return $response;
    }

    public function classListContains($name)
    {
        if ($this->hasAttribute('class')) {
            $existing_class = explode(
                ' ', $this->getAttribute('class')
            );
            if (in_array($name, $existing_class)) {
                return true;
            }
        }

        return false;
    }

    public function classListReplace($name, $replace)
    {
        if ($this->hasAttribute('class')) {
            $existing_class = explode(
                ' ', $this->getAttribute('class')
            );
            if (in_array($name, $existing_class)) {
                $class = array_diff($class, array($name));
                $class[] = $replace;
                $this->setClassList($class);
            }
        }
    }

    private function setClassList(array $class)
    {
        if (!empty($class)) {
            $this->setAttribute(
                'class',
                implode(' ', array_unique($class))
            );
        } else {
            $this->removeAttribute('class');
        }
    }

    public function appendComment($data)
    {
        $this->appendChild($this->ownerDocument->createComment($data));
    }

    public function multiLineContentWithComment($data)
    {
        while ($this->lastChild) {
            $this->removeChild($this->lastChild);
        }

        $doc = $this->ownerDocument;
        $pattern = '/<!--(((?'
            . '>[^(<!--)(-->)]*)|(?R))*)-->(\r\n|\r|\n)?/s';
        if (preg_match_all($pattern, $data, $match)) {
            $lines = preg_split($pattern, $data);
            $last_line = array_pop($lines);
            foreach ($lines as $line) {
                $this->multiLineContent($line, true, true);
                $comment = array_shift($match[1]);
                $this->appendChild($doc->createComment(
                    str_replace(['<', '>'], ['&lt;', '&gt;'], $comment)
                ));
                $crlf = array_shift($match[3]);
                if (!empty($crlf)) {
                    $this->appendChild($doc->createTextNode($crlf));
                }
            }
            $this->multiLineContent($last_line, false, true);
        } else {
            $this->multiLineContent($data);
        }
    }

    public function multiLineContent($data, $notrim = false, $noclear = false)
    {
        if ($noclear === false) {
            while ($this->lastChild) {
                $this->removeChild($this->lastChild);
            }
        }

        $doc = $this->ownerDocument;
        if ($notrim === false) {
            $data = rtrim($data);
        }
        $lines = explode(PHP_EOL, $data);
        $last = array_pop($lines);
        foreach ($lines as $line) {
            $this->appendChild($doc->createTextNode($line));
            $this->appendChild($doc->createElement('br'));
        }
        $this->appendChild($doc->createTextNode($last));
    }

    public function appendOption($value, $label = '', $selected = false, $refnode = null)
    {
        if ($this->nodeName !== 'select' &&
            $this->nodeName !== 'optgroup'
        ) {
            return;
        }
        $doc = $this->ownerDocument;
        $node = $this->appendChild($doc->createElement('option'));
        $node->setAttribute('value', $value);
        if ($selected) {
            $node->setAttribute('selected', true);
        }
        if (empty($label)) {
            $label = $value;
        }
        $node->appendChild($doc->createTextNode($label));
        if ($refnode) {
            $this->insertBefore($node, $refnode);
        } else {
            $this->appendChild($node);
        }
    }

    public function appendScript(
        $src,
        array $options = array(),
        $prepend = false
    ) {
        $doc = $this->ownerDocument;
        $node = $this->appendChild($doc->createElement('script'));
        $node->setAttribute('src', $src);
        foreach ($options as $name => $value) {
            $node->setAttribute($name, $value);
        }

        $scripts = $this->getElementsByTagName('script');
        if ($prepend && $scripts->length > 0) {
            $this->insertBefore($node, $scripts->item(0));
        } else {
            $this->appendChild($node);
        }
    }

    public function querySelector($query)
    {
        $node_list = $this->querySelectorAll($query);

        return $node_list->item(0);
    }

    public function querySelectorAll($query)
    {
        $xpath = new DOMXPath($this->ownerDocument);

        return $xpath->query($query, $this);
    }

    public function getElementsByName($name)
    {
        return $this->querySelectorAll('.//*[@name="'.$name.'"]');
    }

    public function getElementsByClassName($class)
    {
        $query = sprintf('.//*[contains(@class,"%s")]',$class);
        $tmp = $this->querySelectorAll($query, $parent);
        $nodes = array();
        foreach ($tmp as $node) {
            $attribute = $node->getAttribute('class');
            $classes = explode(' ', $attribute);
            if (in_array($class, $classes)) {
                $nodes[] = $node;
            }
        }

        return new \P5\Xml\Dom\NodeList($nodes);
    }

    public function form()
    {
        $parent = $this->parentNode;
        while (strtolower($parent->nodeName) !== 'form') {
            $parent = $parent->parentNode;
        }

        return $parent;
    }

    public function type()
    {
        if ($this->hasAttribute('type')) {
            return $this->getAttribute('type');
        }
    }

    public function insertHidden($form, $name, $value, $options = array())
    {
        $doc = $form->ownerDocument;
        $hidden = $doc->createElement('input');
        $hidden->setAttribute('type', 'hidden');
        $hidden->setAttribute('name', $name);
        $hidden->setAttribute('value', $value);
        foreach ($options as $key => $data) {
            $hidden->setAttribute($key, $data);
        }

        return $form->appendChild($hidden);
    }

    public function innerHtml($source = null)
    {
        $container_id = bin2hex(random_bytes(6));
        $tmp = new DOMDocument();
        $tmp->loadHTML(
            '<?xml encoding="UTF-8">'
            . "<div id='{$container_id}'>{$source}</div>"
        );
        if (false === $tmp) {
            return false;
        }
        // dirty fix
        foreach ($tmp->childNodes as $item) {
            if ($item->nodeType === XML_PI_NODE) {
                $tmp->removeChild($item);
            }
        }
        $tmp->encoding = 'UTF-8';

        while ($this->firstChild) {
            $this->removeChild($this->firstChild);
        }

        $doc = $this->ownerDocument;
        $container = $tmp->getElementById($container_id);
        foreach ($container->childNodes as $item) {
            if (false !== ($node = $doc->importNode($item, true))) {
                $this->appendChild($node);
            }
        }
    }
}

}
