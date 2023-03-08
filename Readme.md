XML parser KPHP compatibility layer.


Run as PHP

```shell
php -d opcache.enable_cli=1 \
    -d opcache.preload=preload.php \
    -f tests/testParseXml.php
```

Use in KPHP:

```php

//Load class
require_once 'path/to/XMLParser.php'
//declare functions xml_parser_*
require_once 'path/to/xml_parser.php'

```

Compile options:

```shell
--extra-linker-flags  -l:libexpat.a
```

Dependencies: **\Sigmalab\IsKPHP** class, you can implement self own. 
```php
class IsKPHP
{
	public static bool $isKPHP = true;
}
#ifndef KPHP
IsKPHP::$isKPHP = false;
#endif
```

(c) Alexey V. Vasilyev, Sigmalab LLC.
License: Apache 2.