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
     * Opens a registry key in the given registry hive with a specified name.
     *
     * @param RegistryHive $hive
     * The registry hive the key is located in.
     *
     * @param string $name
     * The fully-qualified path of the key.
     *
     * @return RegistryKey
     */
    public static function open(RegistryHive $hive, $name)
    {
        return new static(new \COM('winmgmts://./root/default:StdRegProv'), $hive, $name);
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
     * @return \COM
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Gets the specified subkey.
     *
     * @param string $name
     * The name of the subkey.
     *
     * @return RegistryKey
     */
    public function getSubKey($name)
    {
        return new self($this->handle, $this->hive, $this->name . '\\' . $name);
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
     * Gets an iterator for iterating over key values.
     * @return RegistryValueIterator
     */
    public function getValueIterator()
    {
        return new RegistryValueIterator($this);
    }

    /**
     * Gets a key value.
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
    public function getValue($name, RegistryValueType $type)
    {
        // create a variant to store the key value data
        $valueData = new \VARIANT();

        // get the value data type
        switch ($type->value())
        {
            // string type
            case RegistryValueType::STRING:
                // get the data of the value
                $this->handle->GetStringValue($this->hive->value(), $this->name, $name, $valueData);
                return (string)$valueData;

            // expanded string type
            case RegistryValueType::EXPANDED_STRING:
                // get the data of the value
                $this->handle->GetExpandedStringValue($this->hive->value(), $this->name, $name, $valueData);
                return (string)$valueData;

            // binary type
            case RegistryValueType::BINARY:
                // get the data of the value
                $this->handle->GetBinaryValue($this->hive->value(), $this->name, $name, $valueData);
                $binaryString = '';

                // enumerate over each byte
                if ((variant_get_type($valueData) & VT_ARRAY) === VT_ARRAY)
                {
                    foreach ($valueData as $byte)
                    {
                        // add the byte code to the byte string
                        $binaryString .= chr((int)$byte);
                    }
                }

                return $binaryString;

            // int type
            case RegistryValueType::DWORD:
                // get the data of the value
                $this->handle->GetDWORDValue($this->hive->value(), $this->name, $name, $valueData);
                return (int)$valueData;

            // string array type
            case RegistryValueType::MULTI_STRING:
                // get the data of the value
                $this->handle->GetMultiStringValue($this->hive->value(), $this->name, $name, $valueData);

                $stringArray = array();
                // enumerate over each sub string
                if ((variant_get_type($valueData) & VT_ARRAY) === VT_ARRAY)
                {
                    foreach ($valueData as $subValueData)
                    {
                        $stringArray[] = (string)$subValueData;
                    }
                }
                return $stringArray;
        }

        return $valueNode;
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
    }

    /**
     * Creates a new key value object.
     * @param \COM $handle
     * The WMI StdRegProv object handle to use.
     * 
     * @param string $hive
     * The registry hive the key is located in.
     * 
     * @param string $name
     * The fully-qualified name of the key.
     */
    protected function __construct(\COM $handle, $hive, $name)
    {
        $this->handle = $handle;
        $this->hive = $hive;
        $this->name = $name;
    }
}
