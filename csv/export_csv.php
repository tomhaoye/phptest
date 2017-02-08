<?php

function export_csv()
{
    $data_fl = array('name', 'mobile');
    $data_lines = array('qiu', '188888');
    $fisrt = implode(',', $data_fl);
    $line = implode(',', $data_lines);
    $res = fopen('a.csv', 'w+');
    fwrite($res, $fisrt . "\r" . $line);
    fclose($res);
}

export_csv();