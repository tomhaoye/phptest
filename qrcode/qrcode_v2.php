<?php

function getStringInput()
{
    echo 'input a string which length less than or equals of 17: ';
    $input = fopen("php://stdin", 'r');
    $s = fgets($input);
    while (strlen($s) > 17) {
        echo 'length less than or equals of 17:';
        $s = fgets($input);
    }
    fclose($input);
    return $s;
}

function drawQRCode($mask = 1)
{
    $width = 21;
    $height = 21;
    $location_mod = 7;

    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 0xff, 0xff, 0xff);
    $black = imagecolorallocate($image, 0x00, 0x00, 0x00);
    imagefill($image, 0, 0, $white);

    buildLocateSign($image, $width, $location_mod, $black, $white);
    buildTimeSign($image, $location_mod, $black);
    encodeString($image, $white, $black, $mask);

    //掩码ID
    $mask_code_arr = [
        1 => '000',
        2 => '001',
        3 => '010',
        4 => '011',
        5 => '100',
        6 => '101',
        7 => '110',
        8 => '111',
    ];
    $fmt_code = exec('python rs_mask.py ' . $mask_code_arr[$mask]);
    $fmt_code = json_decode($fmt_code);
    $fmt_code = array_reverse($fmt_code);
    fillFmt($image, $fmt_code, $white, $black);

    exportPng($image, $mask);
}

function buildLocateSign($image, $width, $location_mod, $black, $white)
{
//左上角定位
    imagefilledrectangle($image, 0, 0, $location_mod - 1, $location_mod - 1, $black);
    imagerectangle($image, 1, 1, ($location_mod - 1) - 1, ($location_mod - 1) - 1, $white);

//右上角定位
    imagefilledrectangle($image, $width - 1, 0, ($width - $location_mod), $location_mod - 1, $black);
    imagerectangle($image, ($width - $location_mod) + 1, 1, ($width - $location_mod) + ($location_mod - 1) - 1, ($location_mod - 1) - 1, $white);

//左下角定位
    imagefilledrectangle($image, 0, $width - 1, $location_mod - 1, ($width - $location_mod), $black);
    imagerectangle($image, 1, ($width - $location_mod) + 1, ($location_mod - 1) - 1, ($width - $location_mod) + ($location_mod - 1) - 1, $white);
}

function buildTimeSign($image, $location_mod, $black)
{
//定时标示x
    imagesetpixel($image, ($location_mod + 1), ($location_mod - 1), $black);
    imagesetpixel($image, ($location_mod + 1), ($location_mod + 6), $black);
    imagesetpixel($image, ($location_mod + 3), ($location_mod - 1), $black);
    imagesetpixel($image, ($location_mod + 5), ($location_mod - 1), $black);
//y
    imagesetpixel($image, ($location_mod - 1), ($location_mod + 1), $black);
    imagesetpixel($image, ($location_mod - 1), ($location_mod + 3), $black);
    imagesetpixel($image, ($location_mod - 1), ($location_mod + 5), $black);
}

function encodeString($image, $white, $black, $mask = 1)
{
    $data = 'hello mask!';
    $bit_string = '0100';
    $len = strlen($data);
    $bit_string .= sprintf("%08b", $len);
    for ($k = 0; $k < $len; $k++) {
        $bit_string .= sprintf("%08b", ord(iconv("utf-8", "iso-8859-1", $data[$k])));
    }
    $bit_string .= '0000';
    $res = [];
    for ($n = 0; $n < strlen($bit_string); $n += 8) {
        $res[] = intval(substr($bit_string, $n, 8), 2);
    }
    while (count($res) < 19) {
        $res[] = intval('11101100', 2);
        $res[] = intval('00010001', 2);
    }
    unset($res[19]);
    $res = implode(',', $res);
    $return = exec('python rs.py ' . $res);
    $return = json_decode($return);
    foreach ($return as &$item) {
        $item = sprintf("%08b", $item);
    }
//填写编码数据ad hoc
    foreach ($return as $key => $item) {
        for ($p = 0; $p < 8; $p++) {
            $fill_arr[$key][$p] = $item[$p];
        }
    }

    $i = 0;
    for ($i; $i < 3; $i++)
        fillData($mask, $image, 20, 20 - 4 * $i, $fill_arr[$i], $white, $black, false);

    for ($i; $i < 6; $i++)
        fillData($mask, $image, 18, 9 + 4 * ($i - 3), $fill_arr[$i], $white, $black, false, false);

    for ($i; $i < 9; $i++)
        fillData($mask, $image, 16, 20 - 4 * ($i - 6), $fill_arr[$i], $white, $black, false);

    for ($i; $i < 12; $i++)
        fillData($mask, $image, 14, 9 + 4 * ($i - 9), $fill_arr[$i], $white, $black, false, false);

    for ($i; $i < 17; $i++)
        fillData($mask, $image, 12, 20 - 4 * ($i - 12) - ($i > 15 ? 1 : 0), $fill_arr[$i], $white, $black, $i == 15 ? true : false);

    for ($i; $i < 22; $i++)
        fillData($mask, $image, 10, 0 + 4 * ($i - 17) + ($i > 18 ? 1 : 0), $fill_arr[$i], $white, $black, $i == 18 ? true : false, false);

    fillData($mask, $image, 8, 12, $fill_arr[22], $white, $black, false);

    fillData($mask, $image, 5, 9, $fill_arr[23], $white, $black, false, false);

    fillData($mask, $image, 3, 12, $fill_arr[24], $white, $black, false);

    fillData($mask, $image, 1, 9, $fill_arr[25], $white, $black, false, false);

}

function fillData($mask_id, $image, $start_x, $start_y, $fill_arr, $white, $black, $cross = false, $up = true)
{
    for ($i = 0; $i < 8; $i++) {
        if ($i % 2 == 0)
            imagesetpixel($image, $start_x, $start_y - ($up ? 1 : -1) * floor($i / 2), (!empty($fill_arr[$i]) ^ mask($start_x, $start_y - ($up ? 1 : -1) * floor($i / 2), $mask_id)) ? $black : $white);
        else
            imagesetpixel($image, $start_x - 1, $start_y - ($up ? 1 : -1) * (floor($i / 2) + ($cross ? 1 : 0)), (!empty($fill_arr[$i]) ^ mask($start_x - 1, $start_y - ($up ? 1 : -1) * (floor($i / 2) + ($cross ? 1 : 0)), $mask_id)) ? $black : $white);
    }
}

//八种掩码
/*
 * dark if (row + column) mod 2 == 0
 * dark if (row) mod 2 == 0
 * dark if (column) mod 3 == 0
 * dark if (row + column) mod 3 == 0
 * dark if ( floor(row / 2) + floor(column / 3) ) mod 2 == 0
 * dark if ((row * column) mod 2) + ((row * column) mod 3) == 0
 * dark if ( ((row * column) mod 2) + ((row * column) mod 3) ) mod 2 == 0
 * dark if ( ((row + column) mod 2) + ((row * column) mod 3) ) mod 2 == 0
*/
function mask($column, $row, $type)
{
    switch ($type) {
        case 1:
            return ($row + $column) % 2 == 0;
            break;
        case 2:
            return $row % 2 == 0;
            break;
        case 3:
            return $column % 3 == 0;
            break;
        case 4:
            return ($row + $column) % 3 == 0;
            break;
        case 5:
            return (floor($row / 2) + floor($column / 3)) % 2 == 0;
            break;
        case 6:
            return (($row * $column) % 2) + (($row * $column) % 3) == 0;
            break;
        case 7:
            return ((($row * $column) % 2) + (($row * $column) % 3)) % 2 == 0;
            break;
        case 8:
            return ((($row + $column) % 2) + (($row * $column) % 3)) % 2 == 0;
            break;
        default:
            return ($row + $column) % 2 == 0;
            break;
    }
}

/*
 * todo 惩罚计算
 */


function fillFmt($image, $arr, $white, $black)
{
    for ($i = 0; $i <= 5; $i++)
        imagefilledrectangle($image, 8, $i, 8, $i, $arr[$i] ? $black : $white);

    for ($i = 7; $i <= 8; $i++)
        imagefilledrectangle($image, 8, $i, 8, $i, $arr[$i - 1] ? $black : $white);

    for ($i = 14; $i <= 20; $i++)
        imagefilledrectangle($image, 8, $i, 8, $i, $arr[$i - 6] ? $black : $white);

    for ($k = 0, $i = 20; $i >= 13; $i--, $k++)
        imagefilledrectangle($image, $i, 8, $i, 8, $arr[$k] ? $black : $white);

    for ($k = 7, $i = 8; $i >= 7; $i--, $k++)
        imagefilledrectangle($image, $i, 8, $i, 8, $arr[$k] ? $black : $white);

    for ($k = 9, $i = 5; $i >= 0; $i--, $k++)
        imagefilledrectangle($image, $i, 8, $i, 8, $arr[$k] ? $black : $white);

}

function exportPng($image, $mask_id)
{
    imagepng($image, $mask_id . 'v2_png.png');
    imagedestroy($image);
}


drawQRCode(1);
drawQRCode(2);
drawQRCode(3);
drawQRCode(4);
drawQRCode(5);
drawQRCode(6);
drawQRCode(7);
drawQRCode(8);

