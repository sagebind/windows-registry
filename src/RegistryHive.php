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

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * An enum containing valid registry hives.
 */
final class RegistryHive extends AbstractEnumeration
{
    const CLASSES_ROOT = 0x80000000;
    const CURRENT_CONFIG = 0x80000005;
    const CURRENT_USER = 0x80000001;
    const LOCAL_MACHINE = 0x80000002;
    const USERS = 0x80000003;
}