<?php
require 'A.php';

$a = new A();

$re = new ReflectionClass('A');

$properties = $re->getProperties();

foreach ($properties as $property) {
    $a->{$property->getName()} = 2;
}

print_r($a);
