<?php

namespace VaclavVanikTest\XmlToArray;

use DOMDocument;
use DOMException;
use PHPUnit\Framework\TestCase;
use VaclavVanik\XmlToArray\XmlToArray;

use function file_get_contents;

class XmlToArrayTest extends TestCase
{
    private function domFromFile(string $file) : DOMDocument
    {
        $doc = new DOMDocument();
        $doc->load($file);

        return $doc;
    }

    public function testConstructor() : void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<root/>');

        $xmlToArray = new XmlToArray($doc);
        $this->assertSame(['root' => ''], $xmlToArray->toArray());
    }

    public function testConvertArray() : void
    {
        $array = [
            'root' => [
                'good_guy' => [
                    [
                        'name' => 'Luke Skywalker',
                        'weapon' => 'Lightsaber',
                    ],
                    [
                        'name' => 'Gandalf',
                        'weapon' => 'Staff',
                    ],
                ],
            ],
        ];
        $xmlToArray = new XmlToArray($this->domFromFile(__DIR__ . '/_files/array.xml'));
        $this->assertSame($array, $xmlToArray->toArray());
    }

    public function testConvertAttributes() : void
    {
        $attributes = [
            'root' => [
                'bad_guy' => [
                    '@attributes' => [
                        'lang' => 'Black Speech',
                    ],
                    'name' => 'Sauron',
                    'weapon' => 'Evil Eye',
                ],
            ],
        ];

        $xmlToArray = new XmlToArray($this->domFromFile(__DIR__ . '/_files/attributes.xml'));
        $this->assertSame($attributes, $xmlToArray->toArray());
    }

    public function testConvertCdata() : void
    {
        $cdata = [
            'root' => [
                'good_guy' => [
                    'name' => '<h1>Gandalf</h1>',
                    'weapon' => 'Staff',
                ],
            ],
        ];

        $xmlToArray = new XmlToArray($this->domFromFile(__DIR__ . '/_files/cdata.xml'));
        $this->assertSame($cdata, $xmlToArray->toArray());
    }

    public function testSimple() : void
    {
        $simple = [
            'root' => [
                'good_guy' => [
                    'name' => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],
            ],
        ];

        $xmlToArray = new XmlToArray($this->domFromFile(__DIR__ . '/_files/simple.xml'));
        $this->assertSame($simple, $xmlToArray->toArray());
    }

    public function testStringToArray() : void
    {
        $simple = [
            'root' => [
                'good_guy' => [
                    'name' => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],
            ],
        ];

        $result = XmlToArray::stringToArray(file_get_contents(__DIR__ . '/_files/simple.xml'));
        $this->assertSame($simple, $result);
    }

    public function testFileToArray() : void
    {
        $simple = [
            'root' => [
                'good_guy' => [
                    'name' => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],
            ],
        ];

        $result = XmlToArray::fileToArray(__DIR__ . '/_files/simple.xml');
        $this->assertSame($simple, $result);
    }

    public function testStringToArrayThrowsDomException() : void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessageMatches('/^start tag expected/i');
        XmlToArray::stringToArray('foo');
    }

    public function testFileToArrayThrowsDomException() : void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessageMatches('/^failed to load external entity/i');
        XmlToArray::fileToArray(__DIR__ . '/_files/non-exist.xml');
    }
}
