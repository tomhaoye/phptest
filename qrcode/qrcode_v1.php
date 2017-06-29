<?php
$times = 1;
$width = 21;
$height = 21;
$location_mod = 7;

$image = imagecreatetruecolor($width * $times, $height * $times);
$white = imagecolorallocate($image, 0xff, 0xff, 0xff);
$black = imagecolorallocate($image, 0x00, 0x00, 0x00);
imagefill($image, 0, 0, $white);
//左上角定位
imagefilledrectangle($image, 0, 0, $location_mod * $times - 1, $location_mod * $times - 1, $black);
for ($i = $times; $i > 0; $i--) {
    $diff = $times - $i;
    imagerectangle($image, $times + $diff, $times + $diff, ($location_mod - 1) * $times - 1 - $diff, ($location_mod - 1) * $times - 1 - $diff, $white);
}

//右上角定位
imagefilledrectangle($image, $width * $times - 1, 0, ($width - $location_mod) * $times, $location_mod * $times - 1, $black);
for ($i = $times; $i > 0; $i--) {
    $diff = $times - $i;
    imagerectangle($image, ($width - $location_mod) * $times + $times + $diff, $times + $diff, ($width - $location_mod) * $times + ($location_mod - 1) * $times - 1 - $diff, ($location_mod - 1) * $times - 1 - $diff, $white);
}

//左下角定位
imagefilledrectangle($image, 0, $width * $times - 1, $location_mod * $times - 1, ($width - $location_mod) * $times, $black);
for ($i = $times; $i > 0; $i--) {
    $diff = $times - $i;
    imagerectangle($image, $times + $diff, ($width - $location_mod) * $times + $times + $diff, ($location_mod - 1) * $times - 1 - $diff, ($width - $location_mod) * $times + ($location_mod - 1) * $times - 1 - $diff, $white);
}

//定时标示x
imagefilledrectangle($image, ($location_mod + 1) * $times, ($location_mod - 1) * $times, ($location_mod + 2) * $times - 1, $location_mod * $times - 1, $black);
imagefilledrectangle($image, ($location_mod + 1) * $times, ($location_mod + 6) * $times, ($location_mod + 2) * $times - 1, ($location_mod + 7) * $times - 1, $black);
imagefilledrectangle($image, ($location_mod + 3) * $times, ($location_mod - 1) * $times, ($location_mod + 4) * $times - 1, $location_mod * $times - 1, $black);
imagefilledrectangle($image, ($location_mod + 5) * $times, ($location_mod - 1) * $times, ($location_mod + 6) * $times - 1, $location_mod * $times - 1, $black);
//y
imagefilledrectangle($image, ($location_mod - 1) * $times, ($location_mod + 1) * $times, $location_mod * $times - 1, ($location_mod + 2) * $times - 1, $black);
imagefilledrectangle($image, ($location_mod - 1) * $times, ($location_mod + 3) * $times, $location_mod * $times - 1, ($location_mod + 4) * $times - 1, $black);
imagefilledrectangle($image, ($location_mod - 1) * $times, ($location_mod + 5) * $times, $location_mod * $times - 1, ($location_mod + 6) * $times - 1, $black);


//数据编码
$data = 'hello world!';
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
//填写编码数据ad hol
foreach ($return as $key => $item) {
    for ($p = 0; $p < 8; $p++) {
        $fill_arr[$key][$p] = $item[$p];
    }
}
upFillMode($image, 20, 20, $fill_arr[0], $white, $black);
upFillMode($image, 20, 16, $fill_arr[1], $white, $black);
upFillMode($image, 20, 12, $fill_arr[2], $white, $black);

downFillMode($image, 18, 9, $fill_arr[3], $white, $black);
downFillMode($image, 18, 13, $fill_arr[4], $white, $black);
downFillMode($image, 18, 17, $fill_arr[5], $white, $black);

upFillMode($image, 16, 20, $fill_arr[6], $white, $black);
upFillMode($image, 16, 16, $fill_arr[7], $white, $black);
upFillMode($image, 16, 12, $fill_arr[8], $white, $black);

downFillMode($image, 14, 9, $fill_arr[9], $white, $black);
downFillMode($image, 14, 13, $fill_arr[10], $white, $black);
downFillMode($image, 14, 17, $fill_arr[11], $white, $black);

upFillMode($image, 12, 20, $fill_arr[12], $white, $black);
upFillMode($image, 12, 16, $fill_arr[13], $white, $black);
upFillMode($image, 12, 12, $fill_arr[14], $white, $black);
upFillMode($image, 12, 8, $fill_arr[15], $white, $black, true);
upFillMode($image, 12, 3, $fill_arr[16], $white, $black);

downFillMode($image, 10, 0, $fill_arr[17], $white, $black);
downFillMode($image, 10, 4, $fill_arr[18], $white, $black, true);
downFillMode($image, 10, 9, $fill_arr[19], $white, $black);
downFillMode($image, 10, 13, $fill_arr[20], $white, $black);
downFillMode($image, 10, 17, $fill_arr[21], $white, $black);

upFillMode($image, 8, 12, $fill_arr[22], $white, $black);

downFillMode($image, 5, 9, $fill_arr[23], $white, $black);

upFillMode($image, 3, 12, $fill_arr[24], $white, $black);

downFillMode($image, 1, 9, $fill_arr[25], $white, $black);

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

function upFillMode($image, $start_x, $start_y, $fill_arr, $white, $black, $cross = false)
{
    for ($i = 0; $i < 8; $i++) {
        if ($i % 2 == 0)
            imagesetpixel($image, $start_x, $start_y - floor($i / 2), (!empty($fill_arr[$i]) ^ (($start_x + $start_y - floor($i / 2)) % 2 == 0)) ? $black : $white);
        else
            imagesetpixel($image, $start_x - 1, $start_y - floor($i / 2) - ($cross ? 1 : 0), (!empty($fill_arr[$i]) ^ (($start_x - 1 + $start_y - floor($i / 2) - ($cross ? 1 : 0)) % 2 == 0)) ? $black : $white);
    }
}

function downFillMode($image, $start_x, $start_y, $fill_arr, $white, $black, $cross = false)
{
    for ($i = 0; $i < 8; $i++) {
        if ($i % 2 == 0)
            imagesetpixel($image, $start_x, $start_y + floor($i / 2), (!empty($fill_arr[$i]) ^ (($start_x + $start_y + floor($i / 2)) % 2 == 0)) ? $black : $white);
        else
            imagesetpixel($image, $start_x - 1, $start_y + floor($i / 2) + ($cross ? 1 : 0), (!empty($fill_arr[$i]) ^ (($start_x - 1 + $start_y + floor($i / 2) + ($cross ? 1 : 0)) % 2 == 0)) ? $black : $white);
    }
}

//获取格式码
$mask = '000';
$fmt_code = exec('python rs_mask.py ' . $mask);
$fmt_code = json_decode($fmt_code);
$fmt_code = array_reverse($fmt_code);

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

fillFmt($image, $fmt_code, $white, $black);

//生成图像和回收资源
imagepng($image, 'v1_png.png');

$image_big = imagecreatetruecolor(210, 210);
imagecopyresized($image_big, $image, 0, 0, 0, 0, 210, 210, 21, 21);
imagepng($image_big, 'v1_big.png');

imagedestroy($image);
imagedestroy($image_big);

