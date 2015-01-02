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
 * Iterates over values in a registry key.
 */
class RegistryValueIterator implements \Iterator
{
    /**
     * An open registry handle.
     * @type RegistryHandle
     */
    protected $handle;

    /**
     * The registry hive the key is located in.
     * @type int
     */
    protected $hive;

    /**
     * Fully-qualified name of the key.
     * @type string
     */
    protected $keyName;

    /**
     * The current iterator position.
     * @type int
     */
    protected $pointer = 0;

    /**
     * The number of values we are iterating over.
     * @type int
     */
    protected $count = 0;

    /**
     * A (hopefully) enumerable variant containing the value names.
     * @type VARIANT
     */
    protected $valueNames;

    /**
     * A (hopefully) enumerable variant containing the data types of values.
     * @type VARIANT
     */
    protected $valueTypes;

    /**
     * Creates a new registry value iterator.
     *
     * @param RegistryHandle $handle
     * The WMI registry provider handle to use.
     *
     * @param int $hive
     * The registry hive the key is located in.
     *
     * @param string $name
     * The fully-qualified name of the key.
     */
    public function __construct(RegistryHandle $handle, $hive, $name)
    {
        $this->handle = $handle;
        $this->hive = $hive;
        $this->keyName = $name;
    }

    /**
     * Rewinds the iterator to the first value.
     */
    public function rewind()
    {
        // reset pointer and count
        $this->pointer = 0;
        $this->count = 0;

        // create empty variants to store out params
        $this->valueNames = new \VARIANT();
        $this->valueTypes = new \VARIANT();

        // attempt to enumerate values
        $errorCode = $this->handle->enumValues(
            $this->registryKey->getHive(),
            $this->registryKey->getQualifiedName(),
            $this->valueNames,
            $this->valueTypes
        );

        // make sure the enum isn't empty
        if ($errorCode === 0
            && (variant_get_type($this->valueNames) & VT_ARRAY)
            && (variant_get_type($this->valueTypes) & VT_ARRAY)) {
            // store the number of values
            $this->count = count($this->valueNames);
        }
    }

    /**
     * Checks if the current iteration position is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->pointer < $this->count;
    }

    /**
     * Gets the data value of the registry value at the current iteration
     * position.
     *
     * @return mixed
     */
    public function current()
    {
        return $this->registryKey->getValue($this->key(), $this->currentType());
    }

    /**
     * Gets the value type of the registry value at the current iteration
     * position.
     *
     * @return RegistryValueType
     */
    public function currentType()
    {
        return (int)$this->valueTypes[$this->pointer];
    }

    /**
     * Gets the name of the registry value at the current iteration position.
     *
     * @return string
     */
    public function key()
    {
        return (string)$this->valueNames[$this->pointer];
    }

    /**
     * Advances the iterator to the next registry value.
     */
    public function next()
    {
        $this->pointer++;
    }
}
