<?php

function gen() {
    echo "Foo\n";
    try {
        yield;
    } catch (Exception $e) {
        echo "Exception: {$e->getMessage()}\n";
    }
    echo "Bar\n";
}

$gen = gen();
$gen->rewind();                     // echos "Foo"
$gen->throw(new Exception('Test')); // echos "Exception: Test"
// and "Bar"
