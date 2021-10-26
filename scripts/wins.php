<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];
require BLOTTO_STANNP_CLASS;


try {
    if (defined('BLOTTO_STANNP') && BLOTTO_STANNP) {
        // Stannp API is active
        tee ("Sending winner letters using Stannp\n");
        $results = stannp_mail_wins ();
        print_r ($results);
        tee ("{$results['items']} mailpieces\n");
    }
    else {
        tee ("No postal API is active for winner letters\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (101);
}

