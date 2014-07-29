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
     * The key we are iterating subkeys of.
     * @var RegistryKey
     */
    protected $registryKey;

    /**
     * The WMI StdRegProv object handle.
     * @var \COM
     */
    protected $handle;

    /**
     * The current iterator position.
     * @var int
     */
    protected $pointer = 0;

    /**
     * The number of values we are iterating over.
     * @var int
     */
    protected $count = 0;

    /**
     * A (hopefully) enumerable variant containing the value names.
     * @var VARIANT
     */
    protected $valueNames;

    /**
     * A (hopefully) enumerable variant containing the data types of values.
     * @var VARIANT
     */
    protected $valueTypes;

    /**
     * Creates a new registry value iterator.
     *
     * @param RegistryKey $registryKey
     * The key whose values to iterate over.
     */
    public function __construct(RegistryKey $registryKey)
    {
        $this->registryKey = $registryKey;
        $this->handle = $registryKey->getHandle();
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
        $errorCode = $this->handle->EnumValues(
            $this->registryKey->getHive()->value(),
            $this->registryKey->getQualifiedName(),
            $this->valueNames,
            $this->valueTypes
        );

        // make sure the enum isn't empty
        if ($errorCode === 0
            && (variant_get_type($this->valueNames) & VT_ARRAY)
            && (variant_get_type($this->valueTypes) & VT_ARRAY))
        {
            // store the number of values
            $this->count = count($this->valueNames);
        }
    }

    /**
     * Checks if the current iteration position is valid.
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
        return RegistryValueType::memberByValue((int)$this->valueTypes[$this->pointer]);
    }

    /**
     * Gets the name of the registry value at the current iteration position.
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
