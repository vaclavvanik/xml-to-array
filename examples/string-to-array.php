<?php

declare(strict_types=1);

namespace VaclavVanik\XmlToArray;

use function file_get_contents;
use function var_dump;

require_once __DIR__ . '/../vendor/autoload.php';

$xml = file_get_contents(__DIR__ . '/res.xml');
$result = XmlToArray::stringToArray($xml);
var_dump($result);
