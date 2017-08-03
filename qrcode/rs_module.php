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
 * @param $bitstring
 * @param $nsym
 * @return array
 */
function rsEncode($bitstring, $nsym)
{
    //Encode bitstring with nsym EC bits using RS algorithm.
    $gen = rsGenPoly($nsym);
    $ecnum = count($gen) - 1;
    $res = $bitstring;
    while ($ecnum) {
        $res[] = 0;
        $ecnum--;
    }
    for ($i = 0; $i < count($bitstring); $i++) {
        $coef = $res[$i];
        if ($coef != 0) {
            for ($j = 1; $j < count($gen); $j++)
                $res[$i + $j] ^= gfMul($gen[$j], $coef);
        }
    }
    foreach ($bitstring as $key => $value) {
        $res[$key] = $value;
    }
    return $res;
}
