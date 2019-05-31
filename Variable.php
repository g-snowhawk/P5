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
 * Complement the variable processing.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Variable
{
    /**
     * Check hash.
     *
     * @param array $var
     *
     * @return bool
     */
    public static function isHash(&$var)
    {
        if (is_null($var) || !is_array($var)) {
            return false;
        }

        return $var !== array_values($var);
    }
}
