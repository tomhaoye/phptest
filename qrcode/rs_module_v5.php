<?php

function gfpMul($x, $y, $prim = 0x11d, $field_charac_full = 256, $carryless = true)
{
    //采用 Russian Peasant 算法实现GF域整数乘法 (主要使用位运算, 比上面的方法快).
    //当设定参数prim = 0 且 carryless=False 时, 返回普通整数乘法(进位乘法)计算结果.
    $r = 0;
    while ($y) {
        if ($y & 1)
            $r = $carryless ? ($r ^ $x) : ($r + $x);
        $y = $y >> 1;
        $x = $x << 1;
        if ($prim > 0 and $x & $field_charac_full)
            $x = $x ^ $prim;
    }
    return $r;
}

// Calculate alphas to simplify GF calculations.
$gfExp = [];
$gfLog = [];
$gfPrim = 0x11d;

$x = 1;

for ($i = 0; $i < 255; $i++) {
    $gfExp[$i] = $x;
    $gfLog[$x] = $i;
    $x = gfpMul($x, 2);
}

for ($i = 255; $i < 512; $i++) {
    $gfExp[$i] = $gfExp[$i - 255];
}

function gfPow($x, $pow)
{
    //GF power.
    global $gfExp, $gfLog;
    return $gfExp[($gfLog[$x] * $pow) % 255];
}

function gfMul($x, $y)
{
    //Simplified GF multiplication.
    global $gfExp, $gfLog;
    if ($x == 0 or $y == 0)
        return 0;
    return $gfExp[$gfLog[$x] + $gfLog[$y]];
}

function gfPolyMul($p, $q)
{
    //GF polynomial multiplication.
    $r = [];
    for ($i = 0; $i < count($q) + count($p) - 1; $i++) {
        $r[] = 0;
    }
    for ($j = 0; $j < count($q); $j++) {
        for ($i = 0; $i < count($p); $i++) {
            $r[$i + $j] ^= gfMul($p[$i], $q[$j]);
        }
    }
    return $r;
}

function rsGenPoly($nsym)
{
    //Generate generator polynomial for RS algorithm.
    $g = [1];
    for ($i = 0; $i < $nsym; $i++) {
        $g = gfPolyMul($g, [1, gfPow(2, $i)]);
    }
    return $g;
}

/**
 * 根据不同容错等级需要加入的EC块数量
 * version 1
 *
 * @param $bits_arr
 * @param $nsym
 * @return array
 */
function rsEncode($bits_arr, $nsym)
{
    //Encode bits_arr with nsym EC bits using RS algorithm.
    $gen = rsGenPoly($nsym);
    $ecnum = count($gen) - 1;
    $res = $bits_arr;
    while ($ecnum) {
        $res[] = 0;
        $ecnum--;
    }
    for ($i = 0; $i < count($bits_arr); $i++) {
        $coef = $res[$i];
        if ($coef != 0) {
            for ($j = 1; $j < count($gen); $j++)
                $res[$i + $j] ^= gfMul($gen[$j], $coef);
        }
    }
    foreach ($bits_arr as $key => $value) {
        $res[$key] = $value;
    }
    return $res;
}

/**
 * 格式部分由两位容错等级代码和三位QR掩码代码构成
 * L 01
 * M 00
 * Q 11
 * H 10
 * @param string $ec_code
 * @param $mask
 * @return array
 */
function rsMask($ec_code = '01', $mask)
{
    $fmt = fmtEncode(intval($ec_code . sprintf("%03b", $mask), 2));
    $fmt = sprintf("%015b", $fmt);
    $fmt_arr = [];
    $length = strlen($fmt);
    for ($i = 0; $i < $length; $i++) {
        $fmt_arr[] = $fmt[$i];
    }
    return $fmt_arr;
}

//格式信息也是要加容错码的
function fmtEncode($fmt)
{
    //Encode the 15-bit format code using BCH code
    $g = 0x537;
    $code = $fmt << 10;
    for ($i = 4; $i > -1; $i--) {
        if ($code & (1 << ($i + 10))) {
            $code ^= $g << $i;
        }
    }
    return (($fmt << 10) ^ $code) ^ 0b101010000010010;
}
