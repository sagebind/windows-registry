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
 * Represents a single key in the Windows registry.
 */
class RegistryKey
{
    /**
     * The WMI StdRegProv object handle.
     * @var \COM
     */
    protected $handle;

    /**
     * The registry hive the key is located in.
     * @var RegistryHive
     */
    protected $hive;

    /**
     * Fully-qualified name of the key.
     * @var string
     */
    protected $name;

    /**
     * Cache for reading values.
     * @var array
     */
    protected $cache;

    /**
     * Creates a new key value object.
     * 
     * @param \VARIANT $handle
     * The WMI registry provider handle to use.
     * 
     * @param RegistryHive $hive
     * The registry hive the key is located in.
     * 
     * @param string $name
     * The fully-qualified name of the key.
     */
    public function __construct(\VARIANT $handle, RegistryHive $hive, $name)
    {
        $this->handle = $handle;
        $this->hive = $hive;
        $this->name = $name;
        $this->cache = new ValueCache();
    }

    /**
     * Gets the local (unqualified) name of the key.
     * @return string
     */
    public function getName()
    {
        return basename($this->name);
    }

    /**
     * Gets the fully-qualified name of the key.
     * @return string
     */
    public function getQualifiedName()
    {
        return $this->name;
    }

    /**
     * Gets the registry hive the key is located in.
     * @return RegistryHive
     */
    public function getHive()
    {
        return $this->hive;
    }

    /**
     * Gets the underlying handle object used to access the registry.
     * @return \VARIANT
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Deletes the registry key.
     */
    public function delete()
    {
        if ($this->handle->DeleteKey($this->hive->value(), $this->name) !== 0)
        {
            throw new OperationFailedException("Failed to delete key '{$this->name}'.");
        }
    }

    /**
     * Gets a registry subkey with the specified name.
     *
     * @param string $name
     * The name or path of the subkey.
     *
     * @return RegistryKey
     */
    public function getSubKey($name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name . '\\' . $name;
        return new static($this->handle, $this->hive, $subKeyName);
    }

    /**
     * Gets the parent registry key to the current subkey.
     *
     * @return RegistryKey
     */
    public function getParentKey()
    {
        // check if we have a parent key
        if (dirname($this->name) !== '.')
        {
            return new static($this->handle, $this->hive, dirname($this->name));
        }

        return null;
    }

    /**
     * Creates a new registry subkey.
     * 
     * @param string $name
     * The name or path of the key.
     * 
     * @return RegistryKey
     */
    public function createSubKey($name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name . '\\' . $name;

        if ($this->handle->CreateKey($this->hive->value(), $subKeyName) !== 0)
        {
            throw new OperationFailedException("Failed to create key '{$subKeyName}'.");
        }

        return new static($this->handle, $hive, $name);
    }

    /**
     * Deletes a registry subkey.
     * 
     * @param string $name
     * The name or path of the subkey to delete.
     */
    public function deleteSubKey($name)
    {
        $this->getSubKey($name)->delete();
    }

    /**
     * Gets an iterator for iterating over subkeys of this key.
     * @return RegistryKeyIterator
     */
    public function getSubKeyIterator()
    {
        return new RegistryKeyIterator($this);
    }

    /**
     * Checks if a named value exists with the given name.
     * 
     * @param string $name
     * The name of the value to check.
     * 
     * @return boolean
     */
    public function valueExists($name)
    {
        // look for the suspicious "1" error code (which I believe to mean does
        // not exist)
        return $this->handle->GetStringValue(
            $this->hive->value(),
            $this->name,
            $name,
            null
        ) !== 1;
    }

    /**
     * Gets the value data of a named key value.
     * 
     * @param string $name
     * The name of the value.
     * 
     * @param RegistryValueType $type
     * The value type of the value.
     * 
     * @return mixed
     * The value data of the value.
     */
    public function getValue($name, RegistryValueType $type = null)
    {
        // check if value is in the cache
        if ($this->cache->hasValue($name))
        {
            return $this->cache->getValueData($name);
        }

        // create a variant to store the key value data
        $valueData = new \VARIANT();

        // auto detect type
        // not recommended - see getValueType() for details
        if (!$type)
            $type = $this->getValueType($name);

        $normalizedValue = null;
        $errorCode = 0;

        // get the value data type
        switch ($type->value())
        {
            // string type
            case RegistryValueType::STRING:
                // get the data of the value
                $errorCode = $this->handle->GetStringValue($this->hive->value(), $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // expanded string type
            case RegistryValueType::EXPANDED_STRING:
                // get the data of the value
                $this->handle->GetExpandedStringValue($this->hive->value(), $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // binary type
            case RegistryValueType::BINARY:
                // get the data of the value
                $errorCode = $this->handle->GetBinaryValue($this->hive->value(), $this->name, $name, $valueData);
                $binaryString = '';

                // enumerate over each byte
                if (variant_get_type($valueData) & VT_ARRAY)
                {
                    foreach ($valueData as $byte)
                    {
                        // add the byte code to the byte string
                        $binaryString .= chr((int)$byte);
                    }
                }

                $normalizedValue = $binaryString;
                break;

            // int type
            case RegistryValueType::DWORD:
                // get the data of the value
                $this->handle->GetDWORDValue($this->hive->value(), $this->name, $name, $valueData);
                $normalizedValue = (int)$valueData;
                break;

            // int type
            case RegistryValueType::QWORD:
                // get the data of the value
                $this->handle->GetQWORDValue($this->hive->value(), $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // string array type
            case RegistryValueType::MULTI_STRING:
                // get the data of the value
                $this->handle->GetMultiStringValue($this->hive->value(), $this->name, $name, $valueData);

                $stringArray = array();
                // enumerate over each sub string
                if (variant_get_type($valueData) & VT_ARRAY)
                {
                    foreach ($valueData as $subValueData)
                    {
                        $stringArray[] = (string)$subValueData;
                    }
                }

                $normalizedValue = $stringArray;
                break;
        }
        
        $this->cache->storeValue($name, $type, $normalizedValue);

        return $normalizedValue;
    }

    public function setValue($name, $value, RegistryValueType $type = null)
    {
        // automatically choose a type if no type is specified
        if (!$type)
        {
            if (is_array($value))
            {
                $type = RegistryValueType::MULTI_STRING();
            }

            else if (is_numeric($value))
            {
                $type = RegistryValueType::DWORD();
            }

            else
            {
                $type = RegistryValueType::STRING();
            }
        }

        switch ($type->value())
        {
            case RegistryValueType::STRING:
                $this->handle->SetStringValue($this->hive, $keyPath, $name, (string)$value);
                break;

            case RegistryValueType::EXPANDED_STRING:
                $this->handle->SetExpandedStringValue($this->hive, $keyPath, $name, (string)$value);
                break;

            case RegistryValueType::BINARY:
                if (is_string($value))
                {
                    $value = array_map('ord', str_split($value));
                }
                $this->handle->SetBinaryValue($this->defKey, $keyPath, $name, $value);
                break;

            case RegistryValueType::DWORD:
                $this->handle->SetDWORDValue($this->hive, $keyPath, $name, (int)$value);
                break;

            case RegistryValueType::MULTI_STRING:
                if (!is_array($value))
                {
                    throw new Exception("Cannot set non-array type as MultiString.");
                }
                $this->handle->GetMultiStringValue($this->defKey, $keyPath, $name, $value);
                break;
        }

        // update cache
        $this->cache->storeValue($name, $type, $value);
    }

    /**
     * Deletes a named value from the key.
     * 
     * @param string $name
     * The name of the named value to delete.
     */
    public function deleteValue($name)
    {
        // attempt to delete the value
        $errorCode = $this->handle->DeleteValue($this->hive->value(), $this->name, $name);

        if ($errorCode !== 0) {
            if (!$this->valueExists($name)) {
                throw new ValueNotFoundException("The value '{$name}' does not exist.");
            }

            throw new OperationFailedException("Failed to delete value '{$name}' from key '$this->name}'.");
        }
    }

    /**
     * Gets the data type of a given value.
     *
     * Note that this is an expensive operation if the value is not in the
     * cache, especially for keys with lots of values.
     * 
     * @param string $name
     * The name of the value.
     * 
     * @return RegistryValueType
     */
    public function getValueType($name)
    {
        // is the value cached?
        if ($this->cache->hasValue($name))
        {
            // get the type from the cache
            return $this->cache->getValueType($name);
        }

        // iterate over all values in the key
        $iterator = $this->getValueIterator();
        foreach ($iterator as $key => $value)
        {
            // is this the value we are looking for?
            if ($key === $name)
            {
                // value is now cached through the iterator
                return $iterator->currentType();
            }
        }

        throw new ValueNotFoundException("The value '{$name}' does not exist.");
    }

    /**
     * Gets an iterator for iterating over key values.
     * @return RegistryValueIterator
     */
    public function getValueIterator()
    {
        return new RegistryValueIterator($this);
    }
}
