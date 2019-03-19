<?php
/**
 * 工具类
 *
 * @author: xieyong <qxieyongp@163.com>
 * @Date: 2017/8/18
 * @Time: 22:19
 */

namespace Oranger\Library\Tools;

use Oranger\Library\Config\ConfigManager;

class Encrypt
{
    /**
     * 字符串加密以及解密函数
     *
     * @param string $string 原文或者密文
     * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
     * @param string $key 密钥
     * @param int    $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
     *
     * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
     */
    public static function encrypt($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;

        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr(
            $string,
            0,
            $ckey_length
        ) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(
            substr($string, $ckey_length)
        ) : sprintf(
                '%010d',
                $expiry ? $expiry + time() : 0
            ) . substr(md5($string . $keyb), 0, 16) .
            $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
                substr($result, 10, 16) ==
                substr(md5(substr($result, 26) . $keyb), 0, 16)
            ) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 用户密码加密函数
     * @author: xieyong <qxieyongp@163.com>
     * @param string $password 密码
     * @param string $salt 随机串
     * @param string $key 密钥
     * @return string
     */
    public static function passwordEncrypt(string $password, string $salt, string $key)
    {
        $md5_pass = md5($password);
        $md5_salt = md5($salt);

        return md5($key . substr($md5_pass, 0, 16) . substr($md5_salt, 0, 16));
    }
}
