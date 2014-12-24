<?php

/**
 * Copyright 2013, Kerem Gunes <http://qeremy.com/>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/**
 * @class ORMLikeDatabaseAbstract v0.3
 *
 * Abstraction for ORMLikeDatabase object.
 */
abstract class ORMLikeDatabaseAbstract
{
    protected
        // Connection options
        $_cfg = array(
            'user'    => ORMLIKE_DATABASE_USER,    'pass'      => ORMLIKE_DATABASE_PASS,
            'host'    => ORMLIKE_DATABASE_HOST,    'database'  => ORMLIKE_DATABASE_NAME,
            'charset' => ORMLIKE_DATABASE_CHARSET, 'time_zone' => ORMLIKE_DATABASE_TIMEZONE,
        ),
        // The properties of the CRUD operations
        $_props = array('insertId' => 0, 'affectedRows' => 0, 'numRows' => 0),
        // MySQLI and MySQLIResult object
        $_link, $_result,
        $_error, // @todo
        $_query             = '', // Last query
        $_queryCount        = 0,  // Total query runs count
        $_timerStart        = 0,  // Last query time start
        $_timerStop         = 0,  // Last query time stop
        $_timerProcess      = 0,  // Last query time process
        $_timerProcessTotal = 0;  // Total query time processes

    // Result-set of last query for select command
    public $data = array();

    // Initialize a ORMLikeDatabaseAbstract object open connection.
    public function __construct() { $this->connect(); }
    // De-initialize a ORMLikeDatabaseAbstract object and close connection.
    public function __destruct()  { $this->disconnect(); }
    // Return last query as string
    public function __toString()  { return $this->_query; }

    /**
     * Access to ORMLikeDatabaseAbstract vars.
     *
     * @param String $var (required)
     * @return Mixed $var
     * @throw ORMLikeException
     */
    public function __get($var) {
        // Get "numRows, insertId, affectedRows"
        if (array_key_exists($var, $this->_props)) {
            return $this->_props[$var];
        }
        // Access other vars
        $var  = '_'. $var;
        $vars = get_object_vars($this);
        if (array_key_exists($var, $vars)) {
            return $this->$var;
        }
        // No property
        throw new ORMLikeException('Undefined property : %s.', $var);
    }

    /**
     * Open a database connection, set charset and timezone.
     *
     * @return Mixed self::$_link
     * @throw ORMLikeException
     */
    public function connect() {
        if (null === $this->_link) {
            if (!extension_loaded('mysqli')) {
                throw new ORMLikeException('Mysqli extension is not loaded.');
            }
            if (!$this->_link =@ mysqli_connect(
                    $this->_cfg['host'], $this->_cfg['user'], $this->_cfg['pass'], $this->_cfg['database'])) {
                throw new ORMLikeException('Connection error: %s', mysqli_connect_error());
            }
            // Set charset & timezone
            if (!mysqli_set_charset($this->_link, $this->_cfg['charset'])) {
                throw new ORMLikeException('Query error: %s', mysqli_error($this->_link));
            }
            if (!mysqli_query($this->_link, "SET time_zone = '{$this->_cfg['time_zone']}'")) {
                throw new ORMLikeException('Query error: %s', mysqli_error($this->_link));
            }
        }
        return $this->_link;
    }

    /**
     * Close the existing database connection, set self::$_link NULL.
     */
    public function disconnect() {
        if ($this->_link instanceof mysqli) {
            mysqli_close($this->_link);
            $this->_link = null;
        }
    }

    /**
     * Reset self::$data, self::$_query and self::$_props.
     */
    public function reset() {
        $this->data   = array();
        $this->_query = '';
        foreach ($this->_props as $key => $dummy) {
            $this->_props[$key] = 0;
        }
    }

    /**
     * Free self::$_result and set NULL.
     */
    public function freeResult() {
        if ($this->_result instanceof mysqli_result) {
            mysqli_free_result($this->_result);
            $this->_result = null;
        }
    }

    /**
     * Prepare SQL strings.
     *
     * Note: Since MySQLI gives error for non-completed strings (e.g: WHERE id=?),
     * I prefer to use this instead. It is able to secure any input as well.
     *
     * @param String $sql (required)
     * @param Array $params (required)
     * @return String $sql
     * @throw ORMLikeException
     */
    public function prepare($sql, $params = array()) {
        // Make array params
        if (!is_array($params)) {
            $params = array($params);
        }

        if (!empty($params)) {
            preg_match_all('~%[sdfF]|\?|:[a-zA-Z0-9_]+~', $sql, $match);
            if (isset($match[0])) {
                if (count($match[0]) != count($params)) {
                    throw new ORMLikeException('No enough params for %s->prepare().', get_class($this));
                }
                $i = 0; // Indexes could be string, e.g: array(':id' => 1, ...)
                foreach ($params as $key => $val) {
                    $key = $match[0][$i++];
                    $val = $this->escape($val, $key);
                    if (false !== ($pos = strpos($sql, $key))) {
                        $sql = substr_replace($sql, $val, $pos, strlen($key));
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Escape (secure) SQL inputs by types.
     *
     * @param Mixed $input (required)
     * @param String $type
     * @return String $input
     */
    public function escape($input, $type = null) {
        // For all formatted types, excepting %s. E.g: escape('id = %d', 1)
        if (!is_array($input)) {
            if (0 === strpos($type, '%') && '%s' !== $type) {
                return sprintf($type, $input);
            }
        }

        if ($input instanceof ORMLikeSql) {
            return $input->toString();
        }

        switch (gettype($input)) {
            case 'NULL':
                return 'NULL';
            case 'array':
                // IN statement
                return join(', ', array_map(array($this, 'escape'), $input));
            case 'boolean':
                // 1|0
                return (int) $input;
            case 'double':
                // %F = non-locale aware
                return sprintf('%F', $input);
            case 'string':
                // I trust you baby...
                return "'". mysqli_real_escape_string($this->_link, $input) ."'";
        }

        return $input;
    }

    /**
     * Escape identifiers for MySQL.
     *
     * @param String|Array $input (required)
     * @return String $input
     */
    public function escapeIdentifier($input) {
        if (is_array($input)) {
            return array_map(array($this, 'escapeIdentifier'), $input);
        }
        return '`'. trim($input) .'`';
    }

    /**
     * Prepare where statement.
     *
     * @param String $where (required)
     * @param Array $params
     * @return String $where
     */
    protected function _where($where = '1=1', $params = array()) {
        if (!empty($params)) {
            $where = $this->prepare($where, $params);
        }
        return $where;
    }

    /**
     * Abstract methods for ORMLikeDatabase.
     */
    abstract public function query($query, $params = array());
    abstract public function get($query, $params = array());
    abstract public function getAll($query, $params = array());
    abstract public function insert($table, Array $data = array());
    abstract public function update($table, Array $data = array(), $where = '1=1', $params = array());
    abstract public function delete($table, $where = '1=1', $params = array());

    public function sql($sql) {
        return new ORMLikeSql($sql);
    }
}