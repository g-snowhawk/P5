<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

/**
 * Custom Error Handler.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com/>
 */
class Error
{
    /**
     * Custom error handler.
     *
     * @var mixed
     */
    protected $old_error_handler;

    /**
     * Custom exception handler.
     *
     * @var mixed
     */
    protected $old_exception_handler;

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
    protected $temporary_template;

    /**
     * Object Constructor.
     *
     * @param string $template
     */
    public function __construct($template = null)
    {
        ini_set('display_errors', 'On');
        if (defined('ERROR_LOG_DESTINATION') && !self::isEmail(ERROR_LOG_DESTINATION)) {
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
        register_shutdown_function([$this, 'unloadHandler']);
        $this->old_error_handler = set_error_handler([$this, 'errorHandler']);
        $this->old_exception_handler = set_exception_handler([$this, 'exceptionHandler']);
        $this->template = $template;
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

        $message = "$errstr in $errfile on line $errline.";
        self::feedback($message, $errno);
        self::log($message, $errno);

        if (DEBUG_MODE > 1 || ($errno !== E_NOTICE && $errno !== E_USER_NOTICE)) {
            throw new \ErrorException($message, 0, $errno, $errfile, $errline);
        }

        return false;
    }

    /**
     * Custom exception handler.
     *
     * @param Exception $ex
     */
    public function exceptionHandler($ex)
    {
        $message = $ex->getMessage();
        $errno = $ex->getCode();
        self::feedback($message, $errno);
        self::log($message, $errno, $ex->getFile(), $ex->getLine());
    }

    /**
     * Unload action.
     */
    public function unloadHandler()
    {
        $err = error_get_last();
        if (!is_null($err)) {
            $message = "{$err['message']} in {$err['file']} on line {$err['line']}.";
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
        $src = (is_null($this->template)) ? self::htmlSource()
                                          : file_get_contents($this->template);
        if (!empty($this->temporary_template)) {
            $src = file_get_contents($this->temporary_template);
            $this->temporary_template = null;
        }
        $message = htmlspecialchars($message, ENT_COMPAT, mb_internal_encoding(), false);
        if (defined('DEBUG_MODE') && DEBUG_MODE !== 0) {
            $debugger = '';
            foreach ((array) $tracer as $trace) {
                $debugger .= '<br>'.$trace['file'].' on line '.$trace['line'];
            }
            $src = preg_replace(
                '/<!--ERROR_DESCRIPTION-->/',
                '<p id="P5-errormessage">'.
                    htmlentities($message, ENT_QUOTES, 'UTF-8', false).$debugger.
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
        if (!defined('FEEDBACK_ADDR')) {
            return;
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
        if (defined('DEBUG_MODE') && DEBUG_MODE === 0) {
            if (in_array($errno, [8, 1024, 2048])) {
                return;
            }
        }
        if (defined('ERROR_LOG_DESTINATION')) {
            if (self::isEmail(ERROR_LOG_DESTINATION)) {
                error_log($message, 1, ERROR_LOG_DESTINATION);
            } elseif (is_dir(dirname(ERROR_LOG_DESTINATION))) {
                $client = '['.filter_input(INPUT_SERVER, 'REMOTE_ADDR').'] ';
                error_log(date('[Y-m-d H:i:s] ')."$client$message\n", 3, ERROR_LOG_DESTINATION);
            } else {
                error_log($message, 0, ERROR_LOG_DESTINATION);
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
    private static function isEmail($str)
    {
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
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
