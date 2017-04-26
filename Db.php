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
 * Database connection class.
 *
 * @license  http://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <http://www.plus-5.com/>
 */
class P5_Db
{
    /**
     * Current version.
     */
    const VERSION = '1.1.0';

    /**
     * Database Type.
     *
     * @var string
     */
    private $_driver;

    /**
     * Database name.
     *
     * @var string
     */
    private $_source;

    /**
     * server port.
     *
     * @var string
     */
    private $_port;

    /**
     * Database Handler.
     *
     * @var PDO
     */
    private $_dbHandler;

    /**
     * PDOStatement Object.
     *
     * @var PDOStatement
     */
    private $_dbResult;

    /**
     * Transaction.
     *
     * @var bool
     */
    private $_isTransaction = false;

    /**
     * SQL string.
     * 
     * @vat string
     */
    private $_sql;

    /**
     * Error Code.
     *
     * @var int|string
     */
    private $_errorCode;

    /**
     * Error Message.
     *
     * @var string
     */
    private $_errorMessage;

    /**
     * Database.
     *
     * @var string
     */
    private $_dsn;

    /**
     * Database user name.
     *
     * @var string
     */
    private $_user;

    /**
     * Database access password.
     *
     * @var string
     */
    private $_password;

    /**
     * Database encoding.
     *
     * @var string
     */
    private $_enc;

    /**
     * excute counter.
     *
     * @var int
     */
    private $_ecount;

    /*
     * PDO Attributes
     *
     * @var array
     */
    private $_options = array();

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
        $this->_driver = $driver;
        $this->_source = $source;
        $this->_port = $port;
        $this->_user = $user;
        $this->_password = $password;
        $this->_enc = $enc;
        if ($this->_driver != 'sqlite' && $this->_driver != 'sqlite2') {
            $this->_dsn = "$driver:host=$host;port=$port;dbname=$source";
            if ($enc !== '') {
                if (file_exists($enc)) {
                    $this->_options[PDO::MYSQL_ATTR_READ_DEFAULT_FILE] = $enc;
                    $content = str_replace('#', ';', file_get_contents($enc));
                    $init = parse_ini_string($content, true);
                    if (isset($init['client']['default-character-set'])) {
                        $this->_enc = $init['client']['default-character-set'];
                    }
                } else {
                    $this->_dsn .= ";charset=$enc";
                }
            }
            if ($this->_driver === 'mysql') {
                $this->_options[PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
                $this->_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SQL_MODE='ANSI_QUOTES';";
            }
        } else {
            if (!file_exists($host)) {
                mkdir($host, 0777, true);
            }
            $this->_dsn = "$driver:$host/$source";
            if (!empty($port)) {
                $this->_dsn .= ".$port";
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
     * @param string $source
     *
     * @return bool
     */
    public function create($source = null)
    {
        try {
            if (empty($source)) {
                $source = $this->_source;
            }
            $dsn = "{$this->_driver}:{$this->_host};port={$this->_port}";
            $this->_dbHandler = new PDO($dsn, $this->_user, $this->_password);
            $this->_dbHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($this->_driver === 'mysql') {
                $this->_dbHandler->query(
                    "CREATE DATABASE {$source} DEFAULT CHARACTER SET {$this->_enc}"
                );
            }
            if ($this->_driver === 'pgsql') {
                $this->_dbHandler->query(
                    "CREATE DATABASE {$source} ENCODING '{$this->_enc}'"
                );
            }
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

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
            $this->_options[$key] = $value;
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
                $this->_options[PDO::ATTR_TIMEOUT] = $timeout;
            }
            $this->_dbHandler = new PDO($this->_dsn, $this->_user, $this->_password, $this->_options);
            $this->_dbHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

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
        $this->_sql = $this->normalizeSQL($sql);
        try {
            $this->_ecount = $this->_dbHandler->exec($this->_sql);
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

            return false;
        }

        return $this->_ecount;
    }

    /**
     * Execute SQL.
     *
     * @param string $sql
     *
     * @return mixed
     */
    public function query($sql)
    {
        $this->_sql = $this->normalizeSQL($sql);
        try {
            $this->_dbResult = $this->_dbHandler->query($this->_sql);
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

            return false;
        }

        return $this->_dbResult;
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
        $data = (P5_Array::is_hash($data)) ? array($data) : $data;
        $raws = (P5_Array::is_hash($raws)) ? array($raws) : $raws;
        $cnt = 0;
        $keys = array();
        $rows = array();
        foreach ($data as $n => $unit) {
            $vals = array();
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
    public function update($table, $data, $statement = '', $options = array(), $raws = null, $fields = null)
    {
        if (is_null($fields)) {
            $fields = $this->getFields($table, true);
        }
        $pair = array();
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
    public function updateOrInsert($table, array $data, $unique, $raws = array())
    {
        $ecount = 0;
        if (P5_Array::is_hash($data)) {
            $data = array($data);
        }
        foreach ($data as $unit) {
            $keys = array_keys($unit);
            $update = $unit;
            $where = array();
            $states = array();
            foreach ($unique as $key) {
                $where[] = $unit[$key];
                $arr[] = "{$key} = ?";
                if (in_array($key, $keys)) {
                    unset($update[$key]);
                }
            }
            if (self::exists($table, implode(' AND ', $arr), $where)) {
                if (false === $ret = self::update($table, $update, implode(' AND ', $arr), $where, $raws)) {
                    return false;
                }
            } else {
                if (false === $ret = self::insert($table, $unit, $raws)) {
                    return false;
                }
            }
            $ecount += $ret;
        }
        $this->_ecount = $ecount;

        return $this->_ecount;
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
    public function replace($table, array $data, $unique, $raws = array(), $fields = null)
    {
        $ecount = 0;
        if (is_null($fields)) {
            $fields = $this->getFields($table, true);
        }
        if (P5_Array::is_hash($data)) {
            $data = array($data);
        }
        $cnt = 0;
        $keys = array();
        $dest = array();
        $cols = '';
        foreach ($data as $unit) {
            $vals = array();
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
            if ($this->_driver == 'mysql') {
                $sql = "INSERT INTO \"$table\" $sql ON DUPLICATE KEY UPDATE ".implode(',', $dest);
            } elseif ($this->_driver == 'pgsql') {
                $where = array();
                $arr = array();
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
            } elseif ($this->_driver == 'sqlite' || $this->_driver == 'sqlite2') {
                $sql = "INSERT INTO \"$table\" $sql";
                if (false === $ret = $this->exec($sql)) {
                    if (preg_match('/columns? (.+) (is|are) not unique/i', $this->error(), $match)) {
                        $unique = P5_Text::explode(',', $match[1]);
                        $where = array();
                        $arr = array();
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
        $this->_ecount = $ecount;

        return $this->_ecount;
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
    public function select($columns, $table, $statement = '', $options = array())
    {
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
    public function exists($table, $statement = '', $options = array())
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
    public function count($table, $statement = '', $options = array())
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
    public function get($column, $table, $statement = '', $options = array())
    {
        $sql = "SELECT $column FROM $table";
        if (!empty($statement) && !empty($options)) {
            $sql .= ' WHERE '.$this->prepareStatement($statement, $options);
        }
        if ($this->query($sql)) {
            return $this->fetchColumn();
        }

        return false;
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
    public function min($column, $table, $statement = '', $options = array())
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
    public function max($column, $table, $statement = '', $options = array())
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

        return $this->_dbResult = $this->_dbHandler->prepare($statement);
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
        if (false !== $this->_dbResult->execute($input_parameters)) {
            return $this->_dbResult->rowCount();
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
        if (P5_Array::is_hash($options)) {
            $pattern = array();
            $replace = array();
            foreach ($options as $key => $option) {
                $option = preg_replace('/'.preg_quote('$', '/').'/', '$\\', $option);
                $key = preg_replace('/^:/', '', $key, 1);
                $pattern[] = '/'.preg_quote(preg_replace('/^:?/', ':', $key, 1), '/').'/';
                $replace[] = $this->quote($option);
            }
            $statement = preg_replace($pattern, $replace, $statement);
        } else {
            if (is_array($options)) {
                foreach ($options as $option) {
                    $option = preg_replace('/'.preg_quote('$', '/').'/', '$\\', $option);
                    $statement = preg_replace("/\?/", $this->quote($option), $statement, 1);
                }
            }
        }

        return preg_replace('/'.preg_quote('$\\', '/').'/', '$', $statement);
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
    public function fetch($type = PDO::FETCH_ASSOC, $cursor = PDO::FETCH_ORI_NEXT, $offset = 0)
    {
        try {
            $data = $this->_dbResult->fetch($type, $cursor, $offset);
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

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
    public function fetchAll($type = PDO::FETCH_ASSOC, $columnIndex = 0)
    {
        try {
            if ($type == PDO::FETCH_COLUMN) {
                $data = $this->_dbResult->fetchAll($type, $columnIndex);
            } else {
                $data = $this->_dbResult->fetchAll($type);
            }
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

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
            $data = $this->_dbResult->fetchColumn($column_number);
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();

            return false;
        }

        return $data;
    }

    /**
     * escape string.
     *
     * @param mixed $value
     * @param bool  $isZero
     * @param bool  $isNull
     *
     * @return string
     */
    public function quote($value, $isZero = false, $isNull = false)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if ($value === 0) {
            return '0';
        }
        if ($value === '0') {
            return "'0'";
        }
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }

        // TODO: Do I really need this?
        if (empty($value)) {
            if ($isZero === false && $isNull === true) {
                return 'NULL';
            }
        }

        return $this->_dbHandler->quote($value);
    }

    /**
     * escape table or column name.
     *
     * @return string
     */
    public function escapeName($str)
    {
        $str = $this->_dbHandler->quote($str);
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
        $result = array();
        if (is_array($data)) {
            return array_keys($data);
        }
        if ($this->_driver == 'sqlite' || $this->_driver == 'sqlite2') {
            if (preg_match("/^SELECT\s+.+\s+FROM\s[`'\"]?(\w+)[`'\"]?.*$/i", $this->_sql, $match)) {
                $tableName = $match[1];
            } elseif (preg_match("/^UPDATE\s+[`'\"]?(\w+)[`'\"]?.*$/i", $this->_sql, $match)) {
                $tableName = $match[1];
            }
            $sql = "PRAGMA table_info($tableName)";
            if ($this->query($sql)) {
                $result = $this->fetchAll(PDO::FETCH_COLUMN, 1);
            }
        } else {
            for ($i = 0; $i < $this->_dbResult->columnCount(); ++$i) {
                $meta = $this->_dbResult->getColumnMeta($i);
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
        if ($this->_dbHandler->inTransaction() || $this->_dbHandler->beginTransaction()) {
            if (!empty($identifire)) {
                $this->query("SAVEPOINT $identifire");
            }
            $this->_isTransaction = true;

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
        if ($this->_dbHandler->commit()) {
            $this->_isTransaction = false;

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
        if ($this->_isTransaction === false) {
            return true;
        }
        if (!empty($identifire)) {
            if ($this->query("ROLLBACK TO SAVEPOINT $identifire")) {
                $this->_isTransaction = false;

                return true;
            }
        }
        try {
            if ($this->_dbHandler->rollBack()) {
                $this->_isTransaction = false;

                return true;
            }
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();
        }
        $this->_isTransaction = false;

        return false;
    }

    /**
     * transaction exists.
     *
     * @return bool
     */
    public function getTransaction()
    {
        return $this->_dbHandler->inTransaction();
    }

    /**
     * record count of execute query.
     *
     * @return int
     */
    public function recordCount($sql = '')
    {
        if (empty($sql)) {
            $sql = $this->_sql;
        }
        $this->query('SELECT COUNT(*) AS rec FROM ('.$sql.') AS rc');
        try {
            return $this->_dbResult->fetchColumn();
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();
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
            return $this->_dbResult->rowCount();
        } catch (PDOException $e) {
            $this->_errorCode = $e->getCode();
            $this->_errorMessage = $e->getMessage();
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

        return $this->_errorMessage;
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
            return $this->_dbHandler->lastInsertId($col);
        }

        $sql = "SELECT LAST_INSERT_ID() AS id FROM \"$table\"";
        if ($this->_driver == 'sqlite' || $this->_driver == 'sqlite2') {
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
     * @param string $table    Table Name
     * @param bool   $property
     * @param bool   $comment
     *
     * @return mixed
     */
    public function getFields($table, $property = false, $comment = false)
    {
        if ($this->_driver === 'mysql') {
            $sql = ($comment === true) ? "SHOW FULL COLUMNS FROM \"$table\"" : "DESCRIBE \"$table\";";
        } elseif ($this->_driver === 'pgsql') {
            $primary = array();
            $comments = array();
            $sql = 'SELECT ccu.column_name as column_name
                      FROM information_schema.table_constraints tc,
                           information_schema.constraint_column_usage ccu
                     WHERE tc.table_catalog = '.$this->quote($this->_source).'
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

            $sql = 'SELECT * 
                      FROM information_schema.columns
                     WHERE table_catalog = '.$this->quote($this->_source).'
                       AND table_name = '.$this->quote($table).'
                     ORDER BY ordinal_position';
        } elseif ($this->_driver === 'sqlite' || $this->_driver === 'sqlite2') {
            $sql = "PRAGMA table_info($table);";
        }
        $data = array();
        if ($this->query($sql)) {
            while ($value = $this->fetch()) {
                if ($property === false) {
                    if ($this->_driver === 'sqlite' || $this->_driver === 'sqlite2') {
                        $data[] = $value['name'];
                    } elseif ($this->_driver === 'pgsql') {
                        $data[] = $value['column_name'];
                    } else {
                        $data[] = $value['Field'];
                    }
                } else {
                    if ($this->_driver === 'sqlite' || $this->_driver === 'sqlite2') {
                        $data[$value['name']] = $value;
                    } elseif ($this->_driver === 'pgsql') {
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
        return $this->_sql;
    }

    /**
     * get ecount.
     *
     * return int
     */
    public function getRow()
    {
        return $this->_ecount;
    }

    /**
     * PDO::errorInfo.
     *
     * @return array
     */
    public function errorInfo()
    {
        if (is_object($this->_dbResult)) {
            $info = $this->_dbResult->errorInfo();
            if (!is_null($info[2])) {
                return $info;
            }
        }
        if (is_null($this->_dbHandler)) {
            return;
        }

        return $this->_dbHandler->errorInfo();
    }

    /**
     * PDO::errorCode.
     *
     * @return array
     */
    public function errorCode()
    {
        if (is_object($this->_dbResult)) {
            $code = $this->_dbResult->errorCode();
            if (!is_null($code)) {
                return $code;
            }
        }

        return $this->_dbHandler->errorCode();
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
        return $this->_dbHandler->setAttribute($attribute, $value);
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
     * Update Modified date.
     *
     * @param string $table
     * @param string $column
     * @param string $statement
     * @param array  $options
     *
     * @return bool
     */
    public function modified($table, $column = 'modify_date', $statement = '', array $options = array())
    {
        $where = $this->prepareStatement($statement, $options);
        $sql = 'UPDATE "'.$table."\"
                   SET $column = CURRENT_TIMESTAMP
                 WHERE $where";

        return $this->exec($sql);
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
        $arr = array();
        $tags = array('select', 'from');
        foreach ($tags as $tag) {
            $offset = 0;
            while (false !== $idx = stripos($sql, $tag, $offset)) {
                if (!isset($arr[$tag])) {
                    $arr[$tag] = array();
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
        $sql = preg_replace('/`/', '"', $sql);
        $sql = preg_replace("/LIMIT[\s]+([0-9]+)[\s]*,[\s]*([0-9]+)/i", 'LIMIT $2 OFFSET $1', $sql);

        return $sql;
    }
}
