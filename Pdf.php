<?php
/**
 * This file is part of P5 Framework
 *
 * Copyright (c)2016 PlusFive (http://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
include_once('tcpdf/tcpdf.php');
include_once('fpdi/fpdi.php');

/**
 * PDF class
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Pdf extends FPDI
{
    /** 
     * Current version
     */
    const VERSION = '1.1.0';
}
