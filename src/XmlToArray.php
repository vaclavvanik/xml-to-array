<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMText;
use LibXMLError;

use function count;
use function libxml_clear_errors;
use function libxml_get_last_error;
use function libxml_use_internal_errors;
use function sprintf;
use function trim;

class XmlToArray
{
    /** @var DOMDocument */
    private $doc;

    private const KEY_ATTRIBUTES = '@attributes';

    private const KEY_VALUE = '@value';

    public function __construct(DOMDocument $doc)
    {
        $this->doc = $doc;
    }

    /** @throws DOMException */
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

    /** @throws DOMException */
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

    public function toArray(): array
    {
        return [
            $this->doc->documentElement->nodeName => $this->convertDomElement($this->doc->documentElement),
        ];
    }

    /** @throws DOMException */
    private static function throwException(): void
    {
        $toErrorMessage = static function (LibXMLError $error): string {
            $format = '%s on line: %d, column: %d';

            return sprintf($format, trim($error->message), $error->line, $error->column);
        };

        $libXmlError = libxml_get_last_error();
        libxml_clear_errors();

        throw new DOMException($toErrorMessage($libXmlError), $libXmlError->code);
    }

    private function convertDomAttributes(DOMElement $element): array
    {
        if ($element->hasAttributes()) {
            $attributes = [];

            foreach ($element->attributes as $attr) {
                $attributes[$attr->name] = $attr->value;
            }

            return [self::KEY_ATTRIBUTES => $attributes];
        }

        return [];
    }

    /**
     * @return array<string,int>
     */
    private function childNamesCount(DOMElement $element): array
    {
        $names = [];

        foreach ($element->childNodes as $childNode) {
            if (! ($childNode instanceof DOMElement)) {
                continue;
            }

            if (! isset($names[$childNode->nodeName])) {
                $names[$childNode->nodeName] = 0;
            }

            ++$names[$childNode->nodeName];
        }

        return $names;
    }

    /** @return array|string */
    private function convertDomElement(DOMElement $element)
    {
        $result = $this->convertDomAttributes($element);

        $childNames = $this->childNamesCount($element);

        $isArrayElement = static function (string $name) use ($childNames) : bool {
            return $childNames[$name] > 1;
        };

        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMCdataSection) {
                $result[self::KEY_VALUE] = $childNode->data;
                continue;
            }

            if ($childNode instanceof DOMText) {
                $result[self::KEY_VALUE] = $childNode->textContent;
                continue;
            }

            if (! ($childNode instanceof DOMElement)) {
                continue;
            }

            if ($isArrayElement($childNode->nodeName)) {
                if (! isset($result[$childNode->nodeName])) {
                    $result[$childNode->nodeName] = [];
                }

                $result[$childNode->nodeName][] = $this->convertDomElement($childNode);
                continue;
            }

            $result[$childNode->nodeName] = $this->convertDomElement($childNode);
        }

        if (isset($result[self::KEY_VALUE]) && trim($result[self::KEY_VALUE]) !== '') {
            return $result[self::KEY_VALUE];
        }

        unset($result[self::KEY_VALUE]);

        return count($result) > 0 ? $result : '';
    }
}
