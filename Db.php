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
 * Database connection class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Db
{
    /**
     * Database Type.
     *
     * @var string
     */
    private $driver;

    /**
     * Database name.
     *
     * @var string
     */
    private $source;

    /**
     * server port.
     *
     * @var string
     */
    private $port;

    /**
     * Database Handler.
     *
     * @var PDO
     */
    private $handler;

    /**
     * PDOStatement Object.
     *
     * @var PDOStatement
     */
    private $statement;

    /**
     * SQL string.
     * 
     * @vat string
     */
    private $sql;

    /**
     * Error Code.
     *
     * @var int|string
     */
    private $error_code;

    /**
     * Error Message.
     *
     * @var string
     */
    private $error_message;

    /**
     * Database.
     *
     * @var string
     */
    private $dsn;

    /**
     * Database user name.
     *
     * @var string
     */
    private $user;

    /**
     * Database access password.
     *
     * @var string
     */
    private $password;

    /**
     * Database encoding.
     *
     * @var string
     */
    private $encoding;

    /**
     * excute counter.
     *
     * @var int
     */
    private $ecount;

    /*
     * PDO Attributes
     *
     * @var array
     */
    private $options = [];

    /**
     * Object Constructor.
     *
     * @param string $driver   Database driver
     * @param string $host     Database server host name or IP address
     * @param string $source   Data source
     * @param string $user     Database user name
     * @param string $password Database password
     * @param string $port     Database server port
     * @param string $enc      Database encoding
     */
    public function __construct($driver, $host, $source, $user, $password, $port = 3306, $enc = '')
    {
        $this->driver = $driver;
        $this->source = $source;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->encoding = $enc;
        if ($this->driver != 'sqlite' && $this->driver != 'sqlite2') {
            $this->dsn = "$driver:host=$host;port=$port;dbname=$source";
            if ($enc !== '') {
                if (file_exists($enc)) {
                    $this->options[\PDO::MYSQL_ATTR_READ_DEFAULT_FILE] = $enc;
                    $content = str_replace('#', ';', file_get_contents($enc));
                    $init = parse_ini_string($content, true);
                    if (isset($init['client']['default-character-set'])) {
                        $this->encoding = $init['client']['default-character-set'];
                    }
                } else {
                    $this->dsn .= ";charset=$enc";
                }
            }
            if ($this->driver === 'mysql') {
                $this->options[\PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
                $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SQL_MODE='ANSI_QUOTES';";
            }
            $this->options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        } else {
            if (!file_exists($host)) {
                mkdir($host, 0777, true);
            }
            $this->dsn = "$driver:$host/$source";
            if (!empty($port)) {
                $this->dsn .= ".$port";
            }
        }
    }

    /**
     * Clone this class.
     */
    public function __clone()
    {
    }

    /** 
     * Getter method.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $key = '_'.$name;
        if (true === property_exists($this, $key)) {
            switch ($key) {
                case '_driver' :
                    return $this->$key;
            }
        }

        return;
    }

    /**
     * Create database.
     *
     * @param string $db_name
     *
     * @return bool
     */
    public function create($db_name = null)
    {
        try {
            if (empty($db_name)) {
                $db_name = $this->source;
            }
            $dsn = "{$this->driver}:{$this->host};port={$this->port}";
            $this->handler = new \PDO($dsn, $this->user, $this->password);
            $this->handler->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if ($this->driver === 'mysql') {
                $sql = "CREATE DATABASE $db_name";
                if (!empty($this->encoding)) {
                    $sql .= " DEFAULT CHARACTER SET {$this->encoding}";
                }
                $this->handler->query($sql);
            }
            if ($this->driver === 'pgsql') {
                $this->handler->query(
                    "CREATE DATABASE {$db_name} ENCODING '{$this->encoding}'"
                );
            }
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * set driver options.
     *
     * @param array $options
     */
    public function addOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Open database connection.
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function open($timeout = null)
    {
        try {
            if (!is_null($timeout)) {
                $this->options[\PDO::ATTR_TIMEOUT] = $timeout;
            }
            $this->handler = new \PDO($this->dsn, $this->user, $this->password, $this->options);
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Execute SQL.
     *
     * @param string $sql
     *
     * @return mixed
     */
    public function exec($sql)
    {
        $this->sql = $this->normalizeSQL($sql);
        try {
            $this->ecount = $this->handler->exec($this->sql);
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return $this->ecount;
    }

    /**
     * Execute SQL.
     *
     * @param string $sql
     *
     * @return mixed
     */
    public function query($sql, $options = null)
    {
        $this->sql = $this->normalizeSQL($sql);
        try {
            if (is_array($options)) {
                $this->prepare($this->sql);
                $this->execute($options);
            } else {
                $this->statement = $this->handler->query($this->sql);
            }
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return $this->statement;
    }

    /**
     * exec insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function insert($table, array $data, $raws = null, $fields = null)
    {
        if (is_null($fields)) {
            $fields = $this->getFields($table, true);
        }
        $data = (\P5\Variable::isHash($data)) ? [$data] : $data;
        $raws = (\P5\Variable::isHash($raws)) ? [$raws] : $raws;
        $cnt = 0;
        $keys = [];
        $rows = [];
        foreach ($data as $n => $unit) {
            $vals = [];
            foreach ($unit as $key => $value) {
                if ($cnt === 0) {
                    $keys[] = "\"$key\"";
                }
                $fZero = (isset($fields[$key]) &&
                    isset($fields[$key]['Type']) &&
                    self::is_number($fields[$key]['Type'])
                ) ? true : false;
                $vals[] = (is_null($value)) ? 'NULL' : $this->quote($value, $fZero);
            }
            if (isset($raws[$n])) {
                foreach ($raws[$n] as $key => $value) {
                    if ($cnt === 0) {
                        $keys[] = "\"$key\"";
                    }
                    $vals[] = $value;
                }
            }
            $rows[] = '('.implode(',', $vals).')';
            ++$cnt;
        }
        $sql = '('.implode(',', $keys).') VALUES '.implode(',', $rows);

        return $this->exec("INSERT INTO \"$table\" $sql");
    }

    /**
     * exec update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param string $statement
     * @param array  $options
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function update($table, $data, $statement = '', $options = [], $raws = null, $fields = null)
    {
        if (is_null($fields)) {
            $fields = $this->getFields($table, true);
        }
        $pair = [];
        foreach ($data as $key => $value) {
            $type = (isset($fields[$key]['Type'])) ? $fields[$key]['Type'] : null;
            $fZero = (isset($fields[$key]) && self::is_number($type)) ? true : false;
            $value = (is_null($value)) ? 'NULL' : $this->quote($value, $fZero);
            $pair[] = "\"$key\" = $value";
        }
        if (is_array($raws)) {
            foreach ($raws as $key => $value) {
                $pair[] = "\"$key\" = ".$value;
            }
        }
        $sql = 'SET '.implode(',', $pair);
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }

        return $this->exec("UPDATE \"$table\" $sql");
    }

    /**
     * exec delete SQL.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function delete($table, $statement = '', $options)
    {
        $sql = (!empty($statement) && !empty($options)) ?
            'WHERE '.$this->prepareStatement($statement, $options) : '';

        return $this->exec("DELETE FROM \"$table\" $sql");
    }

    /**
     * exec update or insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     *
     * @return mixed
     */
    public function updateOrInsert($table, array $data, $unique, $raws = [])
    {
        $ecount = 0;
        if (\P5\Variable::isHash($data)) {
            $data = [$data];
        }
        foreach ($data as $unit) {
            $keys = array_keys($unit);
            $update = $unit;
            $where = [];
            foreach ($unique as $key) {
                $where[] = $unit[$key];
                $arr[] = "{$key} = ?";
                if (in_array($key, $keys)) {
                    unset($update[$key]);
                }
            }
            $statement = implode(' AND ', $arr);
            if (false === $ret = self::update($table, $update, $statement, $where, $raws)) {
                return false;
            }
            if ($ret === 0 && !self::exists($table, $statement, $where)) {
                if (false === $ret = self::insert($table, $unit, $raws)) {
                    return false;
                }
            }
            $ecount += $ret;
        }
        $this->ecount = $ecount;

        return $this->ecount;
    }

    /**
     * exec insert or update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function replace($table, array $data, $unique, $raws = [], $fields = null)
    {
        $ecount = 0;
        if (is_null($fields)) {
            $fields = $this->getFields($table, true);
        }
        if (\P5\Variable::isHash($data)) {
            $data = [$data];
        }
        $cnt = 0;
        $keys = [];
        $dest = [];
        $cols = '';
        foreach ($data as $unit) {
            $vals = [];
            foreach ($unit as $key => $value) {
                if (empty($cols)) {
                    $keys[] = "\"$key\"";
                }
                $fZero = (isset($fields[$key]) &&
                    isset($fields[$key]['Type']) &&
                    self::is_number($fields[$key]['Type'])) ? true : false;
                $vals[] = (is_null($value)) ? 'NULL' : $this->quote($value, $fZero);
                if (!in_array($key, $unique)) {
                    $dest[] = "\"$key\" = ".$this->quote($value, $fZero);
                }
            }
            foreach ($raws as $key => $value) {
                if (empty($cols)) {
                    $keys[] = "\"$key\"";
                }
                $vals[] = $value;
            }
            if (empty($cols)) {
                $cols = implode(',', $keys);
            }
            $sql = "($cols) VALUES (".implode(',', $vals).')';
            if ($this->driver == 'mysql') {
                $sql = "INSERT INTO \"$table\" $sql ON DUPLICATE KEY UPDATE ".implode(',', $dest);
            } elseif ($this->driver == 'pgsql') {
                $where = [];
                $arr = [];
                foreach ($unique as $key) {
                    $where[] = $unit[$key];
                    $arr[] = "{$key} = ?";
                    unset($unit[$key]);
                }
                if (false === $ret = self::update($table, $unit, implode(' AND ', $arr), $where, $raws)) {
                    return false;
                }
                if ($ret > 0) {
                    continue;
                }
                $sql = "INSERT INTO \"$table\" $sql";
            } elseif ($this->driver == 'sqlite' || $this->driver == 'sqlite2') {
                $sql = "INSERT INTO \"$table\" $sql";
                if (false === $ret = $this->exec($sql)) {
                    if (preg_match('/columns? (.+) (is|are) not unique/i', $this->error(), $match)) {
                        $unique = Text::explode(',', $match[1]);
                        $where = [];
                        $arr = [];
                        foreach ($unique as $key) {
                            $where[] = $unit[$key];
                            $arr[] = "{$key} = ?";
                            unset($unit[$key]);
                        }
                        if (false === $ret = self::update($table, $unit, implode(' AND ', $arr), $where, $raws)) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
                $ecount += $ret;
                continue;
            }
            if (false === $ret = $this->exec($sql)) {
                return false;
            }
            $ecount += $ret;
        }
        $this->ecount = $ecount;

        return $this->ecount;
    }

    /**
     * Select.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function select($columns, $table, $statement = '', $options = [])
    {
        $columns = self::verifyColumns($columns);
        $sql = "SELECT $columns FROM $table";
        if (!empty($statement) && is_array($options)) {
            $sql .= ' '.$this->prepareStatement($statement, $options);
        }
        if ($this->query($sql)) {
            return $this->fetchAll();
        }

        return false;
    }

    /**
     * Exists Records.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return bool
     */
    public function exists($table, $statement = '', $options = [])
    {
        $sql = "SELECT COUNT(*) AS cnt FROM $table";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }
        if ($this->query($sql)) {
            $cnt = $this->fetchColumn();

            return 0 < (int) $cnt;
        }

        return false;
    }

    /**
     * RecordCount.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function count($table, $statement = '', $options = [])
    {
        $sql = "SELECT COUNT(*) AS cnt FROM $table";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }

        return ($this->query($sql)) ? (int) $this->fetchColumn() : false;
    }

    /**
     * Get Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function get($column, $table, $statement = '', $options = [])
    {
        $column = self::verifyColumns($column);
        $sql = "SELECT $column FROM $table";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }
        if (false === $this->query($sql)) {
            return false;
        }
        $ret = $this->fetch();
        if (!is_array($ret)) {
            return $ret;
        } elseif (count($ret) > 1) {
            return $ret;
        }

        return array_shift($ret);
    }

    /**
     * MIN Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function min($column, $table, $statement = '', $options = [])
    {
        $sql = "SELECT MIN($column) FROM \"$table\"";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }
        if ($this->query($sql)) {
            return $this->fetchColumn();
        }

        return false;
    }

    /**
     * MAX Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function max($column, $table, $statement = '', $options = [])
    {
        $sql = "SELECT MAX($column)
                  FROM \"$table\"";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }
        if ($this->query($sql)) {
            return $this->fetchColumn();
        }

        return false;
    }

    /**
     * Prepare.
     *
     * @param string $statement
     *
     * @return mixed
     */
    public function prepare($statement)
    {
        $statement = $this->normalizeSQL($statement);

        return $this->statement = $this->handler->prepare($statement);
    }

    /**
     * Execute.
     *
     * @param array $params
     *
     * @return bool
     */
    public function execute($input_parameters)
    {
        if (false !== $this->statement->execute($input_parameters)) {
            return $this->statement->rowCount();
        }

        return false;
    }

    /**
     * Prepare statement.
     *
     * @param string $statement
     * @param array  $options
     *                          return string
     */
    public function prepareStatement($statement, array $options)
    {
        $statement = $this->normalizeSQL($statement);
        if ($options !== array_values($options)) {
            $pattern = [];
            $replace = [];
            $holder = [];
            foreach ($options as $key => $option) {
                if (is_int($key)) {
                    $holder[] = $option;
                    continue;
                }
                $option = str_replace('$', '$\\', $option);
                $key = preg_replace('/^:/', '', $key, 1);
                $pattern[] = '/'.preg_quote(preg_replace('/^:?/', ':', $key, 1), '/').'/';
                $replace[] = $this->quote($option);
            }
            $statement = preg_replace($pattern, $replace, $statement);
        } else {
            $holder = $options;
        }
        foreach ($holder as $option) {
            $option = str_replace('$', '$\\', $option);
            $statement = preg_replace("/\?/", $this->quote($option), $statement, 1);
        }

        return str_replace('$\\', '$', $statement);
    }

    /**
     * result of query.
     *
     * @param int $type
     * @param int $cursor
     * @param int $offset
     *
     * @return mixed
     */
    public function fetch($type = \PDO::FETCH_ASSOC, $cursor = \PDO::FETCH_ORI_NEXT, $offset = 0)
    {
        try {
            $data = $this->statement->fetch($type, $cursor, $offset);
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return $data;
    }

    /**
     * result of query.
     *
     * @param mixed $type
     *
     * @return mixed
     */
    public function fetchAll($type = \PDO::FETCH_ASSOC, $columnIndex = 0)
    {
        try {
            if ($type == \PDO::FETCH_COLUMN) {
                $data = $this->statement->fetchAll($type, $columnIndex);
            } else {
                $data = $this->statement->fetchAll($type);
            }
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return $data;
    }

    /**
     * result of query.
     *
     * @param mixed $type
     *
     * @return mixed
     */
    public function fetchColumn($column_number = 0)
    {
        try {
            $data = $this->statement->fetchColumn($column_number);
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();

            return false;
        }

        return $data;
    }

    /**
     * escape string.
     *
     * @param mixed $value
     * @param int   $force
     *
     * @return string
     */
    public function quote($value, $force = null)
    {
        if (!is_null($force)) {
            $parameter_type = (int) $force;
        } elseif (is_null($value)) {
            $parameter_type = \PDO::PARAM_NULL;
        } elseif (preg_match('/^[0-9]+$/', $value)) {
            $parameter_type = \PDO::PARAM_INT;
        } else {
            $parameter_type = \PDO::PARAM_STR;
        }
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }

        return $this->handler->quote($value, $parameter_type);
    }

    /**
     * escape table or column name.
     *
     * @return string
     */
    public function escapeName($str)
    {
        $str = $this->handler->quote($str);
        $str = preg_replace("/^'/", '"', $str, 1);
        $str = preg_replace("/'$/", '"', $str, 1);

        return $str;
    }

    /**
     * PDOStatement::getColumnMeta is EXPERIMENTAL.
     * The behaviour of this function, its name, and surrounding documentation 
     * may change without notice in a future release of PHP.
     *
     * @return array
     */
    public function fields($data = null)
    {
        $result = [];
        if (is_array($data)) {
            return array_keys($data);
        }
        if ($this->driver == 'sqlite' || $this->driver == 'sqlite2') {
            if (preg_match("/^SELECT\s+.+\s+FROM\s[`'\"]?(\w+)[`'\"]?.*$/i", $this->sql, $match)) {
                $tableName = $match[1];
            } elseif (preg_match("/^UPDATE\s+[`'\"]?(\w+)[`'\"]?.*$/i", $this->sql, $match)) {
                $tableName = $match[1];
            }
            $sql = "PRAGMA table_info($tableName)";
            if ($this->query($sql)) {
                $result = $this->fetchAll(\PDO::FETCH_COLUMN, 1);
            }
        } else {
            for ($i = 0; $i < $this->statement->columnCount(); ++$i) {
                $meta = $this->statement->getColumnMeta($i);
                $result[] = $meta['name'];
            }
        }

        return $result;
    }

    /**
     * begin transaction.
     *
     * @param string $identifire
     *
     * @return bool
     */
    public function begin($identifire = '')
    {
        if ($this->handler->inTransaction() || $this->handler->beginTransaction()) {
            if (!empty($identifire)) {
                $this->query("SAVEPOINT $identifire");
            }

            return true;
        }

        return false;
    }

    /**
     * commit transaction.
     *
     * @return bool
     */
    public function commit()
    {
        if ($this->handler->commit()) {
            return true;
        }

        return false;
    }

    /**
     * rollback transaction.
     *
     * @param string $identifire
     *
     * @return bool
     */
    public function rollback($identifire = '')
    {
        if (false === $this->handler->inTransaction()) {
            return true;
        }
        if (!empty($identifire)) {
            if ($this->query("ROLLBACK TO SAVEPOINT $identifire")) {
                return true;
            }
        }
        try {
            if ($this->handler->rollBack()) {
                return true;
            }
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }

        return false;
    }

    /**
     * transaction exists.
     *
     * @return bool
     */
    public function getTransaction()
    {
        return $this->handler->inTransaction();
    }

    /**
     * record count of execute query.
     *
     * @return int
     */
    public function recordCount($sql = '', $options = null)
    {
        if (empty($sql)) {
            $sql = $this->sql;
        }

        try {
            $sql = 'SELECT COUNT(*) AS rec FROM ('.$sql.') AS rc';
            if (is_array($options)) {
                $this->prepare($sql);
                $this->execute($options);
            } else {
                $this->query($sql);
            }

            return $this->statement->fetchColumn();
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }

        return false;
    }

    /**
     * record count of execute query.
     *
     * @return int
     */
    public function rowCount()
    {
        try {
            return $this->statement->rowCount();
        } catch (\PDOException $e) {
            $this->error_code = $e->getCode();
            $this->error_message = $e->getMessage();
        }

        return false;
    }

    /**
     * Error message.
     *
     * @return string
     */
    public function error()
    {
        $err = $this->errorInfo();
        if (!empty($err)) {
            return $err[2];
        }

        return $this->error_message;
    }

    /**
     * AUTO_INCREMENT.
     *
     * @param string $table Table Name
     *
     * @return mixed
     */
    public function lastInsertId($table = null, $col = null)
    {
        if (is_null($table)) {
            return $this->handler->lastInsertId($col);
        }

        $sql = "SELECT LAST_INSERT_ID() AS id FROM \"$table\"";
        if ($this->driver == 'sqlite' || $this->driver == 'sqlite2') {
            $sql = "SELECT last_insert_rowid() AS id FROM \"$table\"";
        }
        if ($this->query($sql)) {
            $num = $this->fetch();

            return $num['id'];
        }

        return;
    }

    /**
     * Get field list.
     *
     * @param string $table     Table Name
     * @param bool   $property
     * @param bool   $comment
     * @param string $statement
     *
     * @return mixed
     */
    public function getFields($table, $property = false, $comment = false, $statement = '')
    {
        if ($this->driver === 'mysql') {
            $sql = ($comment === true) ? "SHOW FULL COLUMNS FROM \"$table\"" : "SHOW COLUMNS FROM \"$table\"";
            if (!empty($statement)) {
                $sql .= " $statement";
            }
        } elseif ($this->driver === 'pgsql') {
            $primary = [];
            $comments = [];
            $sql = 'SELECT ccu.column_name as column_name
                      FROM information_schema.table_constraints tc,
                           information_schema.constraint_column_usage ccu
                     WHERE tc.table_catalog = '.$this->quote($this->source).'
                       AND tc.table_name = '.$this->quote($table)."
                       AND tc.constraint_type = 'PRIMARY KEY'
                       AND tc.table_catalog = ccu.table_catalog
                       AND tc.table_schema = ccu.table_schema
                       AND tc.table_name = ccu.table_name
                       AND tc.constraint_name = ccu.constraint_name";
            if ($this->query($sql)) {
                $result = $this->fetchAll();
                foreach ($result as $unit) {
                    $primary[$unit['column_name']] = 'PRI';
                }
            }
            if ($comment === true) {
                $sql = 'SELECT pa.attname as column_name,
                               pd.description as column_comment
                          FROM pg_stat_all_tables psat,
                               pg_description pd,
                               pg_attribute pa
                         WHERE psat.schemaname = (
                                   SELECT schemaname 
                                     FROM pg_stat_user_tables
                                    WHERE relname = '.$this->quote($table).'
                               )
                           AND psat.relname = '.$this->quote($table).'
                           AND psat.relid = pd.objoid
                           AND pd.objsubid <> 0
                           AND pd.objoid = pa.attrelid
                           AND pd.objsubid = pa.attnum
                         ORDER BY pd.objsubid';
                if ($this->query($sql)) {
                    $result = $this->fetchAll();
                    foreach ($result as $unit) {
                        $comments[$unit['column_name']] = $unit['column_comment'];
                    }
                }
            }

            if (!empty($statement)) {
                $statement = "AND column_name $statement";
            }

            $sql = sprintf('SELECT * 
                      FROM information_schema.columns
                     WHERE table_catalog = '.$this->quote($this->source).'
                       AND table_name = '.$this->quote($table).' %s
                     ORDER BY ordinal_position', $statement);
        } elseif ($this->driver === 'sqlite' || $this->driver === 'sqlite2') {
            $sql = "PRAGMA table_info($table);";
        }
        $data = [];
        if ($this->query($sql)) {
            while ($value = $this->fetch()) {
                if ($property === false) {
                    if ($this->driver === 'sqlite' || $this->driver === 'sqlite2') {
                        $data[] = $value['name'];
                    } elseif ($this->driver === 'pgsql') {
                        $data[] = $value['column_name'];
                    } else {
                        $data[] = $value['Field'];
                    }
                } else {
                    if ($this->driver === 'sqlite' || $this->driver === 'sqlite2') {
                        $data[$value['name']] = $value;
                    } elseif ($this->driver === 'pgsql') {
                        if (isset($primary[$value['column_name']])) {
                            $value['Key'] = $primary[$value['column_name']];
                        }
                        if (isset($comments[$value['column_name']])) {
                            $value['Comment'] = $comments[$value['column_name']];
                        }
                        $data[$value['column_name']] = $value;
                    } else {
                        $data[$value['Field']] = $value;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * the field type is number or else.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function is_number($type)
    {
        return preg_match('/^(double|float|real|dec|int|tinyint|smallint|mediumint|bigint|numeric|bit)/i', $type);
    }

    /**
     * get last query.
     *
     * return string
     */
    public function latestSQL()
    {
        return $this->sql;
    }

    /**
     * get ecount.
     *
     * return int
     */
    public function getRow()
    {
        return $this->ecount;
    }

    /**
     * PDO::errorInfo.
     *
     * @return array
     */
    public function errorInfo()
    {
        if (is_object($this->statement)) {
            $info = $this->statement->errorInfo();
            if (!is_null($info[2])) {
                return $info;
            }
        }
        if (is_null($this->handler)) {
            return;
        }

        return $this->handler->errorInfo();
    }

    /**
     * PDO::errorCode.
     *
     * @return array
     */
    public function errorCode()
    {
        if (is_object($this->statement)) {
            $code = $this->statement->errorCode();
            if (!is_null($code)) {
                return $code;
            }
        }

        return $this->handler->errorCode();
    }

    /**
     * Set PDO Attribute.
     *
     * @param int   $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return $this->handler->setAttribute($attribute, $value);
    }

    /**
     * AES Encrypt data.
     *
     * @param string $phrase
     *
     * @return string
     */
    public function aes_encrypt($phrase, $salt)
    {
        $sql = 'SELECT HEX(AES_ENCRYPT('.$this->quote($phrase).',
               '.$this->quote($salt).'));';
        if ($this->query($sql)) {
            return $this->fetchColumn();
        }
    }

    /**
     * Convert Count SQL.
     *
     * @param string $sql
     *
     * @return string
     */
    public static function countSQL($sql)
    {
        $arr = [];
        $tags = ['select', 'from'];
        foreach ($tags as $tag) {
            $offset = 0;
            while (false !== $idx = stripos($sql, $tag, $offset)) {
                if (!isset($arr[$tag])) {
                    $arr[$tag] = [];
                }
                $arr[$tag][] = $idx;
                $offset = $idx + strlen($tag);
            }
        }
        for ($i = 1; $i < count($arr['from']); ++$i) {
            if ($arr['select'][$i - 1] < $arr['from'][$i]) {
                $start = 6;
                $length = ($arr['from'][$i] - $start);

                return substr_replace($sql, ' COUNT(*) AS cnt ', $start, $length);
            }
        }

        return false;
    }

    /**
     * Normalize SQL sting.
     *
     * @param string $sql
     *
     * @return string
     */
    public function normalizeSQL($sql)
    {
        $sql = str_replace('`', '"', $sql);
        $sql = preg_replace("/LIMIT[\s]+([0-9]+)[\s]*,[\s]*([0-9]+)/i", 'LIMIT $2 OFFSET $1', $sql);

        return $sql;
    }

    /**
     * Requote column name
     *
     * @param string $columns
     *
     * @return string
     */
    private static function verifyColumns($columns)
    {
        $columns = array_map([__CLASS__, 'quoteColumn'], explode(',', $columns));
        return implode(',', array_filter($columns));
    }

    private static function quoteColumn($column, $quote = '"')
    {
        $column = trim($column);
        if ($column === '*') {
            return $column;
        }
        elseif (preg_match('/^\s*(\w+)\s+as\s+(\w+)\s*$/i', $column, $match)) {
            return $quote . str_replace($quote, '', $match[1]) . $quote . ' AS '
                 . $quote . str_replace($quote, '', $match[2]) . $quote;
        }
        elseif (preg_match('/^(.+)\.\*$/', $column, $match)) {
            return $quote . str_replace($quote, '', $match[1]) . $quote . '.*';
        }

        return $quote . str_replace($quote, '', $column) . $quote;
    }
}
