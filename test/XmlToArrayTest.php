<?php

namespace VaclavVanikTest\XmlToArray;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use VaclavVanik\XmlToArray\XmlToArray;

class XmlToArrayTest extends TestCase
{
    public function testConstructor()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<root/>');

        $xmlToArray = new XmlToArray($doc);
        $this->assertSame(['root' => ''], $xmlToArray->toArray());
    }

    public function testArray()
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
        $result = XmlToArray::fileToArray(__DIR__ . '/_res/array.xml');
        $this->assertSame($array, $result);
    }

    public function testAttributes()
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
        $result = XmlToArray::fileToArray(__DIR__ . '/_res/attributes.xml');
        $this->assertSame($attributes, $result);
    }

    public function testCdata()
    {
        $cdata = [
            'root' => [
                'good_guy' => [
                    'name' => '<h1>Gandalf</h1>',
                    'weapon' => 'Staff',
                ],
            ],
        ];
        $result = XmlToArray::fileToArray(__DIR__ . '/_res/cdata.xml');
        $this->assertSame($cdata, $result);
    }

    public function testSimple()
    {
        $simple = [
            'root' => [
                'good_guy' => [
                    'name' => 'Luke Skywalker',
                    'weapon' => 'Lightsaber',
                ],
            ],
        ];
        $result = XmlToArray::fileToArray(__DIR__ . '/_res/simple.xml');
        $this->assertSame($simple, $result);
    }
}
