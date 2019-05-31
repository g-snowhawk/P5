<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016-2017 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

use ErrorException;

/**
 * Custom Error Handler.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com/>
 */
class Error
{
    const MAX_LOG_SIZE = 2097152;
    const MAX_LOG_FILES = 10;
    const FEEDBACK_INTERVAL = 10800;

    /**
     * Debug mode.
     *
     * @var int
     */
    private $debug_mode = 0;

    /**
     * logfile save path.
     *
     * @var string
     */
    private $logdir;

    /**
     * Template file path.
     *
     * @var mixed
     */
    protected $template;

    /**
     * Temporary Template file path.
     *
     * @var mixed
     */
    protected static $temporary_template;

    /**
     * not feedback flag.
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
        $this->template = $template;
        if (defined('DEBUG_MODE')) {
            $this->debug_mode = DEBUG_MODE;
        }

        register_shutdown_function([$this, 'unloadHandler']);
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);

        if (defined('ERROR_LOG_DESTINATION')
         && !self::isEmail(ERROR_LOG_DESTINATION)
        ) {
            $dir = dirname(ERROR_LOG_DESTINATION);
            if (!empty($dir)) {
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!file_exists(ERROR_LOG_DESTINATION)) {
                        touch(ERROR_LOG_DESTINATION);
                    }
                } catch (ErrorException $e) {
                    trigger_error(
                        ERROR_LOG_DESTINATION . ' is no such file',
                        E_USER_ERROR
                    );
                }
            }
            $this->logdir = $dir;
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
    public function errorHandler(
        $errno,
        $errstr,
        $errfile,
        $errline,
        $errcontext
    ) {
        if (error_reporting() === 0 && $this->debug_mode === 0) {
            return false;
        }

        if ($errno === E_USER_ERROR
         || $errno === E_USER_NOTICE
         || $errno === E_STRICT
         || $errno === E_NOTICE
        ) {
            $message = "$errstr in $errfile on line $errline.";
            if ($errno === E_USER_ERROR) {
                self::feedback($message, $errno);
            }
            self::log($message, $errno);
            if ($errno === E_USER_ERROR) {
                self::displayError($message, $errno);
            }

            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Custom exception handler.
     *
     * @param object $ex
     */
    public function exceptionHandler($ex)
    {
        $errno = method_exists($ex, 'getSeverity')
            ? $ex->getSeverity() : $ex->getCode();
        $errstr = $ex->getMessage();
        $errfile = $ex->getFile();
        $errline = $ex->getLine();
        $message = "$errstr in $errfile on line $errline.";
        self::feedback($message, $errno);
        self::log($message, $errno, $errfile, $errline);
        $message .= PHP_EOL.$ex->getTraceAsString();
        self::displayError($message, $errno);
    }

    /**
     * Unload action.
     */
    public function unloadHandler()
    {
        $err = error_get_last();
        if (!is_null($err)) {
            $message = "{$err['message']}"
               . " in {$err['file']} on line {$err['line']}. ";
            $errno = $err['type'];
            self::feedback($message, $errno);
            self::log($message, $errno);
            self::displayError($message, $errno);
        }
    }

    /**
     * Display Error message.
     *
     * @param string $message
     * @param int    $errno
     * @param array  $tracer
     */
    public function displayError($message, $errno, $tracer = null)
    {
        if (in_array($errno, [E_NOTICE, E_USER_NOTICE, E_STRICT])) {
            return;
        }

        if (php_sapi_name() === 'cli') {
            echo $message;
            exit($errno);
        }

        $src = (is_null($this->template))
            ? self::htmlSource()
            : file_get_contents($this->template, FILE_USE_INCLUDE_PATH);
        if (!empty($this->temporary_template)) {
            $src = file_get_contents(
                $this->temporary_template,
                FILE_USE_INCLUDE_PATH
            );
            $this->temporary_template = null;
        }
        $message = htmlspecialchars(
            $message,
            ENT_COMPAT,
            mb_internal_encoding(),
            false
        );
        if ($this->debug_mode > 0) {
            $debugger = '';
            foreach ((array) $tracer as $trace) {
                $debugger .= PHP_EOL . $trace['file']
                    . ' on line '.$trace['line'];
            }
            $src = preg_replace(
                '/<!--ERROR_DESCRIPTION-->/',
                '<p id="P5-errormessage">'
                . nl2br(
                    htmlentities(
                        $message,
                        ENT_QUOTES,
                        'UTF-8',
                        false
                    ) . $debugger
                )
                . '</p>',
                $src
            );
        }
        if (defined('LINK_TO_HOMEPAGE')) {
            $src = preg_replace(
                '/<!--LINK_TO_HOMEPAGE-->/',
                '<a href="'
                . LINK_TO_HOMEPAGE
                . '" class="P5-errorhomelink">Back</a>',
                $src
            );
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo $src;
        exit($errno);
    }

    /**
     * Feedback to administrators.
     *
     * @param string $message
     * @param int    $errno
     *
     * @see P5\Text::explode()
     * @see P5\Environment::server()
     */
    public static function feedback($message, $errno)
    {
        if (!defined('FEEDBACK_ADDR') || false === self::$not_feedback) {
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
                if ($match[2] === preg_replace("/^\[(.+?)\].*?\[.+?\]\s*/", "", $message)
                 && time() - strtotime($match[1]) < self::FEEDBACK_INTERVAL
                ) {
                    return;
                }
            }
            fclose($fh);
        }

        $configuration = Text::explode(',', FEEDBACK_ADDR);
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
            $message .= PHP_EOL;
            $message .= PHP_EOL.'User: '.Environment::server('remote_addr');
            $message .= PHP_EOL.'Host: '.Environment::server('server_name');
            $message .= PHP_EOL.'Time: '.date('Y-m-d H:i:s');
            foreach ($feedbacks as $to) {
                error_log($message, 1, $to);
            }
        }
    }

    /**
     * Recoding Error log.
     *
     * @param string $message
     * @param int    $errno
     */
    public function log($message, $errno)
    {
        if ($this->debug_mode === 0) {
            if (in_array($errno, [8, 1024, 2048])) {
                return;
            }
        }
        if (defined('ERROR_LOG_DESTINATION')) {
            if (self::isEmail(ERROR_LOG_DESTINATION)) {
                error_log($message, 1, ERROR_LOG_DESTINATION);
            } elseif (!is_null($this->logdir)) {
                $client = '['
                    . filter_input(INPUT_SERVER, 'REMOTE_ADDR')
                    . '] ';
                error_log(
                    date('[Y-m-d H:i:s] ') . $client . $message . PHP_EOL,
                    3,
                    ERROR_LOG_DESTINATION
                );
            } else {
                error_log($message, 0, ERROR_LOG_DESTINATION);
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
    private static function isEmail($str)
    {
        return (bool)filter_var($str, FILTER_VALIDATE_EMAIL);
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
        $str = '';
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['file'])) {
                $str .= $trace['file'] . ' at ' . $trace['line'] . PHP_EOL;
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
