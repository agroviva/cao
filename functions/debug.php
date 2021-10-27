<?php

if (!function_exists("Dump")) {
    function Dump($dmp, $prnt = false)
    {
        echo '<pre>';
        if ($prnt) {
            print_r($dmp);
        } else {
            var_dump($dmp);
        }
        echo '</pre>';
    }
}

if (!function_exists("encryptIt")) {
    function encryptIt($q)
    {
        $cryptKey = 'upfafhwpqafhsaofihqwohfasastfAKD';
        $qEncoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), $q, MCRYPT_MODE_CBC, md5(md5($cryptKey))));

        return  $qEncoded;
    }
}

if (!function_exists("decryptIt")) {
    function decryptIt($q)
    {
        $cryptKey = 'upfafhwpqafhsaofihqwohfasastfAKD';
        $qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), base64_decode($q), MCRYPT_MODE_CBC, md5(md5($cryptKey))), "\0");

        return  $qDecoded;
    }
}