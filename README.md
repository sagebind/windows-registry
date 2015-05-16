# Windows Registry Wrapper
[![Version](https://img.shields.io/packagist/v/coderstephen/windows-registry.svg)](https://packagist.org/packages/coderstephen/windows-registry)
[![License](https://img.shields.io/packagist/l/coderstephen/windows-registry.svg)](https://packagist.org/packages/coderstephen/windows-registry)

A small library for accessing and manipulating the Registry on Microsoft Windows systems. For that
one time that you need to access the Windows Registry in a PHP application.

This library can be (and has been) used in production code, but *please consider* reading the [disclaimer](#disclaimer) below before using.

## Features
- Read and write access to any hive, key, or value in the registry (that you have permissions to)
- Automatic conversion between all registry value data types to PHP scalar types
- Lazy-loaded iterators over lists of values and recursive iterators over keys and subkeys
- Ability to connect to registries on remote computers using a remote WMI (Windows Management Instrumentation) connection (see Microsoft's docs on [how to connect to WMI remotely](https://msdn.microsoft.com/en-us/library/aa389290%28v=vs.85%29.aspx) for details)

## Requirements
- Microsoft Windows (Vista or newer) or Windows Server (Windows Server 2003 or
  newer)
- PHP [com_dotnet](http://php.net/manual/en/book.com.php) extension

## Installation
Use [Composer](http://getcomposer.org):

```sh
> composer require coderstephen/windows-registry:~0.9
```

## Examples
Below is an example of creating a new registry key with some values and then
deleting them.

```php
use Windows\Registry;

$hklm = Registry\Registry::connect()->getLocalMachine();
$keyPath = 'Software\\MyKey\\MySubKey';

// create a new key
try
{
    $mySubKey = $hklm->createSubKey($keyPath);
}
catch (Registry\Exception $e)
{
    print "Key '{$keyPath}' not created" . PHP_EOL;
}

// create a new value
$mySubKey->setValue('Example DWORD Value', 250, Registry\RegistryKey::TYPE_DWORD);

// delete the new value
$mySubKey->deleteValue('Example DWORD Value');

// delete the new key
try
{
    $hklm->deleteSubKey($keyPath);
}
catch (Registry\Exception $e)
{
    print "Key '{$keyPath}' not deleted" . PHP_EOL;
}
```

You can also iterate over subkeys and values using built-in iterators:

```php
foreach ($key->getSubKeyIterator() as $name => $subKey)
{
    print $subKey->getQualifiedName() . PHP_EOL;
}

foreach ($key->getValueIterator() as $name => $value)
{
    printf("%s: %s\r\n", $name, $value);
}
```

## Disclaimer
Messing with the Windows Registry can be dangerous; Microsoft has plenty of
warnings about how it can **destroy your installation**. Not only should you be
careful when accessing the Registry, this library is *not guaranteed* to be 100%
safe to use and free of bugs. Use discretion, and **test your code in a virtual
machine if possible**. I am not liable for *any* damages caused by this library.
See the [license](LICENSE) for details.
