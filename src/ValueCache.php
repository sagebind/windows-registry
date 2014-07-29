<?php
/*
 * Copyright 2014 Stephen Coakley <me@stephencoakley.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace Coderstephen\Windows\Registry;

/**
 * A cache for storing registry values in memory.
 */
class ValueCache
{
    protected $valueTypes;
    protected $valueData;

    /**
     * Creates a new value cache.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * Checks if a named value is in the cache.
     * 
     * @param string $name
     * The name of the value.
     * 
     * @return boolean
     */
    public function hasValue($name)
    {
        return isset($this->valueTypes[$name]);
    }

    /**
     * Gets the data type of a named value in the cache.
     * 
     * @param string $name
     * The name of the value.
     * 
     * @return RegistryValueType
     */
    public function getValueType($name)
    {
        if ($this->hasValue($name))
        {
            return $this->valueTypes[$name];
        }
    }

    /**
     * Gets the value data of a named value in the cache.
     * 
     * @param string $name
     * The name of the value.
     * 
     * @return mixed
     */
    public function getValueData($name)
    {
        if ($this->hasValue($name))
        {
            return $this->valueData[$name];
        }
    }

    /**
     * Stores a named value in the cache.
     * 
     * If the value is already in the cache, it will be overwritten.
     * 
     * @param string $name
     * The name of the value to cache.
     * 
     * @param RegistryValueType $type
     * The data type of the value.
     * 
     * @param mixed $data
     * The value data of the value.
     */
    public function storeValue($name, RegistryValueType $type, $data)
    {
        $this->valueTypes[$name] = $type;
        $this->valueData[$name] = $data;
    }

    /**
     * Clears the cache of all values.
     */
    public function clear()
    {
        $this->valueTypes = array();
        $this->valueData = array();
    }
}
