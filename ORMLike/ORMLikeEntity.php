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
 * @class ORMLikeEntity v0.1
 *
 * Create an entity.
 */
class ORMLikeEntity
{
    // Row-set
    private $_data = array();
    
    /**
     * Initialize an ORMLike object and ORMLikeDatabase object.
     *
     * @param Array $data (required)
     */
    public function __construct(Array $data = array()) {
        foreach ($data as $key => $val) {
            $this->_data[$key] = $val;
        }
    }
    
    /**
     * Set data.
     *
     * @param String $key (required)
     * @param Mixed $val (required)
     */
    public function __set($key, $val) {
        $this->_data[$key] = $val;
    }
    
    /**
     * Get data.
     *
     * @param String $key (required)
     * @param Mixed $val (required)
     * @throw ORMLikeException
     */
    public function __get($key) {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        throw new ORMLikeException('"%s" is not found for this entity!', $key);
    }
    
    /**
     * Call a fake method for $_data data or defined method.
     *
     * @param String $call (required)
     * @param Array $args
     * @throw ORMLikeException
     */
    public function __call($call, $args = array()) {
        // Self defined method
        if (method_exists($this, $call)) {
            return call_user_func_array(array($this, $call), $args);
        }
        
        $act = substr($call, 0, 3);
        $var = lcfirst(substr($call, 3));
        // Get methods for properties
        if ('get' === $act) {
            return $this->__get($var);
        }
        // Set methods for properties
        if ('set' === $act) {
            return $this->__set($var, $args[0]);
        }
        
        throw new ORMLikeException('%s not callable!', $call);
    }
    
    /**
     * Isset data.
     *
     * @param String $key (required)
     * @return Boolean
     */
    public function __isset($key) {
        return array_key_exists($key, $this->_data);
    }
    
    /**
     * Unset data.
     *
     * @param String $key (required)
     */
    public function __unset($key) {
        unset($this->_data[$key]);
    }
    
    /**
     * Return self::$_data.
     *
     * @return Array
     */
    public function toArray() {
        return $this->_data;
    }
    
    /**
     * Is empty self::$_data or not.
     *
     * @return Boolean
     */
    public function isFound() {
        return !empty($this->_data);
    }
}