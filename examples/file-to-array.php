<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use function var_dump;

require_once __DIR__ . '/../vendor/autoload.php';

$xml = __DIR__ . '/res.xml';
$result = XmlToArray::fileToArray($xml);
var_dump($result);
