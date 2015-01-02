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

namespace Windows\Registry;

/**
 * Iterates over the subkeys of a registry key.
 */
class RegistryKeyIterator implements \RecursiveIterator
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
     * The number of subkeys we are iterating over.
     * @type int
     */
    protected $count = 0;

    /**
     * A (hopefully) enumerable variant containing the names of subkeys.
     * @type VARIANT
     */
    protected $subKeyNames;

    /**
     * Creates a new registry key iterator.
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
        $errorCode = $this->handle->enumKey($this->hive, $this->keyName, $this->subKeyNames);

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
