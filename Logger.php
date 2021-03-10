<?php
/**
 * This file is part of P5 Framework.
 *
 * Copyright (c)2021 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace P5;

class Logger
{
    private $db;
    private $statement;
    private $path;
    private $format;

    public function __construct($source, $format)
    {
        $this->format = $format;
        if (is_object($source) && is_a($source, 'Db')) {
            $this->db = clone $source;
            $this->statement = $this->db->prepare($format);
        } elseif (file_exists($source)) {
            $this->path = $source;
        } else {
            trigger_error("No source for logging");
        }
    }

    public function log(array $log)
    {
        if (!empty($this->db)) {
            $this->toDatabase($log);
        } elseif (!empty($this->path)) {
            $this->toFile($log);
        } else {
            trigger_error(implode("\t", $log));
        }
    }

    private function toDatabase(array $log)
    {
        try {
            $this->statement->execute(array_values($log));
        } catch (\ErrorException $e) {
            trigger_error($e->getMessage());
        }
    }

    private function toFile(array $log)
    {
        @file_put_contents(
            $this->path,
            sprintf($this->format, $log),
            FILE_APPEND | LOCK_EX
        );
    }
}
