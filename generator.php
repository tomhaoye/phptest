<?php

function my_range($start, $range)
{
    for ($i = $start; $i < $range; $i++) {
        yield $i;
    }
}

function get()
{
    foreach (my_range(1, 100) as $item) {
        echo $item . PHP_EOL;
    }
}

get();
