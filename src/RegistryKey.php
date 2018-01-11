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
 * Represents a single key in the Windows registry.
 */
final class RegistryKey
{
    const TYPE_SZ = 1;
    const TYPE_EXPAND_SZ = 2;
    const TYPE_BINARY = 3;
    const TYPE_DWORD = 4;
    const TYPE_MULTI_SZ = 7;
    const TYPE_QWORD = 11;

    /**
     * @var RegistryHandle An open registry handle.
     */
    protected $handle;

    /**
     * @var int The registry hive the key is located in.
     */
    protected $hive;

    /**
     * @var string Fully-qualified name of the key.
     */
    protected $name;

    /**
     * Creates a new key value object.
     *
     * @param RegistryHandle $handle An open registry handle.
     * @param int            $hive   The registry hive the key is located in.
     * @param string         $name   The fully-qualified name of the key.
     */
    public function __construct(RegistryHandle $handle, $hive, $name)
    {
        $this->handle = $handle;
        $this->hive = $hive;
        $this->name = $name;
    }

    /**
     * Gets the local (unqualified) name of the key.
     *
     * @return string
     */
    public function getName()
    {
        if (strpos($this->name, '\\') !== false) {
            return substr($this->name, strpos($this->name, '\\') + 1);
        }
        return $this->name;
    }

    /**
     * Gets the fully-qualified name of the key.
     *
     * @return string
     */
    public function getQualifiedName()
    {
        return $this->name;
    }

    /**
     * Gets the registry hive the key is located in.
     *
     * @return int
     */
    public function getHive()
    {
        return $this->hive;
    }

    /**
     * Gets the underlying handle object used to access the registry.
     *
     * @return RegistryHandle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Gets a registry subkey with the specified name.
     *
     * @param string $name The name or path of the subkey.
     *
     * @return RegistryKey
     */
    public function getSubKey($name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name.'\\'.$name;

        // call EnumKeys on the subkey to check if it exists
        $sNames = new \VARIANT();
        if ($this->handle->enumKey($this->hive, $subKeyName, $sNames) == 0) {
            return new static($this->handle, $this->hive, $subKeyName);
        }

        throw new KeyNotFoundException("The key \"{$subKeyName}\" does not exist.");
    }

    /**
     * Gets the parent registry key of the current subkey, or null if the key
     * is a root key.
     *
     * @return RegistryKey|null
     */
    public function getParentKey()
    {
        // check if we have a parent key
        if (dirname($this->name) !== '.') {
            return new static($this->handle, $this->hive, dirname($this->name));
        }

        return;
    }

    /**
     * Creates a new registry subkey.
     *
     * @param string $name The name or path of the key relative to the current key.
     *
     * @return RegistryKey
     */
    public function createSubKey($name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name.'\\'.$name;

        if ($this->handle->createKey($this->hive, $subKeyName) !== 0) {
            throw new OperationFailedException("Failed to create key \"{$subKeyName}\".");
        }

        return new static($this->handle, $hive, $name);
    }

    /**
     * Deletes a registry subkey.
     *
     * @param string $name The name or path of the subkey to delete.
     */
    public function deleteSubKey($name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name.'\\'.$name;

        if ($this->handle->deleteKey($this->hive, $subKeyName) !== 0) {
            throw new OperationFailedException("Failed to delete key '{$subKeyName}'.");
        }
    }

    /**
     * Gets an iterator for iterating over subkeys of this key.
     *
     * @return RegistryKeyIterator
     */
    public function getSubKeyIterator()
    {
        return new RegistryKeyIterator($this->handle, $this);
    }

    /**
     * Checks if a named value exists with the given name.
     *
     * @param string $name The name of the value to check.
     *
     * @return bool True if the value exists, otherwise false.
     */
    public function valueExists($name)
    {
        // look for the suspicious "1" error code (which I believe to mean does
        // not exist)
        return $this->handle->getStringValue(
            $this->hive,
            $this->name,
            $name,
            null
        ) !== 1;
    }

    /**
     * Gets the value data of a named key value.
     *
     * @param string $name The name of the value.
     * @param int    $type The value type of the value.
     *
     * @return mixed The value data of the value.
     */
    public function getValue($name, $type = null)
    {
        // create a variant to store the key value data
        $valueData = new \VARIANT();

        // auto detect type
        // not recommended - see getValueType() for details
        if (!$type) {
            $type = $this->getValueType($name);
        }

        $normalizedValue = null;
        $errorCode = 0;

        // get the value data type
        switch ($type) {
            // string type
            case self::TYPE_SZ:
                // get the data of the value
                $errorCode = $this->handle->getStringValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // expanded string type
            case self::TYPE_EXPAND_SZ:
                // get the data of the value
                $errorCode = $this->handle->getExpandedStringValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // binary type
            case self::TYPE_BINARY:
                // get the data of the value
                $errorCode = $this->handle->getBinaryValue($this->hive, $this->name, $name, $valueData);
                $binaryString = '';

                // enumerate over each byte
                if (variant_get_type($valueData) & VT_ARRAY) {
                    foreach ($valueData as $byte) {
                        // add the byte code to the byte string
                        $binaryString .= chr((int)$byte);
                    }
                }

                $normalizedValue = $binaryString;
                break;

            // int type
            case self::TYPE_DWORD:
                // get the data of the value
                $errorCode = $this->handle->getDWORDValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (int)$valueData;
                break;

            // big-int type
            case self::TYPE_QWORD:
                // get the data of the value
                $errorCode = $this->handle->getQWORDValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // string array type
            case self::TYPE_MULTI_SZ:
                // get the data of the value
                $errorCode = $this->handle->getMultiStringValue($this->hive, $this->name, $name, $valueData);

                $stringArray = array();
                // enumerate over each sub string
                if (variant_get_type($valueData) & VT_ARRAY) {
                    foreach ($valueData as $subValueData) {
                        $stringArray[] = (string)$subValueData;
                    }
                }

                $normalizedValue = $stringArray;
                break;
        }

        // check for successful read
        if ($errorCode !== 0) {
            throw new OperationFailedException("Failed to read value \"{$name}\".");
        }

        return $normalizedValue;
    }

    /**
     * Sets the value data of a named key value.
     *
     * @param string $name  The name of the value.
     * @param mixed  $value The value data of the value.
     * @param int    $type  The value type of the value.
     */
    public function setValue($name, $value, $type)
    {
        // store error code to check for success later
        $errorCode = 0;

        // set differently depending on type
        switch ($type) {
            case self::TYPE_SZ:
                $errorCode = $this->handle->setStringValue($this->hive, $this->name, $name, (string)$value);
                break;

            case self::TYPE_EXPAND_SZ:
                $errorCode = $this->handle->setExpandedStringValue($this->hive, $this->name, $name, (string)$value);
                break;

            case self::TYPE_BINARY:
                if (is_string($value)) {
                    $value = array_map('ord', str_split($value));
                }
                $errorCode = $this->handle->setBinaryValue($this->defKey, $this->name, $name, $value);
                break;

            case self::TYPE_DWORD:
                $errorCode = $this->handle->setDWORDValue($this->hive, $this->name, $name, (int)$value);
                break;

            case self::TYPE_MULTI_SZ:
                if (!is_array($value)) {
                    throw new Exception('Cannot set non-array type as MultiString.');
                }
                $errorCode = $this->handle->setMultiStringValue($this->defKey, $this->name, $name, $value);
                break;

            default:
                throw new InvalidTypeException("The value {$type} is not a valid registry type.");
        }

        // check for successful write
        if ($errorCode !== 0) {
            throw new OperationFailedException("Failed to write value \"{$name}\".");
        }
    }

    /**
     * Deletes a named value from the key.
     *
     * @param string $name The name of the named value to delete.
     */
    public function deleteValue($name)
    {
        // attempt to delete the value
        $errorCode = $this->handle->deleteValue($this->hive, $this->name, $name);

        if ($errorCode !== 0) {
            if (!$this->valueExists($name)) {
                throw new ValueNotFoundException("The value '{$name}' does not exist.");
            }

            throw new OperationFailedException("Failed to delete value '{$name}' from key '$this->name}'.");
        }
    }

    /**
     * Gets an iterator for iterating over key values.
     *
     * @return RegistryValueIterator
     */
    public function getValueIterator()
    {
        return new RegistryValueIterator($this->handle, $this);
    }

    /**
     * Gets the data type of a given value.
     *
     * Note that this is a relatively expensive operation, especially for keys
     * with lots of values.
     *
     * @param string $name The name of the value.
     *
     * @return int The type of the value.
     */
    public function getValueType($name)
    {
        // iterate over all values in the key
        $iterator = $this->getValueIterator();
        foreach ($iterator as $key => $value) {
            // is this the value we are looking for?
            if ($key === $name) {
                // value is now cached through the iterator
                return $iterator->currentType();
            }
        }

        throw new ValueNotFoundException("The value '{$name}' does not exist.");
    }
}
