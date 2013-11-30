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
 * @class ORMLikeDatabase v0.1
 *
 * Database Adapter for MySQLI.
 */
class ORMLikeDatabase extends ORMLikeDatabaseAbstract
{
    // Fetch types
    const FETCH_OBJECT = 1,
          FETCH_ASSOC  = 2,
          FETCH_ARRAY  = 3,
          FETCH_CLASS  = 4; // @todo

    // ORMLikeDatabase intance for singleton
    private static $_instance = null;

    /**
     * Initialize a ORMLikeDatabase object.
     *
     * @return Object self::$_instance
     */
    public static function init() {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Execute SQL queries.
     *
     * @param String $query (required)
     * @param Array $params
     * @return Mixed MySQLIResult object
     */
    public function query($query, $params = array()) {
        // Reset query & data & props
        $this->reset();

        // Count queries
        ++$this->_queryCount;

        // Check query
        $this->_query = trim($query);
        if ('' === $this->_query) {
            throw new ORMLikeException('Query cannot be empty.');
        }

        // Prepare query
        if (!empty($params)) {
            $this->_query = $this->prepare($query, $params);
        }
        // pre($this->_query);

        // Start time process
        $this->_timerStart = microtime(true);

        if (!$this->_result = mysqli_query($this->_link, $this->_query)) {
            throw new ORMLikeException(
                'Query error: query[%s], error[%s]', $this->_query, mysqli_error($this->_link));
        }

        // Store props
        if (preg_match('~^(?:insert|update|delete|replace)\s+~i', $this->_query)) {
            $this->_props['insertId'] = $this->_link->insert_id;
            $this->_props['affectedRows'] = $this->_link->affected_rows;
        }

        // Stop time process
        $this->_timerStop          = microtime(true);
        $this->_timerProcess       = number_format(floatval($this->_timerStop - $this->_timerStart), 4);
        $this->_timerProcessTotal += $this->_timerProcess;

        return $this->_result;
    }

    /**
     * Fetch a row-set, set self::$_props[numRows].
     *
     * @param String $query (required)
     * @param Array $params
     * @param Integer $fetchType
     * @param Integer $fetchClass (not-implemented)
     * @return Array|NULL
     */
    public function get($query, $params = array(), $fetchType = self::FETCH_OBJECT, $fetchClass = null) {
        $this->query($query, $params);
        $i = 0;
        if (null !== $this->_result || 0 != $this->_result->num_rows) {
            $fetchFunction = $this->_getFetchFunction($fetchType);
            while ($row = $fetchFunction($this->_result)) {
                $this->data[$i++] = $row;
                break; // Only one row
            }
        }
        // Set num rows
        $this->_props['numRows'] = $i;
        // Clear result
        $this->freeResult();

        return isset($this->data[0]) ? $this->data[0] : null;
    }

    /**
     * Fetch row-sets, set self::$_props[numRows].
     *
     * @param String $query (required)
     * @param Array $params
     * @param Integer $fetchType
     * @param Integer $fetchClass (not-implemented)
     * @return Array
     */
    public function getAll($query, $params = array(), $fetchType = self::FETCH_OBJECT, $fetchClass = null) {
        $this->query($query, $params);
        $i = 0;
        if (null !== $this->_result || 0 !== $this->_result->num_rows) {
            $fetchFunction = $this->_getFetchFunction($fetchType);
            while ($row = $fetchFunction($this->_result)) {
                $this->data[$i++] = $row;
            }
        }
        // Set num rows
        $this->_props['numRows'] = $i;
        // Clear result
        $this->freeResult();

        return $this->data;
    }

    /**
     * Insert a row-set, set self::$_props[insertId].
     *
     * @param String $table (required)
     * @param Array $data (required)
     * @return Integer self::_link->insert_id
     */
    public function insert($table, Array $data = array()) {
        $keys = $this->escapeIdentifier(array_keys($data));
        $vals = $this->escape(array_values($data));
        $this->query(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
                $this->escapeIdentifier($table),
                join(', ', $keys),
                $vals
        ));

        // Set insert id
        $this->_props['insertId'] = $this->_link->insert_id;
        // Return insert id
        return $this->_link->insert_id;
    }

    /**
     * Update a row-set, set self::$_props[affectedRows].
     *
     * @param String $table (required)
     * @param Array $data (required)
     * @param String $where
     * @param Array $params
     * @return Integer self::_link->affected_rows
     */
    public function update($table, Array $data = array(), $where = '1=1', $params = array(), $limit = null) {
        $set = array();
        foreach ($data as $key => $val) {
            $set[] = $this->escapeIdentifier($key) .' = '. $this->escape($val);
        }

        $this->query(sprintf(
            'UPDATE %s SET %s WHERE %s %s',
                $this->escapeIdentifier($table),
                join(', ', $set),
                $this->_where($where, $params),
                $limit ? ('LIMIT '. $limit) : ''
        ));

        // Set affected rows
        $this->_props['affectedRows'] = $this->_link->affected_rows;
        // Return affected rows
        return $this->_link->affected_rows;
    }

    /**
     * Delete a row-set, set self::$_props[affectedRows].
     *
     * @param String $table (required)
     * @param String $where (required)
     * @param Array $params
     * @return Integer self::_link->affected_rows
     */
    public function delete($table, $where = '1=1', $params = array(), $limit = null) {
        $this->query(sprintf(
            'DELETE FROM %s WHERE %s %s',
                $this->escapeIdentifier($table),
                $this->_where($where, $params),
                $limit ? ('LIMIT '. $limit) : ''
        ));

        // Set affected rows
        $this->_props['affectedRows'] = $this->_link->affected_rows;
        // Return affected rows
        return $this->_link->affected_rows;
    }

    /**
     * Determine a fetch method for MySQLIResult object.
     *
     * @param Integer $fetchType (required)
     * @return String MySQLI fetch method
     */
    protected function _getFetchFunction($fetchType = self::FETCH_OBJECT) {
        switch ($fetchType) {
            case self::FETCH_ASSOC:
                return 'mysqli_fetch_assoc';
            case self::FETCH_ARRAY:
                return 'mysqli_fetch_array';
            case self::FETCH_OBJECT:
            case self::FETCH_CLASS:
            default:
                return 'mysqli_fetch_object';
        }
    }
}
