<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
if (defined('CAMPAIGN_MONITOR') && CAMPAIGN_MONITOR) {
    require CAMPAIGN_MONITOR;
}
if (defined('VOODOOSMS') && VOODOOSMS) {
    require VOODOOSMS;
}

if (!array_key_exists('provider',$_GET)) {
    http_response_code (404);
    exit;
}

$apis = www_pay_apis ();
if (!array_key_exists($_GET['provider'],$apis)) {
    http_response_code (404);
    exit;
}

try {
    $file = $apis[$_GET['provider']]->file;
    $class = $apis[$_GET['provider']]->class;
    require $file;
    // BLOTTO_MAKE_DB because data is permanent
    $api = new $class (connect(BLOTTO_MAKE_DB));
    $api->callback ();
}
catch (Exception $e) {
    error_log ('GET '.print_r($_GET,true));
    error_log ('POST '.print_r($_POST,true));
    error_log ($e->getMessage());
    mail (
        BLOTTO_EMAIL_WARN_TO,
        "Callback error from $class",
        $e->getMessage(),
        'From: '.BLOTTO_EMAIL_FROM
    );
}

