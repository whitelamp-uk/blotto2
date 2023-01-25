<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


try {
    if (defined('BLOTTO_SNAILMAIL') && BLOTTO_SNAILMAIL) {
        // Stannp API is active
        if (defined('BLOTTO_SNAILMAIL_TPL_WIN') && BLOTTO_SNAILMAIL_TPL_WIN) {
            tee ("    Updating Wins.letter_status using snailmail\n");
            snailmail_wins_status ();
            tee ("    Sending winner letters using snailmail\n");
            $results = snailmail_wins ();
            tee ("      {$results['recipients']} mailpieces\n");
            print_r ($results);
        }
        else {
            tee ("    No postal API template for winners\n");
        }
    }
    else {
        tee ("    No postal API is active for winners\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (101);
}

