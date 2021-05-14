<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNodeList;
use DOMText;
use LibXMLError;

use function array_unique;
use function count;
use function libxml_use_internal_errors;
use function libxml_clear_errors;
use function libxml_get_last_error;
use function sprintf;
use function trim;

class XmlToArray
{
    /**
     * @var DOMDocument
     */
    private $doc;

    private const KEY_ATTRIBUTES = '@attributes';

    private const KEY_VALUE = '@value';

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
    ): array {
        $previousInternalErrors = libxml_use_internal_errors(true);

        try {
            $doc = new DOMDocument($xmlVersion, $xmlEncoding);
            $result = $doc->loadXML($string);

            if ($result === false) {
                self::throwException();
            }
        } finally {
            libxml_use_internal_errors($previousInternalErrors);
        }

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
    ): array {
        $previousInternalErrors = libxml_use_internal_errors(true);

        try {
            $doc = new DOMDocument($xmlVersion, $xmlEncoding);
            $result = $doc->load($file);

            if ($result === false) {
                self::throwException();
            }
        } finally {
            libxml_use_internal_errors($previousInternalErrors);
        }

        $xml = new static($doc);
        return $xml->toArray();
    }

    /**
     * @throws DOMException
     */
    private static function throwException(): void
    {
        $toErrorMessage = function (LibXMLError $error): string {
            $format = '%s on line: %d, column: %d';
            return sprintf($format, trim($error->message), $error->line, $error->column);
        };

        $libXmlError = libxml_get_last_error();
        libxml_clear_errors();

        throw new DOMException($toErrorMessage($libXmlError), $libXmlError->code);
    }

    private function isArrayElement(DOMNodeList $childNodes): bool
    {
        $names = [];
        foreach ($childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $names[] = $childNode->nodeName;
            }
        }

        return count($names) > 1 && count(array_unique($names)) === 1;
    }

    private function convertDomAttributes(DOMElement $element): array
    {
        if ($element->hasAttributes()) {
            $attributes = [];
            foreach ($element->attributes as $attr) {
                $attributes[$attr->name] = $attr->value;
            }

            return [
                self::KEY_ATTRIBUTES => $attributes,
            ];
        }

        return [];
    }

    private function convertDomElement(DOMElement $element)
    {
        $result = $this->convertDomAttributes($element);

        $isGroup = $this->isArrayElement($element->childNodes);

        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMCdataSection) {
                $result[self::KEY_VALUE] = $childNode->data;
                continue;
            }

            if ($childNode instanceof DOMText) {
                $result[self::KEY_VALUE] = $childNode->textContent;
                continue;
            }

            if ($childNode instanceof DOMElement) {
                if ($isGroup) {
                    $result[$childNode->nodeName][] = $this->convertDomElement($childNode);
                    continue;
                }

                $result[$childNode->nodeName] = $this->convertDomElement($childNode);
            }
        }

        if (isset($result[self::KEY_VALUE]) && trim($result[self::KEY_VALUE]) !== '') {
            return $result[self::KEY_VALUE];
        }

        unset($result[self::KEY_VALUE]);

        return count($result) > 0 ? $result : '';
    }

    public function toArray(): array
    {
        return [
            $this->doc->documentElement->nodeName => $this->convertDomElement($this->doc->documentElement),
        ];
    }
}
