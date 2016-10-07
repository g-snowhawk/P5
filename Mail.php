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
 * @copyright Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @version   $Id: Mail.php 2012-01-03 11:15:45 tak@ $
 */

/**
 * @category   P5
 * @package    P5_Mail
 * @copyright  Copyright (c) 2012 PlusFive. (http://www.plus-5.com)
 * @license    GNU General Public License
 */
class P5_Mail
{
    /**
     * Current version.
     */
    const VERSION = '1.0.0';

    /**
     * SMTP hostname or IP address.
     *
     * @var string
     */
    private $_smtp = '';

    /**
     * Port number of SMTP server
     *
     * @var string
     */
    private $_port = '';

    /**
     * SMTP-Auth username
     *
     * @var string
     */
    private $_user = '';

    /**
     * SMTP-Auth password
     *
     * @var string
     */
    private $_passwd = '';

    /**
     * Use TLS
     *
     * @var boolean
     */
    private $_tls = false;

    /**
     * Authentication Types
     *
     * @var array
     */
    private $_authTypes = array();

    /**
     * Mail sender address
     *
     * @var string
     */
    private $_from = '';

    /**
     * Mail envelope sender address
     *
     * @var string
     */
    private $_envfrom = '';

    /**
     * Mail subject
     *
     * @var string
     */
    private $_subject = '';

    /**
     * Plain text content
     *
     * @var string
     */
    private $_message = '';

    /**
     * HTML content
     *
     * @var string
     */
    private $_html = '';

    /**
     * Mailto Addresses
     *
     * @var array
     */
    private $_to = array();

    /**
     * Carbon copy Addresses
     *
     * @var array
     */
    private $_cc = array();

    /**
     * Blind carbon copy Addresses
     *
     * @var array
     */
    private $_bcc = array();

    /**
     * Mail headers
     *
     * @var array
     */
    private $_head = array();

    /**
     * Mail attachments
     *
     * @var array
     */
    private $_attachment = array();

    /**
     * SMTP stream
     *
     * @var stream
     */
    private $_socket;

    /**
     * log
     *
     * @var string
     */
    private $_log = '';

    /**
     * Error message
     *
     * @var string
     */
    private $_error = '';

    /**
     * Delimiter
     *
     * @var string
     */
    private $_delimiter = "\n";

    /**
     * encode
     *
     * @var string
     */
    private $_enc;

    /**
     * Caracterset
     *
     * @var array
     */
    private $_charset = array (
        'jis'   => 'ISO-2022-JP',
        'sjis'  => 'Shift_JIS',
        'utf-8' => 'UTF-8',
    );

    /**
     * Object constructor
     *
     * @param  string   $host
     * @param  number   $port
     * @param  string   $user
     * @param  string   $passwd
     * @return void
     */
    public function __construct($host = '', $port = '', $user = '', $passwd = '', $enc = 'jis')
    {
        $this->_smtp   = $this->setHost($host);
        $this->_port   = $this->setPort($port);
        $this->_user   = $user;
        $this->_passwd = $passwd;
        $this->_enc    = $enc;
    }

    /**
     * SMTP host 
     *
     * @param  string   $host
     * @return string
     */
    public function setHost($host = '')
    {
        if (!empty($host)) return $host;
        $default = ini_get('SMTP');
        $host = (empty($defailt)) ? 'localhost' : $defailt;
        // Windows OS
        if (preg_match("/^WIN/i", PHP_OS)) {
            if ($this->_smtp != 'localhost' && $host != ini_get('SMTP')) {
                ini_set('SMTP', $host);
            }
        }
        return $host;
    }

    /**
     * SMTP port
     *
     * @param  number   $port
     * @return string
     */
    public function setPort($port = null)
    {
        if (!empty($port)) return $port;
        $default = ini_get('smtp_port');
        return (!empty($default)) ? $default : 25;
    }

    /**
     * SET Encoding
     *
     * @param string $enc
     * @return string
     */
    public function setEncoding($enc)
    {
        return $this->_enc = $enc;
    }

    /**
     * Set envelope From address
     *
     * @param string $envfrom
     * @return void
     */
    public function envfrom($envfrom)
    {
        $this->_envfrom = $this->normalizeAddress($envfrom);
    }

    /**
     * Set From address
     *
     * @param  string   $from
     * @return void
     */
    public function from($from)
    {
        $this->_from = $this->normalizeAddress($from);
    }

    /**
     * Set To address
     *
     * @param  string   $to
     * @param  string   $prop
     * @return void
     */
    public function to($to = null, $prop = '_to')
    {
        if (is_null($to)) {
            $this->$prop = array();
        } else if (is_array($to)) {
            foreach($to as $value) {
                array_push($this->$prop, $this->normalizeAddress($value));
            }
        } else {
            array_push($this->$prop, $this->normalizeAddress($to));
        }
    }

    /**
     * Set Cc address
     *
     * @param  string   $cc
     * @return void
     */
    public function cc($cc = null)
    {
        $this->to($cc, '_cc');
    }

    /**
     * Set Bcc address
     *
     * @param  string   $bcc
     * @return void
     */
    public function bcc($bcc = null)
    {
        $this->to($bcc, '_bcc');
    }

    /**
     * Set Attachment path
     *
     * @param  mixed    $attachment
     * @return void
     */
    public function attachment($attachment = null)
    {
        if (is_null($attachment)) {
            $this->_attachment = array();
        } else {
            $this->_attachment[] = $attachment;
        }
    }

    /**
     * Set mail subject
     *
     * @param  string   $subject
     * @return void
     */
    public function subject($subject)
    {
		$str = preg_replace("/(\r\n|\r|\n)/", " ", $subject);
		$this->_subject = $this->encodeHeader($str);
    }

    /**
     * Set message content
     *
     * @param  string   $message
     * @return void
     */
    public function message($message)
    {
        $str = preg_replace("/(\r\n|\r)/", $this->_delimiter, $message);
        $this->_message = $this->convertText($str);
    }

    /**
     * Set message HTML source
     *
     * @param  string   $source
     * @return void
     */
    public function html($source)
    {
        $str = preg_replace("/(\r\n|\r)/", $this->_delimiter, $source);
        if (empty($this->_message)) {
            $this->_message = strip_tags($str);
        }
        $this->_html = $this->convertText($str);
    }

    /**
     * Set mail headers
     *
     * @param  string   $key
     * @param  string   $value
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->_head[$key] = preg_replace("/[\s]+/", " ", $value);
    }

    /**
     * Normalizing email address
     *
     * @param  string   $addr
     * @return string
     */
    public function normalizeAddress($addr)
    {
        if (preg_match("/^([^<]+)<([^>]+)>/", $addr, $match)) {
            $addr = $this->encodeHeader($match[1]) . '<' . $match[2] . '>';
        }
        return $addr;
    }

    /**
     * Strip email address
     *
     * @param  string   $addr
     * @return string
     */
    public function stripAddress($addr)
    {
        return (preg_match("/^[^<]*<([^>]+)>/", $addr, $match)) ? $match[1] : $addr;
    }

    /**
     * Encode header element
     *
     * @param  string   $str
     * @return string
     */
    public function encodeHeader($str)
    {
        $encoded = base64_encode($this->convertText($str));
        return '=?' . $this->getCharset() . '?B?' . $encoded . '?=';
    }

    /**
     * Convert encoding
     *
     * @param  string   $str
     * @return string
     */
    public function convertText($str)
    {
        if ($this->_enc === 'utf-8') return $str;
        return P5_Text::convert($str, $this->_enc);
    }

    /**
     * Create mail header
     *
     * @param  string   $boundary
     * @return string
     */
    public function createHeader($boundary)
    {
        $cs = $this->getCharset();
        $dlm = $this->_delimiter;
        $header = 'From: ' . $this->_from . $dlm;
        if (!empty($this->_cc)) {
            $header .= 'Cc: ' . implode(',', $this->_cc) . $dlm;
        }
        if (!empty($this->_bcc)) {
            $header .= 'Bcc: ' . implode(',', $this->_bcc) . $dlm;
        }
        foreach ($this->_head as $key => $value) {
            $header .= "$key: $value" . $dlm;
        }
        $header .= "Mime-Version: 1.0" . $dlm;
        if (empty($this->_attachment) && empty($this->_html)) {
            $header .= "Content-Type: text/plain; charset=$cs" . $dlm;
            $header .= "Content-Transfer-Encoding: 7bit" . $dlm;
        } else {
            $multipart = (empty($this->_html)) ? 'mixed' : 'alternative';
            $header .= "Content-Type: multipart/$multipart; boundary=\"$boundary\"" . $dlm;
        }
        return $header;
    }

    /**
     * Create Attachment
     *
     * @param  string   $boundary
     * @param  string   $file
     * @return string
     */
    public function createAttachment($boundary, $file)
    {
        $message = '';
        if (is_array($file)) {
            $mime     = $file['mimetype'];
            $basename = $this->encodeHeader($file['filename']);
            $encoded  = chunk_split(base64_encode($file['contents']));
        } else
        if (is_file($file)) {
            $mime     = P5_File::mime($file);
            $basename = $this->encodeHeader($file);
            $encoded  = chunk_split(base64_encode(file_get_contents($file)));
        }
        if (!empty($encoded)) {
            $dlm      = $this->_delimiter;
            $message  = $dlm . $dlm .
                        "--$boundary" . $dlm .
                        "Content-Type: $mime; name=\"$basename\"" . $dlm .
                        "Content-Disposition: attachment; filename=\"$basename\"" . $dlm .
                        "Content-Transfer-Encoding: base64" . $dlm . $dlm .
                        $encoded . $dlm;
        }
        return $message;
    }

    /**
     * Create message
     *
     * @param  string   $boundary
     * @return string
     */
    public function createMessage($boundary)
    {
        $cs = $this->getCharset();
        $dlm = $this->_delimiter;
        if (empty($this->_attachment) && empty($this->_html)) {
            $message = $this->_message;
        } else {
            $message = "--$boundary" . $dlm;
            if (empty($this->_html)) {
                $message .= "Content-Type: text/plain; charset=$cs" . $dlm;
                $message .= "Content-Transfer-Encoding: 7bit" . $dlm;
                $message .= $dlm;
                $message .= $this->_message;
                foreach($this->_attachment as $file) {
                    $message .= $this->createAttachment($boundary, $file);
                }
            } else {
                // Alternative content
                $message .= "Content-Type: text/plain; charset=$cs" . $dlm;
                $message .= "Content-Disposition: inline;" . $dlm;
                $message .= "Content-Transfer-Encoding: quoted-printable" . $dlm;
                $message .= $dlm;
                $message .= quoted_printable_decode($this->_message);
                // HTML content
                $message .= $dlm;
                $message .= "--$boundary" . $dlm;
                $message .= "Content-Type: text/html; charset=$cs" . $dlm;
                $message .= "Content-Disposition: inline;" . $dlm;
                $message .= "Content-Transfer-Encoding: quoted-printable" . $dlm;
                $message .= $dlm;
                $message .= quoted_printable_decode($this->_html);
            }
            $message .= $dlm . "--$boundary--";
        }
        return $message;
    }

    /**
     * Send Mail
     *
     * @return boolean
     */
    public function send()
    {
        if (empty($this->_to)) {
            $this->_error = 'Empty Rceipt to Email address.';
            return false;
        }
        $to = implode(',', $this->_to);
        $boundary = md5(uniqid(rand()));
        // header
        $header = $this->createHeader($boundary);
        // message
        $message = $this->createMessage($boundary);

        if ($this->_smtp === 'localhost') {
            $envfrom = ($this->_envfrom !== '') ? '-f'.$this->_envfrom : null;
            return mail($to, $this->_subject, $message, $header, $envfrom);
        } else {
            return $this->mail($to, $this->_subject, $message, $header);
        }
    }

    /**
     * Send Mail by external SMTP server.
     *
     * @param  string   $to
     * @param  string   $subject
     * @param  string   $message
     * @param  string   $header
     * @return boolean
     */
    public function mail($to, $subject, $message, $header)
    {
        $server = $this->_smtp;
        $from   = $this->_from;

        if (false === $this->open()) return false;

        if ($this->_tls === true) {
            $result = $this->command("STARTTLS");
            if (! preg_match("/^220.*$/", $result)) {
                $this->_error = $result;
                return false;
            }
            if (false === $this->command("EHLO $server")) {
                fclose($this->_socket);
                $this->_smtp = "tls://$server";
                $this->_port = 465;
                if (false === $this->open()) return false;
            }
        }

        if (false === $this->auth()) {
            $this->close();
            return false;
        }

        if (false === $this->command("MAIL FROM: <" . $this->stripAddress($from) . ">")) {
            return false;
        }

        $rcpt = array_merge($this->_to, $this->_cc, $this->_bcc);
        foreach ($rcpt as $rcpt_to) {
            if (false === $this->command("RCPT TO: <" . $this->stripAddress($rcpt_to) . ">")) {
                return false;
            }
        }

        if (false === $this->command("DATA")) return false;

        $dlm = $this->_delimiter;
        $content = "Subject: $subject" . $dlm .
                   "To: $to" . $dlm .
                   "$header" . $dlm .
                   "$message" . $dlm .
                   $dlm . ".";
        if (false === $result = $this->command($content)) return false;
        if(!preg_match("/^250 /", $result)) {
            $this->_error = $result;
            return false;
        }
        return fclose($this->_socket);
    }

    /**
     * Send SMTP command
     *
     * @param  string   $command
     * @return mixed
     */
    public function command($command)
    {
        fputs($this->_socket, $command . $this->_delimiter);
        $this->_log .= $command . $this->_delimiter;
        if (feof($this->_socket)) {
            $this->_error = 'Lost connection...';
            fclose($this->_socket);
            return false;
        }
        $result = fgets($this->_socket);
        while (preg_match("/^([0-9]{3})-(.+)$/", $result, $match)) {
            $match[2] = preg_replace("/[\s]+$/", '', $match[2]);
            if ($match[1] === '250') {
                if (empty($this->_authTypes) && preg_match("/AUTH[ =](.+)$/i", $match[2], $hit)) {
                    $this->_authTypes = explode(' ', $hit[1]);
                    if (is_array($this->_authTypes)) sort($this->_authTypes);
                }
                if ($match[2] === 'STARTTLS') $this->_tls = true;
            }
            $this->_log .= $result;
            if ($match[1] >= 400) {
                $this->_error = $match[2];
                return false;
            }
            $result = fgets($this->_socket);
        }
        $this->_log .= $result;
        if (preg_match("/^[45][0-9]{2} (.+)$/", $result, $match)) {
            $this->_error = $match[1];
            return false;
        }
        return $result;
    }

    /**
     * Autholize SMTP
     *
     * @return boolean
     */
    public function auth()
    {
        $user   = $this->_user;
        $passwd = $this->_passwd;
        if (empty($this->_authTypes) || empty($user)) return true;
        $auth = false;
        foreach ($this->_authTypes as $authType) {
            $result = $this->command("AUTH $authType");
            if (preg_match("/^334(.*)$/", $result, $ts)) {
                if ($authType === 'CRAM-MD5') {
                    $cCode = preg_replace("/^[\s]+/", "", $ts[1]);
                    $timestamp = base64_decode($cCode);
                    $str = base64_encode($user . ' ' . hash_hmac('MD5', $timestamp, $passwd));
                } else if ($authType === 'LOGIN') {
                    $str = base64_encode($user);
                    $result = $this->command("$str");
                    if (! preg_match("/^334/", $result)) continue;
                    $str = base64_encode($passwd);
                } else if ($authType === 'PLAIN') {
                    $str = base64_encode($user . "\0" . $user . "\0" . $passwd);
                }
                $result = $this->command("$str");
                if (preg_match("/^235/", $result)) {
                    $auth = true;
                    break;
                }
            }
        }
        return $auth;
    }

    /**
     * Open connection
     * 
     * return boolean
     */
    public function open()
    {
        $server = $this->_smtp;
        $port   = $this->_port;
        if (false === $this->_socket = fsockopen($server, $port, $errno, $errstr, 5)) {
            echo $errno . ':' . $errstr;exit;
            $this->_error = 'Connection failed SMTP Server (' . $server . ')';
            return false;
        }
        $this->_log .= 'Start connection SMTP Server (' . $server . ')' . $this->_delimiter;
        $this->_log .= fgets($this->_socket);
        $server = preg_replace("/^.+:\/\//", "", $server);
        return $this->command("EHLO $server");
    }

    /**
     * Close connection
     *
     * return boolean
     */
    public function close()
    {
        $result = $this->command("QUIT");
        return fclose($this->_socket);
    }

    /** 
     * SMTP log
     *
     * @return string
     */
    public function getLog()
    {
        return $this->_log;
    }

    /** 
     * Error message
     *
     * @return string
     */
    public function error()
    {
        return $this->_error;
    }

    /**
     * Character set for message
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset[$this->_enc];
    }
}
