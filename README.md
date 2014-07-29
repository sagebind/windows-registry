# Windows Registry Wrapper
A small library for accessing and manipulating the Windows registry.

## Requirements
- Microsoft Windows (Vista or newer) or Windows Server (Windows Server 2003 or newer)
- PHP [com_dotnet](http://php.net/manual/en/book.com.php) extension

## Installation
Use [Composer](http://getcomposer.org):

```sh
> composer require coderstephen/windows-registry:dev-master
```

## Examples
Opening a registry key:

```php
use Coderstephen\Windows\Registry\Registry;

$registry = Registry::connect();
$key = $registry->getCurrentUser()->getSubKey('Control Panel\\Desktop');
```

Getting some values:

```php
print $key->getValue('ImageColor', Registry\RegistryValueType::DWORD());
print $key->getValue('Wallpaper', Registry\RegistryValueType::STRING());
```

Iterating over subkeys and key values:

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

## Stability
Nope
