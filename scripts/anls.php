<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


try {
    if (defined('BLOTTO_STANNP') && BLOTTO_STANNP) {
        // Stannp API is active
        if (defined('BLOTTO_STANNP_TPL_ANL') && BLOTTO_STANNP_TPL_ANL) {
            require BLOTTO_STANNP_CLASS;
            tee ("    Updating ANLs.letter_status using Stannp\n");
            stannp_status_anls ();
            tee ("    Sending ANLs using Stannp\n");
            $results = stannp_mail_anls ();
            tee ("      {$results['recipients']} mailpieces\n");
            print_r ($results);
        }
        else {
            tee ("    No postal API is active for ANLs\n");
        }
    }
    else {
        tee ("    No postal API is active\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (101);
}

