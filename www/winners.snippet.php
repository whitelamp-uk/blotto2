<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;

header ('Access-Control-Allow-Origin: *');

$f = null;
if (array_key_exists('f',$_GET)) {
    $f = $_GET['f'];
    if (!$f) {
        $f = null;
    }
}

$w = www_winners ($f);

table ('lottery-results-latest-table','','Results for '.$w['date'],['Prize','Number'],$w['results']);

table ('lottery-winners-latest-table','','Winners for '.$w['date'],['Ticket','Winnings'],$w['wins']);


