<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;

$apis = www_pay_apis ();
// print_r ($apis);

if (!array_key_exists('provider',$_GET)) {
    http_response_code (404);
    exit;
}
if (!array_key_exists($_GET['provider'],$apis)) {
    http_response_code (404);
    exit;
}
http_response_code (200);

try {
    $file = $apis[$_GET['provider']]->file;
    $class = $apis[$_GET['provider']]->class;
    require $file;
    $api = new $class (connect(BLOTTO_MAKE_DB));
    $api->callback ();
}
catch (Exception $e) {
    error_log ('GET '.print_r($_GET,true));
    error_log ('POST '.print_r($_POST,true));
    error_log ($e->getMessage());
}

