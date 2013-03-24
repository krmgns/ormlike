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
 * @class ORMLike v0.1
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
     * @param String $call (required)
     * @param String $args
     */
    public function __call($call, $args = array()) {
        // Self defined method
        if (method_exists($this, $call)) {
            return call_user_func_array(array($this, $call), $args);
        }
        $this->_checkEntity();
        return $this->_entity->__call($call, $args);
    }
    
    /**
     * Fetch a row-set, create entity object.
     *
     * @param Array $params (required)
     * @return Object self
     */
    public function find($params = array()) {
        // Reset data
        $this->_entity = array();
        $this->_db->get("SELECT * FROM `{$this->_table}` WHERE `{$this->_primaryKey}` = ?", $params);
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
        if ($where && !empty($params)) {
            if (is_array($params)) {
                $params = array($params);
            }
            $where = 'WHERE '. $this->_db->prepare($where, $params);
        }
        // Reset data
        $this->_entity = array();
        $this->_db->getAll("SELECT * FROM `{$this->_table}` $where");
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
        if (!isset($data[$this->_primaryKey])) {
            // Insert action
            if (empty($data)) {
                throw new ORMLikeException('There is no data ehough on entity for insert!');
            }
            $result = $this->_db->insert($this->_table, $data);
            // Set ID
            $this->_entity->__set($this->_primaryKey, $result);
        } else {
            // Update action
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
        throw new ORMLikeException('There is no criteria ehough for delete!');
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
}
