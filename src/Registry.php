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
 * Creates connections to a computer's registry and provides base keys for
 * accessing subkeys.
 */
class Registry
{
    /**
     * A handle to the WMI registry provider.
     */
    protected $handle;

    /**
     * Connects to a registry and returns a registry instance.
     * 
     * @param string $host
     * The host name or IP address of the computer whose registry to connect
     * to. Defaults to the local computer.
     * 
     * @param string $username
     * The user name to use to access the registry.
     * 
     * @param string $password [description]
     * The password to use to access the registry.
     * 
     * @return Registry
     */
    public static function connect($host = '.', $username = null, $password = null)
    {
        // create a wmi connection
        $swbemLocator = new \COM('WbemScripting.SWbemLocator', null, CP_UTF8);
        $swbemService = $swbemLocator->ConnectServer($host, 'root\default', $username, $password);
        $swbemService->Security_->ImpersonationLevel = 3;

        // initialize registry provider
        $registryObject = new static();
        $registryObject->handle = $swbemService->Get('StdRegProv');
        return $registryObject;
    }

    /**
     * Gets the base registry key for the hive CLASSES_ROOT.
     * @return RegistryKey
     */
    public function getClassesRoot()
    {
        return new RegistryKey($this->handle, RegistryHive::CLASSES_ROOT(), '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_CONFIG.
     * @return RegistryKey
     */
    public function getCurrentConfig()
    {
        return new RegistryKey($this->handle, RegistryHive::CURRENT_CONFIG(), '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_USER.
     * @return RegistryKey
     */
    public function getCurrentUser()
    {
        return new RegistryKey($this->handle, RegistryHive::CURRENT_USER(), '');
    }

    /**
     * Gets the base registry key for the hive LOCAL_MACHINE.
     * @return RegistryKey
     */
    public function getLocalMachine()
    {
        return new RegistryKey($this->handle, RegistryHive::LOCAL_MACHINE(), '');
    }

    /**
     * Gets the base registry key for the hive USERS.
     * @return RegistryKey
     */
    public function getUsers()
    {
        return new RegistryKey($this->handle, RegistryHive::USERS(), '');
    }

    /**
     * Gets the underlying handle object used to access the registry.
     * @return \VARIANT
     */
    public function getHandle()
    {
        return $this->handle;
    }
}
