<?php
/**
 * | --------------------------------------------------------------------------
 * |
 * | This file is part of practice PROJECT
 * |
 * | Date: 16/11/1
 * |
 * | Copyright (C) 2015 Foshan Sami Network Technology Co.,Ltd.
 * | All rights reserved.
 * |
 * | Authors:
 * |       qiuyiwei
 * |
 * | This software, including documentation, is protected by copyright
 * | controlled by Foshan Sami Network Technology Co.,Ltd.
 * | All rights are reserved.
 * |
 * | --------------------------------------------------------------------------
 */
//print_r(strtotime('2016-10-10 10:11:10') - strtotime('2016-10-10 09:09:00') . PHP_EOL);
//
//$seq = str_pad(1299, 6, '0', STR_PAD_LEFT);
//echo substr($seq, 0, 3) . 12 . substr($seq, 3);
$t = microtime();
function untilSame()
{
    $arr[0] = substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), -8) . PHP_EOL;
}

var_dump(str_pad(1222, 6, '0', STR_PAD_LEFT));

print_r(microtime() - $t);

print_r(parse_url("http://qhj.xxx.com")['host']);

var_dump(json_decode(""));

echo substr(md5("11:20"), -8) . PHP_EOL;

echo date('Ymd H:i:s', filemtime('log'));

var_dump(is_numeric("122"));

var_dump(ord('A'));
var_dump(ord('B'));
var_dump(ord('V'));

$a['哈哈'] = 2;
$a['雨虹'] = 4;
$a['啊卡'] = 5;
$a['老黄'] = 6;
var_dump($a);

foreach ($a as $key => $value) {
    echo $key;
}


$packages = [];
$decode = json_decode('[{"id":"4","name":"马爹利名士 尊享品尝","count":2,"each_price":2880,"origin_price":0,"img":""},{"id":"5","name":"百威啤酒 畅享套餐","count":1,"each_price":0.1,"origin_price":0,"img":""}]');
foreach ($decode as $item) {
    if (isset($item->name)) {
        $package['name'] = $item->name;
        $package['count'] = $item->count;
        $package['price'] = $item->origin_price;
        $packages[] = $package;
    }
}

$mobile = ((json_decode('{"佛山":"13828487472","桂林":"13756565656"}')));
foreach ($mobile as $key => $item) {
    echo $key, $item;
}
print_r($mobile);

print_r(date('Y-m-d H:i:s', 1481682608));

print_r(strtotime('2016-10-10 10:10:00') - strtotime('2016-10-10 10:00:00'));


$aaa['nickname'] = 2;
print_r(array_intersect_key($aaa, [
    'nickname' => 1,
    'avatar' => 2,
    'self_introduction' => 3,
    'photograph' => 4
]));

function findImg($photograph)
{
    if (!is_array($photograph)) {
        $photograph = json_decode($photograph);
    }
    if (empty($photograph)) {
        return false;
    } else {
        foreach ($photograph as $item) {
            $parse = parse_url($item);
            if (isset($parse['path'])) {
                $ext = explode('.', $parse['path']);
                if (isset($ext[1])) {
                    if ($ext[1] != 'jpg' && $ext[1] != 'png') {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }
}

print_r(explode('.', parse_url('http://asdi90s.com2.z0.glb.qiniucdn.com/2017-01-11-15-06-50-c149d9ps89ky4.jpg')['path'])[1]);

print_r(date('Y-m-d His'));

print_r(substr('qrscene_123', 7));

//extension=>/usr/bin/php
echo ccvita_string(1);
echo PHP_EOL;

$a = (function () {
    return __FUNCTION__;
});
print_r($a());

echo strtotime('2016-05-05');

function ss()
{
    $a = PHP_EOL . 'ss';
    $func = function () use ($a) {
        echo $a;
    };
    $func();
}

ss();

class A
{
    public static function funcA()
    {
        static::funcB();
    }
}

class B extends A
{
    public static function funcB()
    {
        echo "B::funcB()";
    }
}

B::funcA();
