<?php
// see end for stand-alone bash test script
require __DIR__.'/functions.php';
cfg ();
require $argv[1];

echo "    Fetching mandate and collection data\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

try {
    $cn = get_defined_constants(true);
    $userconst = $cn['user'];

    $api_found = false;
    foreach ($userconst as $dfn => $file) {
        if (strpos($dfn,'BLOTTO_PAY_API_CLASS')===0) {
            require $file;

            $api = new \PayApi ($zo);

            $api->import (BLOTTO_DAY_FIRST);
            $api_found = true;
        }
    }
    if (!$api_found) {
        echo "No payment API - aborting\n";
        exit (101);
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    if (!$api->errorCode) {
        // Unexpected error
        exit (103);
    }
    exit ($api->errorCode);
}

