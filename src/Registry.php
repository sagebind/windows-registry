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
 * Creates connections to a computer's registry and provides base keys for
 * accessing subkeys.
 */
final class Registry
{
    const HKEY_CLASSES_ROOT = 0x80000000;
    const HKEY_CURRENT_USER = 0x80000001;
    const HKEY_LOCAL_MACHINE = 0x80000002;
    const HKEY_USERS = 0x80000003;
    const HKEY_CURRENT_CONFIG = 0x80000005;

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
     * @param string $password
     * The password to use to access the registry.
     *
     * @return Registry
     */
    public static function connect($host = '.', $username = null, $password = null)
    {
        // create a WMI connection
        $swbemLocator = new \COM('WbemScripting.SWbemLocator', null, CP_UTF8);
        $swbemService = $swbemLocator->ConnectServer($host, 'root\default', $username, $password);
        $swbemService->Security_->ImpersonationLevel = 3;

        // initialize registry provider
        $handle = new RegistryHandle($swbemService->Get('StdRegProv'));
        return new static($handle);
    }

    /**
     * An open registry handle.
     * @type RegistryHandle
     */
    protected $handle;

    /**
     * Creates a new registry connection object.
     *
     * @param RegistryHandle $handle
     * The WMI registry provider handle to use.
     */
    public function __construct(RegistryHandle $handle)
    {
        $this->handle = $handle;
    }

    /**
     * Gets the underlying handle object used to access the registry.
     * @return RegistryHandle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Gets the base registry key for the hive CLASSES_ROOT.
     * @return RegistryKey
     */
    public function getClassesRoot()
    {
        return new RegistryKey($this->handle, Registry::HKEY_CLASSES_ROOT, '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_CONFIG.
     * @return RegistryKey
     */
    public function getCurrentConfig()
    {
        return new RegistryKey($this->handle, Registry::HKEY_CURRENT_CONFIG, '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_USER.
     * @return RegistryKey
     */
    public function getCurrentUser()
    {
        return new RegistryKey($this->handle, Registry::HKEY_CURRENT_USER, '');
    }

    /**
     * Gets the base registry key for the hive LOCAL_MACHINE.
     * @return RegistryKey
     */
    public function getLocalMachine()
    {
        return new RegistryKey($this->handle, Registry::HKEY_LOCAL_MACHINE, '');
    }

    /**
     * Gets the base registry key for the hive USERS.
     * @return RegistryKey
     */
    public function getUsers()
    {
        return new RegistryKey($this->handle, Registry::HKEY_USERS, '');
    }
}
