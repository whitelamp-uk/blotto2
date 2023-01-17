<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


try {
    if (defined('BLOTTO_SNAILMAIL') && BLOTTO_SNAILMAIL) {
        // Stannp API is active
        if (defined('BLOTTO_SNAILMAIL_TPL_WIN') && BLOTTO_SNAILMAIL_TPL_WIN) {
            require BLOTTO_SNAILMAIL_API_STANNP;
            tee ("    Updating Wins.letter_status using Stannp\n");
            stannp_status_wins ();
            tee ("    Sending winner letters using Stannp\n");
            $results = stannp_mail_wins ();
            tee ("      {$results['recipients']} mailpieces\n");
            print_r ($results);
        }
        else {
            tee ("    No postal API is active for winner letters\n");
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

