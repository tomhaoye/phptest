<?php
require 'Scheduler.php';
require 'Task.php';

function echoTimes($msg, $max)
{
    for ($i = 1; $i <= $max; ++$i) {
        echo "$msg iteration $i\n";
        yield;
    }
}

function task()
{
    echoTimes('foo', 10); // print foo ten times
    echo "---\n";
    echoTimes('bar', 5); // print bar five times
    yield; // force it to be a coroutine
}

$scheduler = new Scheduler;
$scheduler->newTask(task());
$scheduler->run();
