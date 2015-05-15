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

namespace Windows\Registry\Tests;

use Windows\Registry\Registry;
use Windows\Registry\RegistryHandle;
use Windows\Registry\RegistryKey;

class RegistryKeyTest extends \PHPUnit_Framework_TestCase
{
    protected $stubHandle;

    protected function newKey($hive = Registry::HKEY_LOCAL_MACHINE, $name = 'Software')
    {
        return new RegistryKey($this->stubHandle, $hive, $name);
    }

    public function setUp()
    {
        $this->stubHandle = $this->getMockBuilder(RegistryHandle::class)->disableOriginalConstructor()->getMock();
    }

    public function testGetHandleReturnsHandle()
    {
        $key = $this->newKey();
        $this->assertSame($this->stubHandle, $key->getHandle());
    }

    public function testGetHiveReturnsHive()
    {
        $hive = Registry::HKEY_LOCAL_MACHINE;
        $key = $this->newKey($hive);
        $this->assertSame($hive, $key->getHive());
    }

    public function testGetNameReturnsName()
    {
        $key = $this->newKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');
        $this->assertSame('Microsoft', $key->getName());
    }

    public function testGetQualifiedNameReturnsQualifiedName()
    {
        $key = $this->newKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');
        $this->assertSame('Software\Microsoft', $key->getQualifiedName());
    }

    public function testGetParentKey()
    {
        $key = $this->newKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');
        $this->assertSame('Software', $key->getParentKey()->getQualifiedName());
    }

    public function testGetParentKeyGetsHiveKey()
    {
        $key = $this->newKey(Registry::HKEY_LOCAL_MACHINE, 'Software');
        $this->assertSame('', $key->getParentKey()->getQualifiedName());
    }
}
