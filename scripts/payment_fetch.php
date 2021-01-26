<?php
// see end for stand-alone bash test script
require __DIR__.'/functions.php';
cfg ();
require $argv[1];

if (!defined('BLOTTO_PAY_API_CLASS')) {
    echo "No payment API - aborting\n";
    exit (101);
}

echo "    Fetching mandate and collection data\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

try {

    require BLOTTO_PAY_API_CLASS;

    $api = new \PayApi ($zo);

    //$h = $api->do_heartbeat ();
    //print_r ($h);
    //$api->import (20,100); // Limit mandates,collections

    $api->import (BLOTTO_DAY_FIRST); // pass starting date

}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    if (!$api->errorCode) {
        // Unexpected error
        exit (103);
    }
    exit ($api->errorCode);
}

