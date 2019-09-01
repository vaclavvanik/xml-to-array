# Convert xml to array

This package provides a very simple class to convert xml to array.

## Install

You can install this package via composer.

``` bash
composer require vaclavvanik/xml-to-array
```

## Usage

```php
use VaclavVanik\XmlToArray\XmlToArray;

$xml = <<<'XML'
<root>
    <good_guy>
        <name>Luke Skywalker</name>
        <weapon>Lightsaber</weapon>
    </good_guy>
    <good_guy>
        <name><![CDATA[<h1>Gandalf</h1>]]></name>
        <weapon>Staff</weapon>
    </good_guy>
    <bad_guy lang="Black Speech">
        <name>Sauron</name>
        <weapon>Evil Eye</weapon>
    </bad_guy>
</root>
XML;

$result = XmlToArray::stringToArray($xml);
```

After running this piece of code `$result` will contain:

```php
[
    'root' => [
        'good_guy' => [
            [
                'name' => 'Luke Skywalker',
                'weapon' => 'Lightsaber',
            ],
            [
                'name' => '<h1>Gandalf</h1>',
                'weapon' => 'Staff',
            ],
        ],
        'bad_guy' => [
            '@attributes' => [
                'lang' => 'Black Speech',
            ],
            'name' => 'Sauron',
            'weapon' => 'Evil Eye',
        ],
    ],
];
```

Converting XML file is also available:

```php
use VaclavVanik\XmlToArray\XmlToArray;

$result = XmlToArray::stringToArray('my.xml');
```

Converting DOMDocument directly:

```php
use DOMDocument;
use VaclavVanik\XmlToArray\XmlToArray;

$doc = new DOMDocument();
//$doc->loadXML(...);

$xmlToArray = new XmlToArray($doc);
$result = $xmlToArray->toArray();
```

## Testing

```bash
vendor/bin/phpunit
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
