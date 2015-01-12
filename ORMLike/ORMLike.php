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
 * @class ORMLike
 *
 * Built an ORMLike object.
 */
class ORMLike implements Countable, IteratorAggregate
{
    protected
        // Database Adapter
        $_db,
        // Database target table
        $_table,
        // Database target table's primary key
        $_primaryKey,
        // Entity object
        $_entity = null;

    /**
     * Initialize an ORMLike object and ORMLikeDatabase object.
     */
    public function __construct() {
        $this->_db = ORMLikeDatabase::init();
        // Set properties if defined as default
        $refObj   = new ReflectionClass(get_class($this));
        $refProps = $refObj->getProperties(ReflectionProperty::IS_PUBLIC);
        if (!empty($refProps)) {
            $props = array();
            foreach ($refProps as $refProp) {
                $name = $refProp->getName();
                $props[$name] = $this->$name;
                unset($this->$name);
            }
            $this->_checkEntity($props);
        }
    }

    /**
     * Set entity property.
     *
     * @param String $key (required)
     * @param Mixed $var (required)
     */
    public function __set($key, $val) {
        $this->_checkEntity();
        $this->_entity->__set($key, $val);
    }

    /**
     * Get entity property.
     *
     * @param String $key (required)
     */
    public function __get($key) {
        $this->_checkEntity();
        return $this->_entity->__get($key);
    }

    /**
     * Set entity property.
     *
     * @param String $key (required)
     * @param Mixed $var (required)
     */
    public function set($key, $val) {
        return $this->__set($key, $val);
    }

    /**
     * Get entity property.
     *
     * @param String $key (required)
     */
    public function get($key) {
        return $this->__get($key);
    }

    /**
     * Call a fake method of entity for a property (if entity property is exists).
     * Call a real method of entity (if method is exists).
     *
     * @param String $name
     * @param String $args
     */
    public function __call($name, $args) {
        // Self defined method
        if (!method_exists($this, $name)) {
            $this->_checkEntity();
            return $this->_entity->__call($name, $args);
        }
        throw new ORMLikeException('Method does not exists!');
    }

    /**
     * Fetch a row-set, create entity object.
     *
     * @param Array $params (required)
     * @return Object self
     */
    public function find($params = array()) {
        // Check for relations
        if (isset($this->_relations)) {
            $where = $this->_db->prepare(" WHERE `{$this->_table}`.`{$this->_primaryKey}` = ?", $params);
            $query = $this->_generateRelationQuery('select', $where);
        } else {
            $query = $this->_db->prepare("SELECT * FROM `{$this->_table}` WHERE `{$this->_primaryKey}` = ?", $params);
        }

        // Reset data
        $this->_entity = array();
        $this->_db->get($query);
        if ($this->_db->numRows) {
            $this->_entity = $this->_createEntity($this->_db->data[0]);
            $this->_db->reset();
        }
        return $this;
    }

    /**
     * Fetch row-sets, create entity objects.
     *
     * @param String $where
     * @param Array $params
     * @return Object self
     */
    public function findAll($where = '', $params = array()) {
        if ($where) {
            $where = preg_replace('~^WHERE \s*~i', '', trim($where));
            if (!empty($params)) {
                if (is_array($params)) {
                    $params = array($params);
                }
                $where = $this->_db->prepare($where, $params);
            }
            $where = 'WHERE '. $where;
        }
        $query = isset($this->_relations)
            ? $this->_generateRelationQuery('select', $where)
            : "SELECT * FROM `{$this->_table}` $where";

        // Reset data
        $this->_entity = array();
        $this->_db->getAll($query);
        if ($this->_db->numRows) {
            foreach ($this->_db->data as $data) {
                $this->_entity[] = $this->_createEntity($data);
            }
            $this->_db->reset();
        }
        return $this;
    }

    /**
     * Insert or update a row-set.
     * Note: Entity object must be inited before.
     *
     * @return Mixed $result
     * @throw ORMLikeException
     */
    public function save() {
        if (!$this->_entity instanceof ORMLikeEntity) {
            throw new ORMLikeException('Entity is empty for now!');
        }
        $data = $this->_entity->toArray();
        // Clear relations data / prevent columns not match error
        if (property_exists($this, '_relations') && isset($this->_relations['select']['leftJoin'])) {
            foreach ($this->_relations['select']['leftJoin'] as $leftJoin) {
                $field = preg_split('~\s*,\s*~', $leftJoin['field'], -1, PREG_SPLIT_NO_EMPTY);
                $field = array_map(function($field){
                    return preg_replace('~.+?\((.+?)\)~', '\\1', trim($field));
                }, $field);
                // pre($field);
                foreach ($data as $key => $value) {
                    if (in_array($key, $field)) {
                        unset($data[$key]);
                    }
                }
            }
        }
        // Insert action
        if (!isset($data[$this->_primaryKey])) {
            if (empty($data)) {
                throw new ORMLikeException('There is no data ehough on entity for insert!');
            }
            $result = $this->_db->insert($this->_table, $data);
            // Set ID
            $this->_entity->__set($this->_primaryKey, $result);
        }
        // Update action
        else {
            $id = $data[$this->_primaryKey];
            unset($data[$this->_primaryKey]);
            if (empty($data)) {
                throw new ORMLikeException('There is no data ehough on entity for update!');
            }
            $result = $this->_db->update($this->_table, $data, "`{$this->_primaryKey}` = ?", $id);
        }
        return $result;
    }

    /**
     * Delete a row-set.
     *
     * @param Mixed $params (required)
     * @return Mixed $result
     * @throw ORMLikeException
     */
    public function remove($params = array()) {
        if (!empty($params)) {
            if (is_array($params)) {
                $params = array($params);
            }
            return $this->_db->delete($this->_table, "`{$this->_primaryKey}` IN(?)", $params);
        }
        throw new ORMLikeException('There is no criteria enough for delete!');
    }

    /**
     * Return self::$_entity as array.
     *
     * @return Array
     */
    public function toArray() {
        if ($this->_entity instanceof ORMLikeEntity) {
            return $this->_entity->toArray();
        }
        $array = array();
        foreach ($this->_entity as $entity) {
            $array[] = $entity->toArray();
        }
        return $array;
    }

    /**
     * Countable method.
     *
     * @return Integer
     */
    public function count() {
        if ($this->_entity instanceof ORMLikeEntity) {
            return $this->_entity->isFound() ? 1 : 0;
        }
        return count($this->_entity);
    }

    /**
     * IteratorAggregate method.
     *
     * @return Object ArrayIterator
     */
    public function getIterator() {
        if (!is_array($this->_entity)) {
            $this->_entity = array($this->_entity);
        }
        return new ArrayIterator($this->_entity);
    }

    /**
     * Check whether entity object is already created or not and if not create new one.
     */
    protected function _checkEntity($data = array()) {
        if (!$this->_entity instanceof ORMLikeEntity) {
            $this->_entity = $this->_createEntity($data);
        }
    }

    /**
     * Create a new entity object.
     *
     * @param Array $data
     * @return Object ORMLikeEntity object
     */
    protected function _createEntity($data = array()) {
        return new ORMLikeEntity((array) $data);
    }

    /**
     * Generate a sql query (just left joins only for now)
     *
     * @param  string $for
     * @param  string $where
     * @return string
     */
    protected function _generateRelationQuery($for, $where) {
        // Make select query
        if ($for == 'select') {
            $query = sprintf('SELECT `%s`.* FROM `%s`', $this->_table, $this->_table);
            // Check for left join rule
            if (isset($this->_relations['select']['leftJoin'])) {
                foreach ($this->_relations['select']['leftJoin'] as $leftJoin) {
                    $query .= sprintf(' LEFT JOIN `%s` ON `%s`.`%s` = `%s`.`%s`',
                        $leftJoin['table'], $leftJoin['table'], $leftJoin['foreignKey'],
                        $this->_table, $this->_primaryKey
                    );
                    if (isset($leftJoin['field'])) {
                        // Check for field prefix
                        $fieldPrefix = isset($leftJoin['fieldPrefix']) ? $leftJoin['fieldPrefix'] : '';
                        // Split prefix if more than one detecting by comma
                        $field = preg_split('~\s*,\s*~', $leftJoin['field'], -1, PREG_SPLIT_NO_EMPTY);
                        if (!empty($field)) {
                            $field = array_map(function($field) use ($fieldPrefix, $leftJoin) {
                                // Notice user
                                if ($field == '*') {
                                    trigger_error(
                                        '* wildcard causes MySQL ambiguous error on joins, specify each field instead.');
                                }

                                // Check for aggregate functions
                                if (preg_match('~(\w+).*\((.+?)\)~', $field, $match)) {
                                    $field = sprintf('%s(`%s`.%s) AS %s%s',
                                        $match[1], $leftJoin['table'], $match[2], $fieldPrefix, trim($match[2]));
                                } else {
                                    // Prevent * AS *
                                    $field = ($field == '*')
                                        ? sprintf('`%s`.%s', $leftJoin['table'], $field)
                                        : sprintf('`%s`.%s AS %s%s', $leftJoin['table'], $field, $fieldPrefix, $field);
                                }

                                return $field;
                            }, $field);

                            $field = join(', ', $field);
                            // Add field
                            $query = preg_replace_callback('~\s+FROM~', function($m) use ($field) {
                                return ', '. trim($field, ', ') .' FROM';
                            }, $query);
                        }
                    }
                }
                // Add where into
                $query .= $where;

                // Add group by
                if (isset($leftJoin['groupBy'])) {
                    $query .= sprintf(' GROUP BY %s', $leftJoin['groupBy']);
                }
            } else {
                // Add just where
                $query .= $where;
            }

            return $query;
        }
    }
}
