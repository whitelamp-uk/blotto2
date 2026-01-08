<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

$tdb = BLOTTO_TICKET_DB;

if (!BLOTTO_TICKETS_AUTO) {
    tee ("-- Auto-assignment of tickets is switched off - skipping\n");
    exit (0);
}

try {
    $dbs                = dbs ();
    tee ("-- Ticket pool is in `$tdb`.`blotto_ticket`\n");

    echo "USE `$tdb`;\n";
    echo "SET @today = CURDATE();\n\n";

    $pad_length         = strlen (BLOTTO_TICKET_MAX);

    foreach ($dbs as $org_id=>$db) {
        $tickets            = [];
        $players            = [];
        $qty                = 0;
        players_new ($players,$qty,$org_id,$db);
        tee ("-- $qty tickets required for org #$org_id `{$db['make']}`\n");
        if ($qty==0) {
            continue;
        }
        // find available ticket numbers
        for ($i=0;$i<$qty;$i++) {
            while (1) {
                $new = mt_rand (intval(BLOTTO_TICKET_MIN),intval(BLOTTO_TICKET_MAX));
                $new = str_pad ($new,$pad_length,'0',STR_PAD_LEFT);
                if (in_array($new,$tickets)) {
                    // Already selected so try again
                    continue;
                }
                $qs = "
                  SELECT
                    `number`
                  FROM `$tdb`.`blotto_ticket`
                  WHERE `number`='$new'
                  LIMIT 0,1
                  ;
                ";
                try {
                    $r = $zo->query ($qs);
                    if ($r->num_rows>0) {
                        // Already issued so try again
                        continue;
                    }
                }
                catch (\mysqli_sql_exception $e) {
                    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
                    exit (102);
                }
                $tickets[] = $new;
                break;
            }
        }

        // Safety check
        if (count($tickets)!=$qty) {
            fwrite (STDERR,count($tickets)." selected which is different to $qty needed\n");
            exit (103);
        }

        // Generate SQL
        foreach ($players as $p) {
            $crf        = escm ($p['ClientRef']);
            $rno        = escm ($p['RefNo']);
            for ($i=0;$i<$p['qty'];$i++) {
                $tkt    = array_pop ($tickets); // pop not shift!
                echo "INSERT INTO `blotto_ticket` SET `org_id`={$p['org_id']},`mandate_provider`='{$p['Provider']}',`dd_ref_no`='$rno',`client_ref`='$crf',`issue_date`=@today,`number`='{$tkt}';\n";
            }
        }
    }



}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}

