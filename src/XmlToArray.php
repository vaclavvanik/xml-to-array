<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use DOMDocument;
use DOMNode;

use function count;
use function in_array;
use function trim;

use const XML_CDATA_SECTION_NODE;
use const XML_TEXT_NODE;

class XmlToArray
{
    /**
     * @var DOMDocument
     */
    private $doc;

    public function __construct(DOMDocument $doc)
    {
        $this->doc = $doc;
    }

    public static function stringToArray(
        string $string,
        string $xmlEncoding = 'utf-8',
        string $xmlVersion = '1.0'
    ) : array {
        $doc = new DOMDocument($xmlVersion, $xmlEncoding);
        $doc->loadXML($string);

        $xml = new static($doc);
        return $xml->toArray();
    }

    public static function fileToArray(
        string $file,
        string $xmlEncoding = 'utf-8',
        string $xmlVersion = '1.0'
    ) : array {
        $doc = new DOMDocument($xmlVersion, $xmlEncoding);
        $doc->load($file);

        $xml = new static($doc);
        return $xml->toArray();
    }

    public function toArray() : array
    {
        $toArray = function ($root) use (&$toArray) {
            $result = [];

            $nodeTypes = [
                XML_TEXT_NODE,
                XML_CDATA_SECTION_NODE,
            ];

            if ($root instanceof DOMNode && $root->hasAttributes()) {
                foreach ($root->attributes as $attr) {
                    $result['@attributes'][$attr->name] = $attr->value;
                }
            }

            if ($root instanceof DOMNode && $root->hasChildNodes()) {
                $children = $root->childNodes;

                if ($children->length === 1) {
                    $child = $children->item(0);
                    if (in_array($child->nodeType, $nodeTypes, true)) {
                        $result['_value'] = $child->nodeValue;
                        return count($result) === 1
                            ? $result['_value']
                            : $result;
                    }
                }

                $groups = [];
                foreach ($children as $child) {
                    if (in_array($child->nodeType, $nodeTypes, true) && ! trim($child->nodeValue)) {
                        continue;
                    }

                    if (! isset($result[$child->nodeName])) {
                        $result[$child->nodeName] = $toArray($child);
                    } else {
                        if (! isset($groups[$child->nodeName])) {
                            $result[$child->nodeName] = [$result[$child->nodeName]];
                            $groups[$child->nodeName] = 1;
                        }
                        $result[$child->nodeName][] = $toArray($child);
                    }
                }
            }

            return $result ?: '';
        };

        return (array) $toArray($this->doc);
    }
}
