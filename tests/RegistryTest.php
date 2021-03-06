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

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    protected $stubHandle;
    protected $registry;

    public function setUp()
    {
        $this->stubHandle = $this->getMockBuilder(RegistryHandle::class)->disableOriginalConstructor()->getMock();
        $this->registry = new Registry($this->stubHandle);
    }

    public function testGetHandle()
    {
        $this->assertSame($this->stubHandle, $this->registry->getHandle());
    }
}
