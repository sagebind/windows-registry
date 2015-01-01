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
 * Iterates over subkeys of a registry key.
 */
class RegistryKeyIterator implements \RecursiveIterator
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
     * The number of subkeys we are iterating over.
     * @var int
     */
    protected $count = 0;

    /**
     * A (hopefully) enumerable variant containing the names of subkeys.
     * @var VARIANT
     */
    protected $subKeyNames;

    /**
     * Creates a new registry key iterator.
     *
     * @param RegistryKey $registryKey
     * The parent key of the subkeys to iterate over.
     */
    public function __construct(RegistryKey $registryKey)
    {
        $this->registryKey = $registryKey;
        $this->handle = $registryKey->getHandle();
    }

    /**
     * Returns if a subkey iterator can be created for the current key.
     * @return boolean
     */
    public function hasChildren()
    {
        $iterator = $this->getChildren();
        $iterator->rewind();
        return $iterator->valid();
    }

    /**
     * Gets an iterator for subkeys of the current registry key.
     * @return RegistryKeyIterator
     */
    public function getChildren()
    {
        return new static($this->current());
    }

    /**
     * Rewinds the iterator to the first key.
     */
    public function rewind()
    {
        // reset pointer and count
        $this->pointer = 0;
        $this->count = 0;

        // create an empty variant to store subkey names
        $this->subKeyNames = new \VARIANT();

        // attempt to enumerate subkeys
        $errorCode = $this->handle->EnumKey(
            $this->registryKey->getHive()->value(),
            $this->registryKey->getQualifiedName(),
            $this->subKeyNames
        );

        // make sure the enum isn't empty
        if ($errorCode === 0 && (variant_get_type($this->subKeyNames) & VT_ARRAY)) {
            // store the number of subkeys
            $this->count = count($this->subKeyNames);
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
     * Gets the registry key at the current iteration position.
     * @return RegistryKey
     */
    public function current()
    {
        return $this->registryKey->getSubKey($this->key());
    }

    /**
     * Gets the name of the registry key at the current iteration position.
     * @return string
     */
    public function key()
    {
        return (string)$this->subKeyNames[$this->pointer];
    }

    /**
     * Advances the iterator to the next registry key.
     */
    public function next()
    {
        $this->pointer++;
    }
}
