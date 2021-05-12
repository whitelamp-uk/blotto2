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

header ('Content-Type: text/plain');

if (!array_key_exists('provider',$_GET)) {
    http_response_code (404);
    echo "Resource not found\n";
    exit;
}

$apis = www_pay_apis ();
if (!array_key_exists($_GET['provider'],$apis)) {
    http_response_code (404);
    echo "Resource not found\n";
    exit;
}

$org            = null;
$responded      = false;
$class          = null;

try {
    $file       = $apis[$_GET['provider']]->file;
    $class      = $apis[$_GET['provider']]->class;
    require $file;
    // BLOTTO_MAKE_DB because data is permanent
    $api        = new $class (connect(BLOTTO_MAKE_DB),org());
    // $responded is boolean by reference
    $api->callback ($responded);
}
catch (\Exception $e) {
    if ($responded) {
        $responded = 'Y';
    }
    else {
        $responded = 'N';
        // Something went wrong before responding
        // So respond now
        http_response_code (500);
    }
    echo "Something went wrong\n";
    error_log ('GET '.print_r($_GET,true));
    error_log ('POST '.print_r($_POST,true));
    error_log ($e->getMessage());
    $subj = "Callback error";
    if ($class) {
       $subj .= " from class=$class";
    }
    mail (
        BLOTTO_EMAIL_WARN_TO,
        $subj,
        $e->getMessage()."\nSent 200 OK? $responded",
        'From: '.BLOTTO_EMAIL_FROM
    );
}

