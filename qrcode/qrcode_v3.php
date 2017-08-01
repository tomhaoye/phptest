<?php
include 'rs_module.php';

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
    $fmt_code = rsMask('01', intval($mask_code_arr[$mask], 2));
    $fmt_code = array_reverse($fmt_code);
    fillFmt($image, $fmt_code, $white, $black);

    exportPng($image, $mask);
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

//定位标示
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

//定时标示
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

/**
 * 真正的数据编码
 *
 * 编码模式:
 * 1\数字（Numeric）：0-9
 * 2\大写字母和数字（alphanumeric）：0-9，A-Z，空格，$，%，*，+，-，.，/，:
 * 3\二进制/字节：通过 ISO/IEC 8859-1 标准编码
 * 4\日本汉字/假名：通过 Shift JISJIS X 0208 标准编码
 *
 * byte mode的前缀是 0100，接上八位二进制数代表的数据长度，构成数据前缀。
 * 再把数据用 ISO/IEC 8859-1 标准编码，
 * 按八个二进制位分组，接上终止符和11101100和00010001交替的填充字节，按标准修剪到19字节，完成数据编码
 *
 * @param $image
 * @param $white
 * @param $black
 * @param int $mask
 */
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
    //加入容错编码 L 7
    $return = rsEncode($res, 7);
    foreach ($return as &$item) {
        $item = sprintf("%08b", $item);
    }
    //填写编码数据ad hoc
    foreach ($return as $key => $item) {
        for ($p = 0; $p < 8; $p++) {
            $fill_arr[$key][$p] = $item[$p];
        }
    }

    $template = template($fill_arr);
    newFillData($image, $template, $mask, $white, $black);

}

/**
 * todo v4 全模板
 *
 * 构造模版(数据编码部分)
 * v1 qr-code 21*21 matrix
 * @param $fill_arr
 * @return array $template
 */
function template($fill_arr)
{
    $template = [];
    $x = 20;
    $y = 20;
    $cell = 8;
    $bottom = 20;
    $right_mid = $x - 7;
    $left_mid = 8;
    $y_operate = 1;

    for ($i = 0; $i < count($fill_arr); $i++) {
        echo $x . ' ';

        for ($j = 0; $j < 8; $j++) {
            //y=6定时标示
            if ($y == 6) $y -= $y_operate;

            if ($j % 2 == 0) {
                $template[$x][$y] = $fill_arr[$i][$j];
            } else {
                $template[$x - 1][$y] = $fill_arr[$i][$j];
                $y -= $y_operate;
            }
        }
        //中间部分
        if ($x < $right_mid && $x > $left_mid) {
            $cell = 0;
        }
        //y到顶或底 反响运算 x自减
        if ($y <= $cell || $y >= $bottom) {
            $y += $y_operate;
            $y_operate = -$y_operate;
            $x -= 2;
        } elseif ($x <= $left_mid && $y_operate > 0) {
            $y = 12;
            $x -= 2;
        } elseif ($x <= $left_mid && $y_operate < 0) {
            $y = 9;
            $x -= 2;
        }
        //x=6定时标示
        if ($x == 6) $x -= 1;
    }
    return $template;
}

function newFillData($image, $template, $mask_id = 1, $white, $black)
{
    for ($i = 0; $i < 21; $i++) {
        for ($j = 0; $j < 21; $j++) {
            if (isset($template[$i][$j])) {
                imagesetpixel($image, $i, $j, (!empty($template[$i][$j]) ^ mask($i, $j, $mask_id)) ? $black : $white);
            }
        }
    }
}

/**
 * 八种掩码
 * dark if (row + column) mod 2 == 0
 * dark if (row) mod 2 == 0
 * dark if (column) mod 3 == 0
 * dark if (row + column) mod 3 == 0
 * dark if ( floor(row / 2) + floor(column / 3) ) mod 2 == 0
 * dark if ((row * column) mod 2) + ((row * column) mod 3) == 0
 * dark if ( ((row * column) mod 2) + ((row * column) mod 3) ) mod 2 == 0
 * dark if ( ((row + column) mod 2) + ((row * column) mod 3) ) mod 2 == 0
 *
 * @param $column
 * @param $row
 * @param $type
 * @return bool
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
function penalty()
{

}

//格式标示
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
    imagepng($image, $mask_id . 'v3_png.png');
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
