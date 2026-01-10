<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

if (!BLOTTO_TICKETS_AUTO) {
    fwrite (STDERR,"-- Auto-assignment of tickets is switched off - skipping\n");
    exit (0);
}

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

try {
    $dbs                = dbs (); // in practice, only one

    $pad_length         = strlen (BLOTTO_TICKET_MAX);
    $total = $duplicates = 0;
    foreach ($dbs as $org_id=>$db) {
        $tickets            = [];
        $players            = [];
        $qty                = 0;
        players_new ($players,$qty,$org_id,$db);
        fwrite (STDERR,"$qty tickets required for org #$org_id `{$db['make']}`\n");
        if ($qty==0) {
            continue;
        }

        foreach ($players as $p) {
            $org_id    =  escm ($p['org_id']);
            $Provider  =  escm ($p['Provider']);
            $RefNo     = escm ($p['RefNo']);
            $ClientRef = escm ($p['ClientRef']);
            $inserted = 0;
            while ($inserted<$p['qty']) {
                // a bit clearer in PHP than lpad(floor(rand())) in sql
                $new = mt_rand (intval(BLOTTO_TICKET_MIN),intval(BLOTTO_TICKET_MAX));
                $new = str_pad ($new,$pad_length,'0',STR_PAD_LEFT);

                $qi = "
                  INSERT INTO `".BLOTTO_TICKET_DB."`.`blotto_ticket` SET
                    `number`='$new'
                   ,`issue_date`=CURDATE()
                   ,`org_id`='$org_id'
                   ,`mandate_provider`='$Provider'
                   ,`dd_ref_no`='$RefNo'
                   ,`client_ref`='$ClientRef'
                  ON DUPLICATE KEY UPDATE `number`=`number`
                  ;
                ";
                try {
                    $zo->query ($qi);
                    if ($zo->affected_rows > 0) {  // if new number inserted
                        $inserted++;
                        $total++;
                    } else {
                        $duplicates++;
                    }
                }
                catch (\mysqli_sql_exception $e) {
                    fwrite (STDERR,$qi."\n".$e->getMessage()."\n");
                    exit (102);
                }
            }
        }
    }
    fwrite (STDERR,"$total new tickets, $duplicates numbers skipped as already in use\n");
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}

