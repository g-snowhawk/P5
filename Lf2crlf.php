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
 * Convert LF to CRLF for stream filter.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Lf2crlf extends php_user_filter
{
    /** 
     * Current version.
     */
    const VERSION = '1.1.0';

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = mb_convert_encoding(
                preg_replace("/(?<!\r)\n/", "\r\n", $bucket->data),
                $this->params['to'],
                mb_internal_encoding()
            );
            $consumed += strlen($bucket->data);
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
