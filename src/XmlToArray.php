<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use DOMDocument;
use DOMException;
use DOMNode;
use LibXMLError;

use function count;
use function in_array;
use function libxml_use_internal_errors;
use function libxml_clear_errors;
use function libxml_get_last_error;
use function sprintf;
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

    /**
     * @throws DOMException if DOM load failed
     */
    public static function stringToArray(
        string $string,
        string $xmlEncoding = 'utf-8',
        string $xmlVersion = '1.0'
    ) : array {
        $previousInternalErrors = libxml_use_internal_errors(true);

        $doc = new DOMDocument($xmlVersion, $xmlEncoding);
        $result = $doc->loadXML($string);

        if ($result === false) {
            self::throwException($previousInternalErrors);
        }

        libxml_use_internal_errors($previousInternalErrors);

        $xml = new static($doc);
        return $xml->toArray();
    }

    /**
     * @throws DOMException if DOM load failed
     */
    public static function fileToArray(
        string $file,
        string $xmlEncoding = 'utf-8',
        string $xmlVersion = '1.0'
    ) : array {
        $previousInternalErrors = libxml_use_internal_errors(true);

        $doc = new DOMDocument($xmlVersion, $xmlEncoding);
        $result = $doc->load($file);

        if ($result === false) {
            self::throwException($previousInternalErrors);
        }

        libxml_use_internal_errors($previousInternalErrors);

        $xml = new static($doc);
        return $xml->toArray();
    }

    /**
     * @throws DOMException
     */
    private static function throwException(bool $previousInternalErrors) : void
    {
        $toErrorMessage = function (LibXMLError $error) : string {
            $format = '%s on line: %d, column: %d';
            return sprintf($format, trim($error->message), $error->line, $error->column);
        };

        $libXmlError = libxml_get_last_error();

        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        throw new DOMException($toErrorMessage($libXmlError), $libXmlError->code);
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
