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
 * See the P5_Text.
 */
require_once 'P5/Text.php';

/**
 * See the P5_Environment.
 */
require_once 'P5/Environment.php';

/**
 * Custom error handler class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Error
{
    /**
     * Error Handler.
     *
     * @var mixed
     */
    protected $_oldErrorHandler;

    /**
     * Temporary Template file path.
     *
     * @var mixed
     */
    protected $_temporaryTemplate;

    /**
     * Object Constructor.
     *
     * @param string $template
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
                    trigger_error(ERROR_LOG_DESTINATION.' Permission denied.', E_USER_ERROR);
                }
            }
        }
        ini_set('display_errors', 'Off');
        register_shutdown_function(array('P5_Error', 'unloadHandler'));
        $this->_oldErrorHandler = set_error_handler(array($this, 'errorHandler'));

        if (!empty($template) && !defined('ERROR_DOCUMENT')) {
            $src = file_get_contents($template, FILE_USE_INCLUDE_PATH);
            if (!empty($src)) {
                define('ERROR_DOCUMENT', $src);
            }
        }
    }

    /**
     * Custom error handler.
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @param array  $errcontext
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (error_reporting() === 0) {
            return false;
        }

        $msg = "$errstr in $errfile on $errline.";
        self::feedback($msg, $errno);
        self::log($msg, $errno);

        if (DEBUG_MODE > 1 || ($errno !== E_NOTICE && $errno !== E_USER_NOTICE)) {
            throw new ErrorException($msg, 0, $errno, $errfile, $errline);
        }

        return false;
    }

    /**
     * Custom exception handler.
     *
     * @param Exception $ex
     *
     * @return mixed
     */
    public function exceptionHandler($ex)
    {
        $msg = $ex->getMessage().' in '.$ex->getFile().' on '.$ex->getLine();
        $code = $ex->getCode();
        self::feedback($msg, $code);
        self::log($msg, $code);
    }

    /**
     * Unload action.
     */
    public static function unloadHandler()
    {
        $err = (version_compare(PHP_VERSION, '5.3.0') >= 0) ? error_get_last() : null;
        if (!is_null($err)) {
            $msg = "{$err['message']} in {$err['file']} on {$err['line']}.";
            self::feedback($msg, $err['type']);
            self::log($msg, $err['type']);
            self::displayError($msg, $err['type']);
        }
    }

    /**
     * Display Error message.
     *
     * @param string $msg
     * @param int    $errno
     */
    public static function displayError($msg, $errno)
    {
        // POST Size Over.
        if (preg_match("/POST Content\-Length of ([0-9]+) bytes exceeds the limit of ([0-9]+) bytes/i", $msg, $match)) {
            return;
        }
        if (preg_match("/Couldn't fetch DOMElement/i", $msg, $match)) {
            throw new ErrorException($msg);
        }
        if (in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_STRICT))) {
            return;
        }
        if (defined('ERROR_DOCUMENT')) {
            if (file_exists(ERROR_DOCUMENT)) {
                $src = file_get_contents(ERROR_DOCUMENT);
            } else {
                $src = ERROR_DOCUMENT;
            }
        } else {
            $src = self::htmlSource();
        }

        $msg = htmlspecialchars($msg, ENT_COMPAT, mb_internal_encoding(), false);
        if (defined('DEBUG_MODE') && DEBUG_MODE !== 0) {
            $src = preg_replace(
                '/<!--ERROR_DESCRIPTION-->/',
                '<p id="P5-errormessage">'.
                    htmlentities($msg, ENT_QUOTES, 'UTF-8', false).
                '</p>', $src
            );
        }
        if (defined('LINK_TO_HOMEPAGE')) {
            $src = preg_replace(
                '/<!--LINK_TO_HOMEPAGE-->/',
                '<a href="'.LINK_TO_HOMEPAGE.'" class="P5-errorhomelink">Back</a>', $src
            );
        }

        header('HTTP/1.1 500 Internal Server Error');
        echo $src;
        exit($errno);
    }

    /*
     * Feedback to administrators
     *
     * @param string $msg
     * @param int    $errno
     * @return void
     */
    public static function feedback($msg, $errno)
    {
        if (!defined('FEEDBACK_ADDR')) {
            return;
        }
        $configuration = P5_Text::explode(',', FEEDBACK_ADDR);
        $feedbacks = array();
        foreach ($configuration as $feedback_addr) {
            $feedbacks[] = filter_var(
                $feedback_addr,
                FILTER_VALIDATE_EMAIL,
                array(
                    'options' => array(
                        'default' => null,
                    ),
                )
            );
        }
        $feedbacks = array_values(array_filter($feedbacks, 'strlen'));
        if (count($feedbacks) > 0) {
            $msg .= PHP_EOL;
            $msg .= PHP_EOL.'Host: '.P5_Environment::server('server_name');
            $msg .= PHP_EOL.'Time: '.date('Y-m-d H:i:s');
            foreach ($feedbacks as $to) {
                error_log($msg, 1, $to);
            }
        }
    }

    /**
     * Recoding Error log.
     *
     * @param string $msg
     * @param int    $errno
     */
    public static function log($msg, $errno)
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE === 0) {
            if (in_array($errno, array(8, 1024, 2048))) {
                return;
            }
        }
        if (defined('ERROR_LOG_DESTINATION')) {
            if (self::_isEmail(ERROR_LOG_DESTINATION)) {
                error_log($msg, 1, ERROR_LOG_DESTINATION);
            } elseif (is_dir(dirname(ERROR_LOG_DESTINATION))) {
                $client = '['.$_SERVER['REMOTE_ADDR'].'] ';
                error_log(date('[Y-m-d H:i:s] ')."$client$msg\n", 3, ERROR_LOG_DESTINATION);
            } else {
                error_log($msg, 0, ERROR_LOG_DESTINATION);
            }
        }
    }

    /**
     * check E-mail format.
     *
     * @param string $str
     *
     * @return bool
     */
    private static function _isEmail($str)
    {
        $pattern = '^(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)'.
                   '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|'.
                   '(?:"(?:\\[^\r\n]|[^\\"])*")))\@'.
                   '(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+)'.
                   '(?:\.(?:[a-zA-Z0-9_!#\$\%&\'*+\/=?\^`{}~|\-]+))*)|'.
                   '(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))$';

        return preg_match("/$pattern/", $str);
    }

    /**
     * backtrace.
     *
     * @return string
     */
    public static function backtrace()
    {
        $backtrace = debug_backtrace();
        $str = '';
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['file'])) {
                $str .= $trace['file'].' at '.$trace['line']."\n";
            }
        }

        return $str;
    }

    /**
     * Default Error HTML.
     *
     * @return string
     */
    private function htmlSource()
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
}
