<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use DOMException;

use function var_dump;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    XmlToArray::stringToArray('<foo>');
} catch (DOMException $e) {
    var_dump($e->getMessage());
}
