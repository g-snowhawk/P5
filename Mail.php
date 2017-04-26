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
 * Send Mail class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Mail
{
    /**
     * SMTP hostname or IP address.
     *
     * @var string
     */
    private $smtp = '';

    /**
     * Port number of SMTP server.
     *
     * @var string
     */
    private $port = '';

    /**
     * SMTP-Auth username.
     *
     * @var string
     */
    private $user = '';

    /**
     * SMTP-Auth password.
     *
     * @var string
     */
    private $passwd = '';

    /**
     * Use TLS.
     *
     * @var bool
     */
    private $tls = false;

    /**
     * Authentication Types.
     *
     * @var array
     */
    private $auth_types = [];

    /**
     * Mail sender address.
     *
     * @var string
     */
    private $from = '';

    /**
     * Mail envelope sender address.
     *
     * @var string
     */
    private $envfrom = '';

    /**
     * Mail subject.
     *
     * @var string
     */
    private $subject = '';

    /**
     * Plain text content.
     *
     * @var string
     */
    private $message = '';

    /**
     * HTML content.
     *
     * @var string
     */
    private $html = '';

    /**
     * Mailto Addresses.
     *
     * @var array
     */
    private $to = [];

    /**
     * Carbon copy Addresses.
     *
     * @var array
     */
    private $cc = [];

    /**
     * Blind carbon copy Addresses.
     *
     * @var array
     */
    private $bcc = [];

    /**
     * Mail headers.
     *
     * @var array
     */
    private $head = [];

    /**
     * Mail attachments.
     *
     * @var array
     */
    private $attachment = [];

    /**
     * SMTP stream.
     *
     * @var stream
     */
    private $socket;

    /**
     * log.
     *
     * @var string
     */
    private $log = '';

    /**
     * Error message.
     *
     * @var string
     */
    private $error = '';

    /**
     * Delimiter.
     *
     * @var string
     */
    private $delimiter = "\n";

    /**
     * encode.
     *
     * @var string
     */
    private $encoding;

    /**
     * Caracterset.
     *
     * @var array
     */
    private $charset = [
        'jis' => 'ISO-2022-JP',
        'sjis' => 'Shift_JIS',
        'utf-8' => 'UTF-8',
    ];

    /**
     * Object constructor.
     *
     * @param string $host
     * @param number $port
     * @param string $user
     * @param string $passwd
     */
    public function __construct($host = '', $port = '', $user = '', $passwd = '', $encoding = 'jis')
    {
        $this->smtp = $this->setHost($host);
        $this->port = $this->setPort($port);
        $this->user = $user;
        $this->passwd = $passwd;
        $this->encoding = $encoding;
    }

    /**
     * SMTP host.
     *
     * @param string $host
     *
     * @return string
     */
    public function setHost($host = '')
    {
        if (!empty($host)) {
            return $host;
        }
        $default = ini_get('SMTP');
        $host = (empty($defailt)) ? 'localhost' : $defailt;
        // Windows OS
        if (preg_match('/^WIN/i', PHP_OS)) {
            if ($this->smtp != 'localhost' && $host != ini_get('SMTP')) {
                ini_set('SMTP', $host);
            }
        }

        return $host;
    }

    /**
     * SMTP port.
     *
     * @param number $port
     *
     * @return string
     */
    public function setPort($port = null)
    {
        if (!empty($port)) {
            return $port;
        }
        $default = ini_get('smtp_port');

        return (!empty($default)) ? $default : 25;
    }

    /**
     * SET Encoding.
     *
     * @param string $encoding
     *
     * @return string
     */
    public function setEncoding($encoding)
    {
        return $this->encoding = $encoding;
    }

    /**
     * Set envelope From address.
     *
     * @param string $envfrom
     */
    public function envfrom($envfrom)
    {
        $this->envfrom = $this->normalizeAddress($envfrom);
    }

    /**
     * Set From address.
     *
     * @param string $from
     */
    public function from($from)
    {
        $this->from = $this->normalizeAddress($from);
    }

    /**
     * Set To address.
     *
     * @param string $to
     * @param string $prop
     */
    public function to($to = null, $prop = 'to')
    {
        if (is_null($this->$prop)) {
            $this->$prop = [];
        }
        if (is_null($to)) {
            $this->$prop = [];
        } elseif (is_array($to)) {
            foreach ($to as $value) {
                $this->{$prop}[] = $this->normalizeAddress($value);
            }
        } else {
            $this->{$prop}[] = $this->normalizeAddress($to);
        }
    }

    /**
     * Set Cc address.
     *
     * @param string $cc
     */
    public function cc($cc = null)
    {
        $this->to($cc, 'cc');
    }

    /**
     * Set Bcc address.
     *
     * @param string $bcc
     */
    public function bcc($bcc = null)
    {
        $this->to($bcc, 'bcc');
    }

    /**
     * Set Attachment path.
     *
     * @param mixed $attachment
     */
    public function attachment($attachment = null)
    {
        if (is_null($attachment)) {
            $this->attachment = [];
        } else {
            $this->attachment[] = $attachment;
        }
    }

    /**
     * Set mail subject.
     *
     * @param string $subject
     */
    public function subject($subject)
    {
        $str = preg_replace("/(\r\n|\r|\n)/", ' ', $subject);
        $this->subject = $this->encodeHeader($str);
    }

    /**
     * Set message content.
     *
     * @param string $message
     */
    public function message($message)
    {
        $str = preg_replace("/(\r\n|\r)/", $this->delimiter, $message);
        $this->message = $this->convertText($str);
    }

    /**
     * Set message HTML source.
     *
     * @param string $source
     */
    public function html($source)
    {
        $str = preg_replace("/(\r\n|\r)/", $this->delimiter, $source);
        if (empty($this->message)) {
            $this->message = strip_tags($str);
        }
        $this->html = $this->convertText($str);
    }

    /**
     * Set mail headers.
     *
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value)
    {
        $this->head[$key] = preg_replace("/[\s]+/", ' ', $value);
    }

    /**
     * Normalizing email address.
     *
     * @param string $addr
     *
     * @return string
     */
    public function normalizeAddress($addr)
    {
        if (preg_match('/^([^<]+)<([^>]+)>/', $addr, $match)) {
            $addr = $this->encodeHeader($match[1]).'<'.$match[2].'>';
        }

        return $addr;
    }

    /**
     * Strip email address.
     *
     * @param string $addr
     *
     * @return string
     */
    public function stripAddress($addr)
    {
        return (preg_match('/^[^<]*<([^>]+)>/', $addr, $match)) ? $match[1] : $addr;
    }

    /**
     * Encode header element.
     *
     * @param string $str
     *
     * @return string
     */
    public function encodeHeader($str)
    {
        $encoded = base64_encode($this->convertText($str));

        return '=?'.$this->getCharset().'?B?'.$encoded.'?=';
    }

    /**
     * Convert encoding.
     *
     * @param string $str
     *
     * @return string
     */
    public function convertText($str)
    {
        if ($this->encoding === 'utf-8') {
            return $str;
        }

        return Text::convert($str, $this->encoding);
    }

    /**
     * Create mail header.
     *
     * @param string $boundary
     *
     * @return string
     */
    public function createHeader($boundary)
    {
        $cs = $this->getCharset();
        $dlm = $this->delimiter;
        $header = 'From: '.$this->from.$dlm;
        if (!empty($this->cc)) {
            $header .= 'Cc: '.implode(',', $this->cc).$dlm;
        }
        if (!empty($this->bcc)) {
            $header .= 'Bcc: '.implode(',', $this->bcc).$dlm;
        }
        foreach ($this->head as $key => $value) {
            $header .= "$key: $value".$dlm;
        }
        $header .= 'Mime-Version: 1.0'.$dlm;
        if (empty($this->attachment) && empty($this->html)) {
            $header .= "Content-Type: text/plain; charset=$cs".$dlm;
            $header .= 'Content-Transfer-Encoding: 7bit'.$dlm;
        } else {
            $multipart = (empty($this->html)) ? 'mixed' : 'alternative';
            $header .= "Content-Type: multipart/$multipart; boundary=\"$boundary\"".$dlm;
        }

        return $header;
    }

    /**
     * Create Attachment.
     *
     * @param string $boundary
     * @param string $file
     *
     * @return string
     */
    public function createAttachment($boundary, $file)
    {
        $message = '';
        if (is_array($file)) {
            $mime = $file['mimetype'];
            $basename = $this->encodeHeader($file['filename']);
            $encoded = chunk_split(base64_encode($file['contents']));
        } elseif (is_file($file)) {
            $mime = P5_File::mime($file);
            $basename = $this->encodeHeader($file);
            $encoded = chunk_split(base64_encode(file_get_contents($file)));
        }
        if (!empty($encoded)) {
            $dlm = $this->delimiter;
            $message = $dlm.$dlm.
                        "--$boundary".$dlm.
                        "Content-Type: $mime; name=\"$basename\"".$dlm.
                        "Content-Disposition: attachment; filename=\"$basename\"".$dlm.
                        'Content-Transfer-Encoding: base64'.$dlm.$dlm.
                        $encoded.$dlm;
        }

        return $message;
    }

    /**
     * Create message.
     *
     * @param string $boundary
     *
     * @return string
     */
    public function createMessage($boundary)
    {
        $cs = $this->getCharset();
        $dlm = $this->delimiter;
        if (empty($this->attachment) && empty($this->html)) {
            $message = $this->message;
        } else {
            $message = "--$boundary".$dlm;
            if (empty($this->html)) {
                $message .= "Content-Type: text/plain; charset=$cs".$dlm;
                $message .= 'Content-Transfer-Encoding: 7bit'.$dlm;
                $message .= $dlm;
                $message .= $this->message;
                foreach ($this->attachment as $file) {
                    $message .= $this->createAttachment($boundary, $file);
                }
            } else {
                // Alternative content
                $message .= "Content-Type: text/plain; charset=$cs".$dlm;
                $message .= 'Content-Disposition: inline;'.$dlm;
                $message .= 'Content-Transfer-Encoding: quoted-printable'.$dlm;
                $message .= $dlm;
                $message .= quoted_printable_decode($this->message);
                // HTML content
                $message .= $dlm;
                $message .= "--$boundary".$dlm;
                $message .= "Content-Type: text/html; charset=$cs".$dlm;
                $message .= 'Content-Disposition: inline;'.$dlm;
                $message .= 'Content-Transfer-Encoding: quoted-printable'.$dlm;
                $message .= $dlm;
                $message .= quoted_printable_decode($this->html);
            }
            $message .= $dlm."--$boundary--";
        }

        return $message;
    }

    /**
     * Send Mail.
     *
     * @return bool
     */
    public function send()
    {
        if (empty($this->to)) {
            $this->error = 'Empty Rceipt to Email address.';
            trigger_error($this->error);

            return false;
        }
        $to = implode(',', $this->to);
        $boundary = md5(uniqid(rand()));
        // header
        $header = $this->createHeader($boundary);
        // message
        $message = $this->createMessage($boundary);

        if ($this->smtp === 'localhost') {
            $envfrom = ($this->envfrom !== '') ? '-f'.$this->envfrom : null;

            return mail($to, $this->subject, $message, $header, $envfrom);
        } else {
            return $this->mail($to, $this->subject, $message, $header);
        }
    }

    /**
     * Send Mail by external SMTP server.
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $header
     *
     * @return bool
     */
    public function mail($to, $subject, $message, $header)
    {
        $server = $this->smtp;
        $from = $this->from;

        if (false === $this->open()) {
            return false;
        }

        if ($this->tls === true) {
            $result = $this->command('STARTTLS');
            if (!preg_match('/^220.*$/', $result)) {
                $this->error = $result;

                return false;
            }
            if (false === $this->command("EHLO $server")) {
                fclose($this->socket);
                $this->smtp = "tls://$server";
                $this->port = 465;
                if (false === $this->open()) {
                    return false;
                }
            }
        }

        if (false === $this->auth()) {
            $this->close();

            return false;
        }

        if (false === $this->command('MAIL FROM: <'.$this->stripAddress($from).'>')) {
            return false;
        }

        $rcpt = array_merge($this->to, $this->cc, $this->bcc);
        foreach ($rcpt as $rcpt_to) {
            if (false === $this->command('RCPT TO: <'.$this->stripAddress($rcpt_to).'>')) {
                return false;
            }
        }

        if (false === $this->command('DATA')) {
            return false;
        }

        $dlm = $this->delimiter;
        $content = "Subject: $subject".$dlm.
                   "To: $to".$dlm.
                   "$header".$dlm.
                   "$message".$dlm.
                   $dlm.'.';
        if (false === $result = $this->command($content)) {
            return false;
        }
        if (!preg_match('/^250 /', $result)) {
            $this->error = $result;

            return false;
        }

        return fclose($this->socket);
    }

    /**
     * Send SMTP command.
     *
     * @param string $command
     *
     * @return mixed
     */
    public function command($command)
    {
        fputs($this->socket, $command.$this->delimiter);
        $this->log .= $command.$this->delimiter;
        if (feof($this->socket)) {
            $this->error = 'Lost connection...';
            fclose($this->socket);

            return false;
        }
        $result = fgets($this->socket);
        while (preg_match('/^([0-9]{3})-(.+)$/', $result, $match)) {
            $match[2] = preg_replace("/[\s]+$/", '', $match[2]);
            if ($match[1] === '250') {
                if (empty($this->auth_types) && preg_match('/AUTH[ =](.+)$/i', $match[2], $hit)) {
                    $this->auth_types = explode(' ', $hit[1]);
                    if (is_array($this->auth_types)) {
                        sort($this->auth_types);
                    }
                }
                if ($match[2] === 'STARTTLS') {
                    $this->tls = true;
                }
            }
            $this->log .= $result;
            if ($match[1] >= 400) {
                $this->error = $match[2];

                return false;
            }
            $result = fgets($this->socket);
        }
        $this->log .= $result;
        if (preg_match('/^[45][0-9]{2} (.+)$/', $result, $match)) {
            $this->error = $match[1];

            return false;
        }

        return $result;
    }

    /**
     * Autholize SMTP.
     *
     * @return bool
     */
    public function auth()
    {
        $user = $this->user;
        $passwd = $this->passwd;
        if (empty($this->auth_types) || empty($user)) {
            return true;
        }
        $auth = false;
        foreach ($this->auth_types as $authType) {
            $result = $this->command("AUTH $authType");
            if (preg_match('/^334(.*)$/', $result, $ts)) {
                if ($authType === 'CRAM-MD5') {
                    $cCode = preg_replace("/^[\s]+/", '', $ts[1]);
                    $timestamp = base64_decode($cCode);
                    $str = base64_encode($user.' '.hash_hmac('MD5', $timestamp, $passwd));
                } elseif ($authType === 'LOGIN') {
                    $str = base64_encode($user);
                    $result = $this->command("$str");
                    if (!preg_match('/^334/', $result)) {
                        continue;
                    }
                    $str = base64_encode($passwd);
                } elseif ($authType === 'PLAIN') {
                    $str = base64_encode($user."\0".$user."\0".$passwd);
                }
                $result = $this->command("$str");
                if (preg_match('/^235/', $result)) {
                    $auth = true;
                    break;
                }
            }
        }

        return $auth;
    }

    /**
     * Open connection.
     * 
     * return boolean
     */
    public function open()
    {
        $server = $this->smtp;
        $port = $this->port;
        if (false === $this->socket = fsockopen($server, $port, $errno, $errstr, 5)) {
            echo $errno.':'.$errstr;
            exit;
            $this->error = 'Connection failed SMTP Server ('.$server.')';

            return false;
        }
        $this->log .= 'Start connection SMTP Server ('.$server.')'.$this->delimiter;
        $this->log .= fgets($this->socket);
        $server = preg_replace("/^.+:\/\//", '', $server);

        return $this->command("EHLO $server");
    }

    /**
     * Close connection.
     *
     * return boolean
     */
    public function close()
    {
        $result = $this->command('QUIT');

        return fclose($this->socket);
    }

    /** 
     * SMTP log.
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /** 
     * Error message.
     *
     * @return string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Character set for message.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset[$this->encoding];
    }
}
