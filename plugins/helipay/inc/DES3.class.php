<?php
class DES3 {

    // 数据加密
    function encrypt($input, $key) {
        // 使用 OpenSSL 3DES-CBC 加密
        $key = str_pad($key, 24, '0'); // 确保密钥长度为 24 字节
        $iv = openssl_random_pseudo_bytes(8); // 3DES 的 IV 长度为 8 字节
        $input = $this->pkcs5_pad($input, 8); // 3DES 块大小为 8 字节
        $encrypted = openssl_encrypt(
            $input,
            'des-ede3-cbc', // 3DES-CBC 模式
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($iv . $encrypted); // 返回 IV + 加密数据
    }

    // 数据解密
    function decrypt($encrypted, $key) {
        $encrypted = base64_decode($encrypted);
        $key = str_pad($key, 24, '0'); // 确保密钥长度为 24 字节
        $iv = substr($encrypted, 0, 8); // 提取前 8 字节作为 IV
        $encrypted = substr($encrypted, 8); // 剩余部分为加密数据
        $decrypted = openssl_decrypt(
            $encrypted,
            'des-ede3-cbc', // 3DES-CBC 模式
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return $this->pkcs5_unpad($decrypted); // 去除填充
    }

    // PKCS5 填充
    function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    // PKCS5 去除填充
    function pkcs5_unpad($text) {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    function encrypt2($input, $key)
    {
        $key = str_pad($key, 24, '0');
        $encrypted = openssl_encrypt($input, 'des-ede3', $key, OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }

    function decrypt2($encrypted, $key)
    {
        $key = str_pad($key, 24, '0');
        $encrypted = base64_decode($encrypted);
        $decrypted = openssl_decrypt($encrypted, 'des-ede3', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }
}
?>