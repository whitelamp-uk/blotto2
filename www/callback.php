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

$org_code       = BLOTTO_ORG_ID;
$msg            = null;
$responded      = false;
$class          = null;

try {
    try {
        $zo     = connect (BLOTTO_CONFIG_DB);
        $msg    = $zo->query (
          "
            SELECT
              signup_sms_message AS `msg`
            FROM `blotto_org`
            WHERE `org_code`=$org_code
          "
        );
        $msg    = $msg->fetch_assoc()['msg'];
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($e->getMessage());
        throw new \Exception ("Could not get SMS message from database");
    }
    $file       = $apis[$_GET['provider']]->file;
    $class      = $apis[$_GET['provider']]->class;
    require $file;
    // BLOTTO_MAKE_DB because data is permanent
    $api        = new $class (connect(BLOTTO_MAKE_DB));
    // $responded is boolean by reference
    $api->callback ($msg,$responded);
}
catch (\Exception $e) {
    echo "Something went wrong\n";
    if ($responded) {
        $responded = 'Y';
    }
    else {
        $responded = 'N';
        // Something went wrong before responding
        // So respond now
        http_response_code (500);
    }
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

