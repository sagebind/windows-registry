# Windows Registry Wrapper
A small library for accessing and manipulating the Windows registry. For that
one time that you need to access the Windows registry in a PHP application.

## Requirements
- Microsoft Windows (Vista or newer) or Windows Server (Windows Server 2003 or
  newer)
- PHP [com_dotnet](http://php.net/manual/en/book.com.php) extension

## Installation
Use [Composer](http://getcomposer.org):

```sh
> composer require coderstephen/windows-registry:dev-master
```

## Examples
Below is an example of creating a new registry key with some values and then
deleting them.

```php
use Coderstephen\Windows\Registry;

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
    print $subKey->getQualifiedName();
}

foreach ($key->getValueIterator() as $name => $value)
{
    printf("%s: %s\r\n", $name, $value);
}
```

## Disclaimer
Messing with the Windows registry can be dangerous; Microsoft has plenty of
warnings about how it can destroy your installation. Not only should you be
careful when accessing the registry, this library is not guaranteed to be 100%
safe to use and free of bugs. Use discretion, and test your code in a virtual
machine if possible. I am not liable for any damages caused by this library.
See the [license](LICENSE) for details.
