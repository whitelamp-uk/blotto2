<?php

require '../config.www.php';


function cookie2end ($cookie_key) {
    if (!array_key_exists($cookie_key,$_COOKIE)) {
        return true;
    }
    if (!strlen($_COOKIE[$cookie_key])) {
        return true;
    }
    if ($_COOKIE[$cookie_key]<time()) {
        return true;
    }
    return false;
}

function cookie2pwd ($cookie_key) {
    if (!array_key_exists($cookie_key,$_COOKIE)) {
        return '';
    }
    $cookie = $_COOKIE[$cookie_key];
    $iv = hex2bin(substr($cookie, 0, BLOTTO_WWW_IV_LENGTH * 2)); 
    $encval = base64_decode(substr($cookie, BLOTTO_WWW_IV_LENGTH * 2)); 
    return openssl_decrypt (
        $encval,
        BLOTTO_WWW_ALG,
        BLOTTO_WWW_KEY,
        0,
        $iv
    );
}

function cookie2value ($cookie_key) {
    if (!array_key_exists($cookie_key,$_COOKIE)) {
        return '';
    }
    return $_COOKIE[$cookie_key];
}

function pwd2cookie ($password) {
    $iv = random_bytes(BLOTTO_WWW_IV_LENGTH);
    return bin2hex($iv).base64_encode (
        openssl_encrypt (
            $password,
            BLOTTO_WWW_ALG,
            BLOTTO_WWW_KEY,
            0,
            $iv
        )
    );
}
