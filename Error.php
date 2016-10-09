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
 * Custom error handler class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Error 
{
    /**
     * Current version
     */
    const VERSION = '1.1.0';

    /**
     * Error Handler
     *
     * @var mixed
     */
    protected $_oldErrorHandler;

    /**
     * Template file path
     *
     * @var mixed
     */
    protected $_template;

    /**
     * Temporary Template file path
     *
     * @var mixed
     */
    protected $_temporaryTemplate;

    /**
     * Object Constructor
     *
     * @param string $template
     * @return void
     */
    public function __construct($template = null)
    {
        ini_set('display_errors', 'On');
        if (defined('ERROR_LOG_DESTINATION') && !self::_isEmail(ERROR_LOG_DESTINATION)) {
            $dir = dirname(ERROR_LOG_DESTINATION);
            if (!empty($dir)) {
                if (!is_dir($dir)) {
                    if (false === @mkdir($dir, 0777, true)) {
                        trigger_error("$dir is not found.", E_USER_ERROR);
                    }
                }
                if (false === @touch(ERROR_LOG_DESTINATION)) {
                    trigger_error(ERROR_LOG_DESTINATION . " Permission denied.", E_USER_ERROR);
                }
            }
        }
        ini_set('display_errors', 'Off');
        register_shutdown_function(array($this, 'unloadHandler'));
        $this->_oldErrorHandler = set_error_handler(array($this, 'errorHandler'));
        $this->_oldExceptionHandler = set_exception_handler(array($this, 'exceptionHandler'));
        $this->_template = $template;
    }

    /**
     * Custom error handler
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return void
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (error_reporting() === 0) {
            return false;
        }
        $msg = "$errstr in $errfile on $errline.";
        self::log($msg, $errno);
        self::displayError($msg, $errno);
        return false;
    }

    /**
     * Custom exception handler
     *
     * @param Exception $ex
     * @return void
     */
    public function exceptionHandler($ex)
    {
        $msg = $ex->getMessage();
        self::log($msg, $ex->getCode());
    }

    /**
     * Unload action
     *
     * @return void
     */
    public function unloadHandler()
    {
        $ver = preg_replace('/\.([0-9]+)$/', "$1", PHP_VERSION, 1);
        $err = ($ver >= 5.20) ? error_get_last() : null;
        if (!is_null($err)) {
            $msg = "{$err['message']} in {$err['file']} on {$err['line']}.";
            self::log($msg, $err['type']);
            self::displayError($msg, $err['type']);
        }
    }

    /**
     * Display Error message
     *
     * @param string $msg
     * @param int $errno
     * @return void
     */
    public function displayError($msg, $errno)
    {
        // POST Sise Over.
        if (preg_match("/POST Content\-Length of ([0-9]+) bytes exceeds the limit of ([0-9]+) bytes/i", $msg, $match)) {
            return;
        }
        if (preg_match("/Couldn't fetch DOMElement/i", $msg, $match)) {
            throw new ErrorException($msg);
        }
        if (in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_STRICT))) {
            return;
        }
        $src = (is_null($this->_template)) ? self::htmlSource() 
                                           : file_get_contents($this->_template);
        if(!empty($this->_temporaryTemplate)) {
            $src = file_get_contents($this->_temporaryTemplate);
            $this->__temporaryTemplate = null;
        }

        $msg = htmlspecialchars($msg, ENT_COMPAT, mb_internal_encoding(), false);
        if (defined('DEBUG_MODE') && DEBUG_MODE !== 0) {
            $src = preg_replace(
                "/<!--ERROR_DESCRIPTION-->/",
                '<p id="P5-errormessage">' .
                    htmlentities($msg, ENT_QUOTES, 'UTF-8', false) .
                '</p>', $src
            );
        }
        if (defined('LINK_TO_HOMEPAGE')) {
            $src = preg_replace(
                "/<!--LINK_TO_HOMEPAGE-->/",
                '<a href="'.LINK_TO_HOMEPAGE.'" class="P5-errorhomelink">Back</a>', $src
            );
        }

        header('HTTP/1.1 500 Internal Server Error');
        echo $src;
        exit($errno);
    }

    /**
     * Recoding Error log.
     *
     * @param string $msg
     * @param int $errno
     * @return void
     */
    public function log($msg, $errno)
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE === 0) {
            if (in_array($errno, array(8, 1024, 2048))) {
                return;
            }
        }
        if (defined('ERROR_LOG_DESTINATION')) {
            if (self::_isEmail(ERROR_LOG_DESTINATION)) {
                error_log($msg, 1, ERROR_LOG_DESTINATION);
            } else if (is_dir(dirname(ERROR_LOG_DESTINATION))) {
                $client = '['.$_SERVER['REMOTE_ADDR'].'] ';
                error_log(date('[Y-m-d H:i:s] ') . "$client$msg\n", 3, ERROR_LOG_DESTINATION);
            } else {
                error_log($msg, 0, ERROR_LOG_DESTINATION);
            }
        }
    }

    /**
     * check E-mail format.
     *
     * @param string $str
     * @return bool
     */
    static private function _isEmail($str)
    {
        $pattern = '^(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                   '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                   '(?:"(?:\\[^\r\n]|[^\\"])*")))\@' .
                   '(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)' .
                   '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|' .
                   '(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))$';
        return preg_match("/$pattern/", $str);
    }

    /**
     * Default Error HTML
     *
     * @return string
     */
    public function htmlSource()
    {
        return <<<_HERE_
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>[P5] System Error</title>
    </head>
    <body>
        <h1>System Error</h1>
        <!--ERROR_DESCRIPTION-->
    </body>
</html>
_HERE_;

    }

    /**
     * backtrace
     *
     * @return string
     */
    static public function backtrace()
    {
        $backtrace = debug_backtrace();
        $str = '';
        foreach(debug_backtrace() as $trace) {
            if(isset($trace['file'])) {
                $str .= $trace['file'] . ' at ' . $trace['line'] . "\n";
            }
        }
        return $str;
    }
}
