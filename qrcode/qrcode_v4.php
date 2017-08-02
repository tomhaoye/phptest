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
    drawQRCode($s);
}

/**
 * @param $data
 */
function drawQRCode($data)
{
    $width = 21;
    $height = 21;

    $image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($image, 0xff, 0xff, 0xff);
    $black = imagecolorallocate($image, 0x00, 0x00, 0x00);
    imagefill($image, 0, 0, $white);

    $mask_id = encodeString($data, $image, $white, $black);

    exportPng($image, $mask_id);
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

/**
 * 定位标示模板
 * @param $mask_template
 */
function buildLocateSign(&$mask_template)
{
    for ($i = 0; $i < 7; $i++) {
        //竖
        $mask_template[0][$i] = 1;
        $mask_template[6][$i] = 1;
        $mask_template[0][$i + 14] = 1;
        $mask_template[6][$i + 14] = 1;
        $mask_template[14][$i] = 1;
        $mask_template[20][$i] = 1;

        //横
        $mask_template[$i][0] = 1;
        $mask_template[$i][6] = 1;
        $mask_template[$i][14] = 1;
        $mask_template[$i][20] = 1;
        $mask_template[$i + 14][0] = 1;
        $mask_template[$i + 14][6] = 1;
    }

    //中间
    for ($j = 2; $j < 5; $j++) {
        for ($k = 2; $k < 5; $k++) {
            $mask_template[$j][$k] = 1;
            $mask_template[$j + 14][$k] = 1;
            $mask_template[$j][$k + 14] = 1;
        }
    }
}

/**
 * 定时标示模板
 * @param $mask_template
 */
function buildTimeSign(&$mask_template)
{
//定时标示x
    $mask_template[8][6] = 1;
    $mask_template[10][6] = 1;
    $mask_template[12][6] = 1;
    $mask_template[8][13] = 1;
//y
    $mask_template[6][8] = 1;
    $mask_template[6][10] = 1;
    $mask_template[6][12] = 1;
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
 * @param $data
 * @param $image
 * @param $white
 * @param $black
 * @return int $mask_id
 */
function encodeString($data, $image, $white, $black)
{
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
        if (count($res) == 18) {
            $res[] = intval('11101100', 2);
        } else {
            $res[] = intval('11101100', 2);
            $res[] = intval('00010001', 2);
        }
    }
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
    //數據部分編碼模板
    $template = template($fill_arr);
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

    //寻找最低惩罚依此mask生成二维码
    $penalty = [];
    $mask_template = [];
    foreach ($mask_code_arr as $mask_id => $mask) {
        $mask_template[$mask_id] = maskTemplate($template, $mask_id);

        //将固定部分和格式信息加入模板再计算惩罚
        //固定部分
        buildLocateSign($mask_template[$mask_id]);
        buildTimeSign($mask_template[$mask_id]);
        //格式信息
        $fmt_code = rsMask('01', intval($mask, 2));
        $fmt_code = array_reverse($fmt_code);
        fillFmt($mask_template[$mask_id], $fmt_code);
        //剩余空白hash填充
        for ($i = 0; $i < 21; $i++) {
            for ($j = 0; $j < 21; $j++) {
                if (!isset($mask_template[$mask_id][$i][$j])) {
                    $mask_template[$mask_id][$i][$j] = 0;
                }
            }
            ksort($mask_template[$mask_id][$i]);
        }

        $penalty[$mask_id] = penalty($mask_template[$mask_id]);
    }
    print_r($penalty);

    $mask_id = array_search(min($penalty), $penalty);
    $mask_template = $mask_template[$mask_id];

    fillMaskData($image, $mask_template, $white, $black);

    return $mask_id;
}

/**
 * version1 qr code ad hoc
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

        if ($x == $left_mid && $y_operate > 0) $y = 12;
        elseif ($x == $left_mid && $y_operate < 0) $y = 9;

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

        if ($x <= $left_mid && $y_operate > 0) {
            $y = 12;
            $x -= 2;
        } elseif ($x <= $left_mid && $y_operate < 0) {
            $y = 9;
            $x -= 2;
        } else {
            //y到顶或底 反响运算 x自减
            if ($y <= $cell || $y >= $bottom) {
                $y += $y_operate;
                $y_operate = -$y_operate;
                $x -= 2;
            }
        }
        //x=6定时标示
        if ($x == 6) $x -= 1;
    }
    return $template;
}

/**
 * 计算掩码后的模板
 * @param $template
 * @param $mask_id
 * @return array
 */
function maskTemplate($template, $mask_id)
{
    $mask_template = [];
    for ($i = 0; $i < 21; $i++) {
        for ($j = 0; $j < 21; $j++) {
            if (isset($template[$i][$j])) {
                $mask_template[$i][$j] = !empty($template[$i][$j]) ^ mask($i, $j, $mask_id);
            }
        }
    }
    return $mask_template;
}

/**
 * 填寫編碼部分數據（使用被掩码编码的模板）
 * @param $image
 * @param $mask_template
 * @param $white
 * @param $black
 */
function fillMaskData($image, $mask_template, $white, $black)
{
    for ($i = 0; $i < 21; $i++) {
        for ($j = 0; $j < 21; $j++) {
            if (isset($mask_template[$i][$j])) {
                imagesetpixel($image, $i, $j, (!empty($mask_template[$i][$j])) ? $black : $white);
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

/**
 * 懲罰計算
 * Calculate penalty score for a masked matrix.
 * N1: penalty for more than 5 consecutive pixels in row/column,
 * 3 points for each occurrence of such pattern,
 * and extra 1 point for each pixel exceeding 5
 * consecutive pixels.
 * N2: penalty for blocks of pixels larger than 2x2.
 * 3*(m-1)*(n-1) points for each block of mxn
 * (larger than 2x2).
 * N3: penalty for patterns similar to the finder pattern.
 * 40 points for each occurrence of 1:1:3:1:1 ratio
 * (dark:light:dark:light:dark) pattern in row/column,
 * preceded of followed by 4 consecutive light pixels.
 * N4: penalty for unbalanced dark/light ratio.
 * 10*k points where k is the rating of the deviation of
 * the proportion of dark pixels from 50% in steps of 5%.
 *
 * @param $matrix
 * @return int
 */
function penalty($matrix)
{
    $n1 = 0;
    $n2 = 0;
    $n3 = 0;
    $n4 = 0;
    //N1
    for ($j = 0; $j < count($matrix); $j++) {
        $count = 1;
        $adj = false;
        for ($i = 1; $i < count($matrix); $i++) {
            if (isset($matrix[$j][$i - 1]) and isset($matrix[$j][$i]) and $matrix[$j][$i] == $matrix[$j][$i - 1]) {
                $count += 1;
            } else {
                $count = 1;
                $adj = false;
            }
            if ($count >= 5) {
                if (!$adj) {
                    $adj = true;
                    $n1 += 3;
                } else {
                    $n1 += 1;
                }
            }
        }
    }
    for ($i = 0; $i < count($matrix); $i++) {
        $count = 1;
        $adj = false;
        for ($j = 1; $j < count($matrix); $j++) {
            if ($matrix[$j][$i] == $matrix[$j - 1][$i]) {
                $count += 1;
            } else {
                $count = 1;
                $adj = false;
            }
            if ($count >= 5) {
                if (!$adj) {
                    $adj = true;
                    $n1 += 3;
                } else {
                    $n1 += 1;
                }
            }
        }
    }

    //N2
    $m = 1;
    $n = 1;
    for ($j = 1; $j < count($matrix); $j++) {
        for ($i = 1; $i < count($matrix); $i++) {
            if ($matrix[$j][$i] == $matrix[$j - 1][$i] and $matrix[$j][$i] == $matrix[$j][$i - 1] and $matrix[$j][$i] == $matrix[$j - 1][$i - 1]) {
                $m += 1;
                $n += 1;
            } else {
                $n2 += 3 * ($m - 1) * ($n - 1);
                $m = 1;
                $n = 1;
            }
        }
    }

    //N3一个方向寻找
    $count = 0;
    foreach ($matrix as $row) {
        $row_str = implode('', $row);
        $begin = 0;
        while ($begin < strlen($row_str) and strpos('00001011101', $row_str, $begin) !== false) {
            $begin = strpos('00001011101', $row_str, $begin) + 11;
            $count += 1;
        }
        $begin = 0;
        while ($begin < strlen($row_str) and strpos('10111010000', $row_str, $begin) !== false) {
            $begin = strpos('10111010000', $row_str, $begin) + 11;
            $count += 1;
        }
    }
    //另一个方向 矩阵转置
    $transpose_matrix = [];
    foreach ($matrix as $row) {
        foreach ($row as $key => $value) {
            $transpose_matrix[$key][] = $value;
        }
    }
    foreach ($transpose_matrix as $row) {
        $row_str = implode('', $row);
        $begin = 0;
        while ($begin < strlen($row_str) and strpos('00001011101', $row_str, $begin) !== false) {
            $begin = strpos('00001011101', $row_str, $begin) + 11;
            $count += 1;
        }
        $begin = 0;
        while ($begin < strlen($row_str) and strpos('10111010000', $row_str, $begin) !== false) {
            $begin = strpos('10111010000', $row_str, $begin) + 11;
            $count += 1;
        }
    }
    $n3 += $count * 40;

    //N4
    $dark = getSum($matrix);
    $percent = intval((floatval($dark)) / floatval(pow(count($matrix), 2)) * 100);
    $pre = $percent - $percent % 5;
    $nex = $percent + 5 - $percent % 5;
    $n4 = min(abs($pre - 50) / 5, abs($nex - 50) / 5) * 10;

    print_r([$n1, $n2, $n3, $n4]);
    return $n1 + $n2 + $n3 + $n4;
}

function getSum($array)
{
    $num = 0;
    foreach ($array as $k => $v) {
        if (is_array($v)) {
            $num += getSum($v);
        }
    }
    return $num + array_sum($array);
}

/**
 * 格式标示模板
 * @param $mask_template
 * @param $arr
 */
function fillFmt(&$mask_template, $arr)
{
    for ($i = 0; $i <= 5; $i++)
        $mask_template[8][$i] = $arr[$i] ? 1 : 0;

    for ($i = 7; $i <= 8; $i++)
        $mask_template[8][$i] = $arr[$i - 1] ? 1 : 0;

    for ($i = 14; $i <= 20; $i++)
        $mask_template[8][$i] = $arr[$i - 6] ? 1 : 0;

    for ($k = 0, $i = 20; $i >= 13; $i--, $k++)
        $mask_template[$i][8] = $arr[$k] ? 1 : 0;

    for ($k = 7, $i = 8; $i >= 7; $i--, $k++)
        $mask_template[$i][8] = $arr[$k] ? 1 : 0;

    for ($k = 9, $i = 5; $i >= 0; $i--, $k++)
        $mask_template[$i][8] = $arr[$k] ? 1 : 0;
}

function exportPng($image, $mask_id)
{
    imagepng($image, 'mask' . $mask_id . 'v3_png.png');
    imagedestroy($image);
}

getStringInput();
