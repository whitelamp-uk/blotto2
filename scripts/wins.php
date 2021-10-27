<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


try {
    if (defined('BLOTTO_STANNP') && BLOTTO_STANNP) {
        // Stannp API is active
        tee ("    Sending winner letters using Stannp\n");
        require BLOTTO_STANNP_CLASS;
        $results = stannp_mail_wins ();
        tee ("      {$results['recipients']} mailpieces\n");
        print_r ($results);
    }
    else {
        tee ("    No postal API is active for winner letters\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (101);
}

