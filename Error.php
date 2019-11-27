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
    const MAX_LOG_SIZE = 2097152;
    const MAX_LOG_FILES = 10;
    const FEEDBACK_INTERVAL = 10800;

    /**
     * Error reporting level
     *
     * @var int
     */
    protected $error_reporting;

    /**
     * Custom error handler.
     *
     * @var mixed
     */
    protected $_oldErrorHandler;

    /**
     * Custom exception handler.
     *
     * @var mixed
     */
    protected $_oldExceptionHandler;

    /**
     * Temporary Template file path.
     *
     * @var string
     */
    protected static $_temporaryTemplate;

    /**
     * not sending feedback flag
     *
     * @var bool
     */
    protected static $not_feedback = false;

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
        $this->_oldExceptionHandler = set_exception_handler(array($this, 'exceptionHandler'));
        $this->error_reporting = error_reporting();

        if (!empty($template) && !defined('ERROR_DOCUMENT')) {
            if (false !== $fh = fopen($template, 'r', FILE_USE_INCLUDE_PATH)) {
                define('ERROR_DOCUMENT', $template);
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
        if ($this->error_reporting === 0) {
            return false;
        }

        $msg = "$errstr in $errfile on $errline.";

        if (DEBUG_MODE > 1 || ($errno !== E_NOTICE && $errno !== E_USER_NOTICE)) {
            throw new ErrorException($msg, 0, $errno, $errfile, $errline);
        }

        self::feedback($msg, $errno, self::$not_feedback);
        self::log($msg, $errno);

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
        self::feedback($msg, $code, self::$not_feedback);
        self::log($msg, $code);
        self::displayError($msg, $code);
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

        if (php_sapi_name() === 'cli') {
            $src = $msg . PHP_EOL;
        }
        else {
            if (!is_null(self::$_temporaryTemplate)) {
                $src = file_get_contents(self::$_temporaryTemplate, FILE_USE_INCLUDE_PATH);
            } elseif (defined('ERROR_DOCUMENT')) {
                if (false !== $fh = fopen(ERROR_DOCUMENT, 'r', FILE_USE_INCLUDE_PATH)) {
                    $src = stream_get_contents($fh);
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
        }

        echo $src;
        self::$_temporaryTemplate = null;
        exit($errno);
    }

    /**
     * Feedback to administrators
     *
     * @param string $msg
     * @param int    $errno
     * @param bool   $not_feedback
     *
     * @return void
     */
    public static function feedback($msg, $errno, $not_feedback = false)
    {
        if ($not_feedback === true || !defined('FEEDBACK_ADDR')) {
            return;
        }

        if ($errno === E_NOTICE) {
            return;
        }

        if ($fh = fopen(ERROR_LOG_DESTINATION, 'r')) {
            $final = '';
            for ($i = -2;; $i--) {
                if (fseek($fh, $i, SEEK_END) === -1) {
                    break;
                }
                $line = rtrim(fgets($fh, 8192));
                if (empty($line)) {
                    break;
                }
                $final = $line;
            }
            if (preg_match("/^\[(.+?)\].*?\[.+?\]\s*(.+$)/", $final, $match)) {
                if ($match[2] === preg_replace("/^\[(.+?)\].*?\[.+?\]\s*/", "", $msg)
                 && time() - strtotime($match[1]) < self::FEEDBACK_INTERVAL
                ) {
                    return;
                }
            }
            fclose($fh);
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
            $msg .= PHP_EOL.'User: '.P5_Environment::server('remote_addr');
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
                $remote_addr = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
                error_log(date('[Y-m-d H:i:s] ')."[$remote_addr] $msg\n", 3, ERROR_LOG_DESTINATION);
            } else {
                error_log($msg, 0, ERROR_LOG_DESTINATION);
            }
        }

        self::rotate();
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

    public static function rotate($force = false)
    {
        if (!file_exists(ERROR_LOG_DESTINATION)) {
            return true;
        }

        $size = filesize(ERROR_LOG_DESTINATION);
        if ($size === 0) {
            return true;
        }

        $max_log_size = (defined('MAX_LOG_SIZE'))
            ? MAX_LOG_SIZE : self::MAX_LOG_SIZE;
        if (false === $force && $size < $max_log_size) {
            return true;
        }

        $ext = date('.YmdHis');
        if (!rename(ERROR_LOG_DESTINATION, ERROR_LOG_DESTINATION . $ext)) {
            return false;
        }

        $max_log_files = (defined('MAX_LOG_FILES'))
            ? MAX_LOG_FILES : self::MAX_LOG_FILES;
        $files = glob(ERROR_LOG_DESTINATION . '.*');
        if (count($files) <= $max_log_files) {
            return true;
        }

        return unlink($files[0]);
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
    private static function htmlSource()
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
