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
 * HTML source to DOM class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Html_Source extends P5_Xml_Dom
{
    /**
     * Name space.
     */
    const NAMESPACE_URI = 'http://www.plus-5.com/xml';

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
        $source = P5_Html::htmlToXml($source, $ishtml);
        $this->orgSource = $source;
        $this->escapeCdata();

        // Call parent constructor.
        parent::__construct($this->orgSource);

        self::setIdAttrs($this->dom);
    }

    /**
     * Append X-UA-Compatible.
     *
     * @param object $html
     */
    public function insertXUACompatible($meta = false)
    {
        $content = '';
        if (preg_match("/MSIE ([0-9\.]+);/", P5_Environment::server('HTTP_USER_AGENT'), $ver)) {
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
                P5_Http::responceHeader('X-UA-Compatible', $content);
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
                $html = $this->dom->saveXML();
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

        $source = $html;
        if ($noFormat === false) {
            $formatted = new P5_Html_Format($source, $noDecl, $noDtd);
            $source = $formatted->toString();
        }

        if (is_null($enc)) {
            return $source;
        }

        return P5_Html::convertEncoding($source, $enc);
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

    /**
     * Getting HTML element by tag name.
     *
     * @param string $name
     * @param object $parent
     * @return mixed
     */
    public function getElementByName($name, $parent = null)
    {
        if (is_null($parent)) {
            $parent = $this->dom;
        }

        $nodelist = $parent->getElementsByTagName('*');
        for ($i = 0; $i < $nodelist->length; ++$i) {
            if ($nodelist->item($i)->getAttribute('name') == $name) {
                return $nodelist->item($i);
            }
        }

        return;
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
        if (is_null($parent)) {
            $parent = $this->dom;
        }
        $nodes = array();
        $this->getElementsByAttr($parent, 'name', $name, $nodes);

        return new P5_Xml_Dom_NodeList($nodes);
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
        if (!is_object($parent)) {
            $parent = $this->dom;
        }
        $nodes = array();
        $this->getElementsByAttr($parent, 'class', $class, $nodes);

        return new P5_Xml_Dom_NodeList($nodes);
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
        if (method_exists($node, 'hasAttribute') && $node->hasAttribute($attr)) {
            $attribute = $node->getAttribute($attr);
            if ($attr === 'class') {
                $classes = explode(' ', $attribute);
                if (in_array($value, $classes)) {
                    $arr[] = $node;
                }
            } else {
                if ($attribute === $value) {
                    $arr[] = $node;
                }
            }
        }
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $cn) {
                self::getElementsByAttr($cn, $attr, $value, $arr);
            }
        }
    }

    /**
     * Append class attribute.
     *
     * @param DOMElement $element
     * @param string     $class
     * @return bool
     */
    public function setAttrClass($element, $class)
    {
        if ($element->nodeType != 1) {
            return true;
        }
        $classes = preg_split("/[\s]+/", $element->getAttribute('class'));
        array_push($classes, $class);
        $attr = implode(' ', array_unique(array_filter($classes)));

        return $element->setAttribute('class', $attr);
    }

    /**
     * remove class attribute.
     *
     * @param DOMElement $element
     * @param string     $class
     * @return bool
     */
    public function unsetAttrClass($element, $class)
    {
        if ($element->nodeType != 1) {
            return true;
        }
        $classes = preg_split("/[\s]+/", $element->getAttribute('class'));
        $keys = array_keys($classes, $class);
        foreach ($keys as $key) {
            unset($classes[$key]);
        }
        $attr = implode(' ', $classes);

        if (empty($attr)) {
            return $element->removeAttribute('class');
        }

        return $element->setAttribute('class', $attr);
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
    public function insertScript($src, $index = null)
    {
        $head = $this->dom->getElementsByTagName('head')->item(0);
        if (!is_object($head)) {
            return false;
        }

        $nodes = $this->importChild($src);

        $scripts = $head->getElementsByTagName('script');
        if ($scripts->length > 0) {
            $refNode = (is_null($index)) ? $scripts->item($scripts->length - 1)->nextSibling
                                         : $scripts->item($index);
        }

        $error = 0;
        if (isset($refNode) && is_object($refNode)) {
            foreach ($nodes as $node) {
                if (!$head->insertBefore($node, $refNode)) {
                    ++$error;
                }
            }
        } else {
            foreach ($nodes as $node) {
                if (!$head->appendChild($node)) {
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

        $inputs = $form->getElementsByTagName('input');

        foreach ($inputs as $input) {
            if ($input->getAttribute('name') == $name && false === strpos($name, '[]')) {
                $exists = 1;
                break;
            }
        }
        if (isset($exists)) {
            $input->setAttribute('value', $value);
        } else {
            $src = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            $this->appendChild($src, $form);
        }
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
    protected function escapeCdata()
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
}
