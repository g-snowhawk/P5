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
 * XML DOM class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Xml_Dom
{
    /** 
     * Current version
     */
    const VERSION = '1.1.0';

    /** 
     * DOMDocument object
     *
     * @ver DOMDocument
     */
    protected $_dom;

    /**
     * Skip WhiteSpace
     *
     * @var boolean
     */
    private $_skipWhiteSpace = false;

    /**
     * XML Processing Instruction
     *
     * @var boolean
     */
    private $_pi = false;

    /**
     * Error message
     *
     * @var string
     */
    private $_error = '';

    /** 
     * Object constructor
     *
     * @param mixed $source
     * @param bool $ishtml
     * @return  void
     */
    public function __construct($source, $ishtml = false) 
    {
        $this->_pi = preg_match("/<\?xml[^>]+>/", $source);
        $this->_dom = $this->load($source);
    }

    /**
     * Getter Method
     *
     * @param  string   $key
     * @return mixed
     */
    public function __get($key)
    {
        if (true === property_exists($this->_dom, $key)) {
            return $this->_dom->$key;
        }
        return null;
    }

    /**
     * Reload source to DOM Document
     *
     * @param string $template
     * @return void
     */
    public function reload($template)
    {
        $this->_pi = preg_match("/<\?xml[^>]+>/", $template);
        $this->_dom = $this->load($template);
    }

    /**
     * Load source to DOM Document
     *
     * @param string $template
     * @return mixed
     */
    public function load($template)
    {
        clearstatcache();
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = !$this->_skipWhiteSpace;

        $pattern = "/^[a-zA-Z0-9_:\-" . preg_quote('./\\', '/') . "]+$/";
        if (preg_match($pattern, $template) && is_file($template)) {
            $source = file_get_contents($template);
        } else {
            $source = $template;
        }

        $br = '';
        if (preg_match("/^([\r\n]+)/", $source, $match)) {
            $br = $match[1];
        }

        if (!empty($source) || $source === '0') {

            $source = P5_Html::escapeEntityReference($source);

            // if source is plain text
            if (! preg_match("/^[\s]*</", $source)) {
                $source = "<dummy>$source</dummy>";
            }
            if (!empty($br)) {
                $source = "<dummy>$source</dummy>";
            }

            $oeh = set_error_handler(array($this, 'errorHandler'));

            try {
                $dom->loadXML($source);
            } catch(Exception $e) {
                if (preg_match("/junk after document element/", $e->getMessage())
                    || preg_match("/Extra content at the end of the document in Entity/i", $e->getMessage())
                ) {
                    $xml = "<dummy>$source</dummy>";
                    $dom = $this->load($xml);
                }
                elseif (preg_match("/Namespace prefix ([^\s]+)/", $e->getMessage(), $match)) {
                    switch (strtolower($match[1])) {
                        case 'p5' :
                            $ns_uri = P5_Html_Source::NAMESPACE_URI;
                            break;
                        case 'rdf' :
                            $ns_uri = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
                            break;
                        case 'dc' :
                            $ns_uri = 'http://purl.org/dc/elements/1.1/';
                            break;
                        default :
                            $ns_uri = '';
                            break;
                    }
                    if (preg_match("/^[\s]*<dummy[\s]+/is", $source)) {
                        $xml = preg_replace("/^[\s]*<dummy([\s]+)/is", "<dummy xmlns:{$match[1]}=\"$ns_uri\"$1", $source);
                    } else {
                        $xml = "<dummy xmlns:{$match[1]}=\"$ns_uri\">$source</dummy>";
                    }
                    $dom = $this->load($xml);
                }
                elseif (preg_match("/Space required after the Public Identifier in Entity/", $e->getMessage(), $match)) {
                    $xml = preg_replace_callback("/<!DOCTYPE\s+([^>]+)>/i", array($this, "setPi"), $source);
                    $dom = $this->load($xml);
                }
                elseif (preg_match("/PCDATA invalid Char value/i", $e->getMessage(), $match)) {
                    $xml = preg_replace('/(?![\r\n\t])[[:cntrl:]]/', '', $source);
                    $dom = $this->load($xml);
                }
                elseif (preg_match("/Opening and ending tag mismatch:/", $e->getMessage()) ||
                         preg_match("/expected '>' in Entity, line:/", $e->getMessage()) ||
                         preg_match("/Specification mandate value for attribute .+ in Entity, line:/", $e->getMessage()) ||
                         preg_match("/xmlParseEntityRef: no name in Entity, line:/", $e->getMessage())
                ) {
                    throw new P5_Xml_Dom_Exception($e->getMessage());
                }
                else {
                    $this->_error = $e->getMessage();
                }
            }
            set_error_handler($oeh);
            if (!empty($this->_error)) {
                trigger_error($this->_error, E_USER_ERROR);
            }
        } else {
            $this->_parseError = true;
        }
        return $dom;
    }

    /**
     * Get parant node
     *
     * @param DOMElement $element
     * @param string $node_name
     * @param string $class_name
     * @return mixed
     */
    static public function getParentNode(DOMElement $element, $node_name, $class_name = '')
    {
        while ($parent = $element->parentNode) {
            if (!empty($class_name) && $parent->hasAttribute('class')) {
                $classes = preg_split("/[\s]+/", $parent->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($class === $class_name) {
                        return $parent;
                    }
                }
            }
            if ($parent->nodeName === $node_name) {
                return $parent;
            }
            $element = $parent;
        }
        return NULL;
    }


    /**
     * Get Chiled Nodes
     *
     * @return mixed
     */
    public function getChildNodes()
    {
        return $this->_dom->childNodes;
    }

    /**
     * Get elements
     *
     * @param string $id
     * @param string $attr
     * @return mixed
     */
    public function getElementById($id, $attr = 'id')
    {
        self::_setIdAttrs($this->_dom, $attr);
        return $this->_dom->getElementById($id);
    }

    /**
     * Get elements
     *
     * @param  string   $tagName
     * @return mixed
     */
    public function getElementsByTagName($tagName)
    {
        $tagName = strtolower($tagName);
        $nodeList = $this->_dom->getElementsByTagName($tagName);
        if ($nodeList->length == 0) {
            $tagName = strtoupper($tagName);
            $nodeList = $this->_dom->getElementsByTagName($tagName);
        }
        if ($nodeList->length == 0) {
            $tagName = ucfirst(strtolower($tagName));
            $nodeList = $this->_dom->getElementsByTagName($tagName);
        }
        return $nodeList;
    }

    /**
     * Get elements
     *
     * @param  string   $url
     * @param  string   $tagName
     * @return mixed
     */
    public function getElementsByTagNameNS($url, $tagName)
    {
        $tagName = strtolower($tagName);
        $nodeList = $this->_dom->getElementsByTagNameNS($url, $tagName);
        if ($nodeList->length == 0) {
            $tagName = strtoupper($tagName);
            $nodeList = $this->_dom->getElementsByTagNameNS($url, $tagName);
        }
        if ($nodeList->length == 0) {
            $tagName = ucfirst(strtolower($tagName));
            $nodeList = $this->_dom->getElementsByTagNameNS($url, $tagName);
        }
        return $nodeList;
    }

    /**
     * Insert child node
     *
     * @param mixed $node       Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function insertBefore($node, DOMNode $refNode)
    {
        if (is_string($node)) $node = $this->importChild($node);

        $parentNode = $refNode->parentNode;
        if (!is_object($parentNode)) return;

        if (is_array($node) || method_exists($node, 'item')) {
            $imported = array();
            foreach ($node as $child) {
                $ret = $parentNode->insertBefore($child, $refNode);
                array_push($imported, $ret);
            }
            if (count($imported) > 1) {
                return new P5_Xml_Dom_NodeList($imported);
            } elseif (count($imported) > 0) {
                return $imported[0];
            } 
            return null;
        }

        return $parentNode->insertBefore($node, $refNode);
    }

    /**
     * Insert after child node
     *
     * @param mixed $node       Source code or XML::DOM::Element
     * @param object $refNode
     * @return mixed
     */
    public function insertAfter($node, DOMNode $refNode)
    {
        if (is_string($node)) {
            $node = $this->importChild($node);
        }

        $parentNode = $refNode->parentNode;
        if (!is_object($parentNode)) {
            return;
        }

        $nextSibling = $refNode->nextSibling;

        if (is_array($node) || method_exists($node, 'item')) {
            $imported = array();
            foreach ($node as $child) {
                if (is_object($nextSibling)) {
                    $ret = $parentNode->insertBefore($child, $nextSibling);
                } else {
                    $ret = $parentNode->appendChild($child);
                }
                array_push($imported, $ret);
            }
            if (count($imported) > 1) {
                return new P5_Xml_Dom_NodeList($imported);
            } elseif (count($imported) > 0) {
                return $imported[0];
            } 
            return null;
        }

        if (is_object($nextSibling)) {
            $ret = $parentNode->insertBefore($node, $nextSibling);
        } else {
            $ret = $parentNode->appendChild($node);
        }
        return $ret;
    }

    /**
     * Append Comment
     *
     * @param string $node 
     * @param object $refNode
     * @param string $lf
     * @return mixed
     */
    public function appendComment($data, $refNode, $lf = '')
    {
        $com = $refNode->appendChild($this->_dom->createComment($data));
        if (!empty($lf)) {
            $this->insertBefore($this->_dom->createTextNode($lf), $com);
        }
    }

    /**
     * Append child node
     *
     * @param  mixed    $node       Source code or XML::DOM::Element
     * @param  object   $refNode
     * @return mixed
     */
    public function appendChild($node, $refNode)
    {
        if (is_string($node)) {
            $node = $this->importChild($node);
        }
        if (is_array($node) || method_exists($node, 'item')) {
            $imported = array();
            foreach ($node as $child) {
                if (is_object($refNode)) {
                    $ret = $refNode->appendChild($child);
                    array_push($imported, $ret);
                }
            }
            if (count($imported) > 1) {
                return new P5_Xml_Dom_NodeList($imported);
            } elseif (count($imported) > 0) {
                return $imported[0];
            }
            return null;
        }
        return $refNode->appendChild($node);
    }

    /**
     * Remove child node
     *
     * @param object $node
     * @param bool $recursive
     * @return mixed
     */
    public function removeChild($node, $recursive = false)
    {
        if (!is_object($node)) {
            return;
        }
        if (get_class($node) == 'P5_Xml_Dom_NodeList' || get_class($node) == 'DOMNodeList') {
            for ($i = $node->length - 1; $i >= 0; --$i) {
                if (false === $node->item($i)->parentNode->removeChild($node->item($i))) {
                    return false;
                }
            }
            return true;
        }
        if ($recursive !== true) {
            return $node->parentNode->removeChild($node);
        }
        return $this->_cleanUpNode($node);
    }

    /**
     * Clean up childnodes
     * 
     * @param DOMElement $node
     * @return mixed
     */
    private function _cleanUpNode($node)
    {
        while ($node->hasChildNodes()) {
            $this->_cleanUpNode($node->firstChild);
        }
        return $node->parentNode->removeChild($node);
    }

    /**
     * Import child node
     *
     * @param  mixed    $node       Source code or XML::DOM::Element
     * @param  object   $refNode
     * @return object
     */
    public function replaceChild($node, $refNode)
    {
        if (is_string($node)) $node = $this->importChild($node);
        if (get_class($refNode) == 'DOMNodeList') {
            while($refNode->length > 1) {
                $refNode->item(0)->parentNode->removeChild($refNode->item(0));
            }
            $refNode = $refNode->item(0);
        }
        $parent = $refNode->parentNode;
        if (is_null($parent)) {
            return;
        }
        if (is_array($node) || method_exists($node, 'item')) {
            $imported = array();
            foreach ($node as $child) {
                $ret = $parent->insertBefore($child, $refNode);
                array_push($imported, $ret);
            }
            $parent->removeChild($refNode);
            if (count($imported) > 1) {
                return new P5_Xml_Dom_NodeList($imported);
            } elseif (count($imported) > 0) {
                return $imported[0];
            }
            return null;
        }
        return $parent->replaceChild($node, $refNode);
    }

    /**
     * Import child node
     *
     * @param mixed $node Source code or XML::DOM::Element
     * @return object
     */
    public function importChild($node)
    {
        if (is_string($node)) {
            $node = $this->load($node);
            $node = $node->childNodes;
        }
        if (property_exists($node, 'length')) {
            $imported = array();
            foreach ($node as $child) {
                if ($child->nodeName == 'dummy') {
                    $children = $child->childNodes;
                    foreach ($children as $childNode) {
                        array_push($imported, $this->_dom->importNode($childNode, true));
                    }
                    continue;
                }
                array_push($imported, $this->_dom->importNode($child, true));
            }
            return new P5_Xml_Dom_NodeList($imported);
            //return $imported;
        }
        return $this->_dom->importNode($node, true);
    }

    /**
     * Create new DOM node
     *
     * @param string $tag
     * @param string $value
     * @return object
     */
    public function createElement($name, $value = '')
    {
        return $this->_dom->createElement($name, $value);
    }

    /**
     * Create new Text node
     *
     * @param  string   $text
     * @return object
     */
    public function createTextNode($text)
    {
        return $this->_dom->createTextNode($text);
    }

    /**
     * Processing Instruction
     *
     * @return mixed
     */
    public function processingInstruction()
    {
        if ($this->_pi) {
            return (object) array ('version'  => $this->_dom->xmlVersion,
                                   'encoding' => $this->_dom->xmlEncoding);
        }
        return null;
    }

    /**
     * Doctype
     *
     * @return mixed
     */
    public function doctype()
    {
        if (is_object($this->_dom->doctype)) return $this->_dom->doctype;
        return null;
    }

    /**
     * Error message (Read only)
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }

    /**
     * Using getElementById
     * 
     * @param DOMNode $node
     * @param string $attr
     * @return void
     */
    static private function _setIdAttrs(DOMNode $node, $attr) 
    {
        foreach ($node->childNodes as $cn) {
            if ($cn->hasAttributes()) {
                if ($cn->hasAttribute($attr)) {
                    // Important
                    if (false === $cn->getAttributeNode('id')->isID()) 
                        $cn->setIdAttribute($attr, true);
                }
            }
            if ($cn->hasChildNodes())
                self::_setIdAttrs($cn, $attr);
        }
    }

    /**
     * Save XML
     *
     * @param DOMNode
     * @return string
     */
    public function saveXML($node)
    {
        return $this->_dom->saveXML($node);
    }

    /**
     * Custom Error Handler
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return void
     */
    public function errorHandler($errno, $errstr, $errfile, $errline ) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
}
