<?php
require 'Scheduler.php';
require 'Task.php';
require 'SystemCall.php';

function getTaskId()
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function newTask(Generator $coroutine)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}

function killTask($tid)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($tid) {
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        }
    );
}


function task($max)
{
    $tid = (yield getTaskId()); // <-- here's the syscall!
    for ($i = 1; $i <= $max; ++$i) {
        echo "This is task $tid iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->newTask(task(10));
$scheduler->newTask(task(5));

$scheduler->run();
