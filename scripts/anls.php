<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];
require BLOTTO_STANNP_CLASS;


try {
    if (defined('BLOTTO_STANNP') && BLOTTO_STANNP) {
        // Stannp API is active
        tee ("    Sending ANLs using Stannp\n");
        $results = stannp_mail_anls ();
        tee ("      {$results['recipients']} mailpieces\n");
        print_r ($results);
    }
    else {
        tee ("    No postal API is active for ANLs\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (101);
}

