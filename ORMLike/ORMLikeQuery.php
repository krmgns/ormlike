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
 * @class ORMLikeQuery
 *
 * Raw SQL query builder.
 */
class ORMLikeQuery
{
    // Operators
    const OP_OR = 'OR',
          OP_AND = 'AND',
          OP_ASC = 'ASC',
          OP_DESC = 'DESC';

    protected
        // Query parts stack
        $_query = array(),
        // Query string stack
        $_queryString,
        // Table for `FROM` statement
        $_table,
        // ORMLikeDatabase object
        $db;


    /**
     * Initialize an ORMLikeQuery object
     * Initialize an ORMLikeDatabase object
     */
    public function __construct($table) {
        // Need a table first
        if (empty($table)) {
            throw new ORMLikeException('Table cannot be empty!');
        }
        // Set db
        $this->db = ORMLikeDatabase::init();
        // Set table
        $this->_table = $table;
    }

    /**
     * Put a new element into query stack
     *
     * @param  string  $key
     * @param  string  $value
     * @param  boolean $sub
     * @return
     */
    protected function _push($key, $value) {
        // Set query sub array
        if (!isset($this->_query[$key])) {
            $this->_query[$key] = array();
        }
        $this->_query[$key][] = $value;

        return $this;
    }

    /**
     * Dump SQL query string
     *
     * @return string
     */
    public function toString() {
        // Set once query string
        if (!empty($this->_query) && empty($this->_queryString)) {
            // Add select statement
            if (isset($this->_query['select'])) {
                // Add aggregate statements
                $aggregate = isset($this->_query['aggregate'])
                    ? ', '. join(', ', $this->_query['aggregate'])
                    : '';
                $this->_queryString .= sprintf(
                    'SELECT %s%s FROM %s', join(', ', $this->_query['select']), $aggregate, $this->_table);
            }
            // Add left join statement
            if (isset($this->_query['join'])) {
                $this->_queryString .= sprintf(' LEFT JOIN %s', join(' ', $this->_query['join']));
            }
            // Add where statement
            if (isset($this->_query['where'])) {
                $this->_queryString .= sprintf(' WHERE %s', trim(join(' ', $this->_query['where'])));
            }
            // Add group by statement
            if (isset($this->_query['groupBy'])) {
                $this->_queryString .= sprintf(' GROUP BY %s', join(', ', $this->_query['groupBy']));
            }
            // Add having statement
            if (isset($this->_query['having'])) {
                // Use only first element of having for now..
                $this->_queryString .= sprintf(' HAVING %s', $this->_query['having'][0]);
            }
            // Add order by statement
            if (isset($this->_query['orderBy'])) {
                $this->_queryString .= sprintf(' ORDER BY %s', join(', ', $this->_query['orderBy']));
            }
            // Add limit statement
            if (isset($this->_query['limit'])) {
                $this->_queryString .= isset($this->_query['limit'][1])
                    ? sprintf(' LIMIT %d,%d', $this->_query['limit'][0], $this->_query['limit'][1])
                    : sprintf(' LIMIT %d', $this->_query['limit'][0]);
            }
            $this->_queryString = trim($this->_queryString);
        }

        return $this->_queryString;
    }

    /**
     * Reset query / query string
     *
     * Useful when new query need
     *
     * @return self._push()
     */
    public function reset() {
        $this->_query = array();
        $this->_queryString = '';
        return $this;
    }

    /**
     * Push `select` statement
     *
     * @param  string $field
     * @return self._push()
     */
    public function select($field) {
        return $this->_push('select', $field);
    }

    /**
     * Push `left join` statement with `on` option
     *
     * @param  string $table
     * @param  string $on
     * @param  array  $params
     * @return self._push()
     */
    public function joinLeft($table, $on = '', $params = array()) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->db->prepare($on, $params);
        }

        return $this->_push('join', sprintf('%s ON %s', $table, $on));
    }


    /**
     * Push `left join` statement with `using` option
     *
     * @param  string $table
     * @param  string $using
     * @param  array  $params
     * @return self._push()
     */
    public function joinLeftUsing($table, $using = '', $params = array()) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->db->prepare($using, $params);
        }

        return $this->_push('join', sprintf('%s USING (%s)', $table, $using));
    }

    /**
     * Push `where` statement
     *
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self._push()
     */
    public function where($query, $params = array(), $op = self::OP_AND) {
        $query = $this->db->prepare($query, $params);
        if (!empty($this->_query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->_push('where', $query);
    }

    /**
     * Push `where` statement
     *
     * That will escape all string, use
     * `where` if you dont wanna this happens
     *
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self.where()
     */
    public function whereLike($query, $params = array(), $op = self::OP_AND) {
        $params = (array) $params;
        foreach ($params as &$param) {
            $charfirst = $param[0];
            $charlast  = substr($param, -1);
            // both appended
            if ($charfirst == '%' && $charlast == '%') {
                $param = $charfirst . addcslashes(substr($param, 1, -1), '%_') . $charlast;
            }
            // left appended
            elseif ($charfirst == '%') {
                $param = $charfirst . addcslashes(substr($param, 1), '%_');
            }
            // right appended
            elseif ($charlast == '%') {
                $param = addcslashes(substr($param, 0, -1), '%_') . $charlast;
            }
        }

        return $this->where($query, $params, $op);
    }

    /**
     * Push `where` statement with `is null`
     *
     * @param  string $field
     * @return self._push()
     */
    public function whereNull($field) {
        return $this->_push('where', sprintf('%s IS NULL', $field));
    }

    /**
     * Push `where` statement with `is not null`
     *
     * @param  string $field
     * @return self._push()
     */
    public function whereNotNull($field) {
        return $this->_push('where', sprintf('%s IS NOT NULL', $field));
    }

    /**
     * Push `having` statement
     *
     * @param  string $query
     * @param  array  $params
     * @return self._push()
     */
    public function having($query, $params = array()) {
        $query = $this->db->prepare($query, $params);
        return $this->_push('having', $query);
    }

    /**
     * Push `group by` statement
     *
     * @param  string $field
     * @return self._push()()
     */
    public function groupBy($field) {
        return $this->_push('groupBy', $field);
    }

    /**
     * Push `order by` statement
     *
     * @param  string $field
     * @return self._push()()
     */
    public function orderBy($field, $op = null) {
        return $this->_push('orderBy', $field .' '. $op);
    }

    /**
     * Push `limit` statement
     *
     * @param  integer $start
     * @param  integer $stop
     * @return self._push()
     */
    public function limit($start, $stop = null) {
        if ($stop === null) {
            return $this->_push('limit', $start);
        }
        return $this->_push('limit', $start)->_push('limit', $stop);
    }

    /**
     * Push aggregate statements
     *
     * @param  string $aggr       Only count() sum() min() max() avg()
     * @param  string $field
     * @param  string $fieldAlias
     * @return self._push()
     */
    public function aggregate($aggr, $field = '*', $fieldAlias = '') {
        return $this->_push('aggregate', sprintf(
            '%s(%s) %s', ucfirst($aggr), $field,
                ($fieldAlias ? $fieldAlias : ($field && $field != '*' ? $field : ''))));
    }

    /**
     * Proxy method for db.query() to query execution
     *
     * @param  \Closure|null $callback
     * @return MySQLi Result Object
     */
    public function execute(\Closure $callback = null) {
        $result = $this->db->query($this->toString());
        // Render result if callback provided
        if (is_callable($callback)) {
            $result = $callback($result);
        }
        return $result;
    }

    /**
     * Proxy method for db.get()
     *
     * @param  \Closure|null $callback
     * @return mixed|null
     */
    public function get(\Closure $callback = null) {
        $result = $this->db->get($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }
        return $result;
    }

    /**
     * Proxy method for db.getAll()
     *
     * @param  \Closure|null $callback
     * @return array
     */
    public function getAll(\Closure $callback = null) {
        $result = $this->db->getAll($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }
        return $result;
    }
}
