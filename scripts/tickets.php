<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


if (!BLOTTO_TICKETS_AUTO) {
    tee ("-- Auto-assignment of tickets is switched off - skipping\n");
    exit (0);
}

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}


$org = BLOTTO_ORG_NAME;
$fdb = BLOTTO_DB;
$tdb = BLOTTO_TICKET_DB;
$dbs = [];


try {


    $dbs                = dbs ();
    tee ("-- Ticket pool is in `$tdb`.`blotto_ticket`\n");

    echo "USE `$tdb`;\n";
    echo "SET @today = CURDATE();\n\n";

    $pad_length         = strlen (BLOTTO_TICKET_MAX);
    $count              = 0;
    $tickets            = [];
    $players            = [];
    $qty                = 0;

    foreach ($dbs as $org_id=>$db) {
        players_new ($players,$qty,$org_id,$db);
        tee ("-- $qty tickets required for org #$org_id `{$db['make']}`\n");
        if ($qty==0) {
            continue;
        }
    }

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
            array_push ($tickets,$new);
            break;
        }
    }

    // Safety check
    if (count($tickets)!=$qty) {
        fwrite (STDERR,count($tickets)." selected which is different to $qty needed\n");
        exit (103);
    }

    // Generate SQL
    $super = [];
    foreach ($players as $p) {
        if (!array_key_exists($p['db']['make'],$super)) {
            $super[$p['db']['make']] = [];
        }
        if (!array_key_exists($p['db']['frontend'],$super)) {
            $super[$p['db']['frontend']] = [];
        }
        $crf        = escm ($p['ClientRef']);
        for ($i=0;$i<$p['qty'];$i++) {
            $tkt    = array_shift ($tickets);
            echo "INSERT INTO `blotto_ticket` SET `org_id`={$p['org_id']},`mandate_provider`='{$p['Provider']}',`client_ref`='$crf',`issue_date`=@today,`number`='{$tkt}';\n";
            if (defined('BLOTTO_RBE_DBS')) {
                $super[$p['db']['make']][$tkt] = $crf;
                $super[$p['db']['frontend']][$tkt] = $crf;
            }
        }
    }

}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}


if (!defined('BLOTTO_RBE_DBS')) {
    exit (0);
}

// Superdraw ticket feedback
foreach ($super as $db=>$players) {
    echo "\n\n-- Pass tickets back to $db\n\n";
    echo "USE $db;\n\n";
    foreach ($players as $ticket=>$cref) {
        echo "INSERT IGNORE INTO `blotto_super_ticket` SET `superdraw_db`='$fdb',`superdraw_name`='$org',`ticket_number`='$ticket',`client_ref`='$cref';\n";
    }
}

