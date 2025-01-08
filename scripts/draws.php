<?php

function finish_up () {
    global $amounts;
    winnings_notify ($amounts);
}

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

tee ("    Draw result engine\n");

$rdb            = BLOTTO_RESULTS_DB;
$quiet          = get_argument('q',$Sw) !== false;
$placeholder    = str_pad ('',strlen(BLOTTO_TICKET_MAX),'-');
$single         = get_argument('s',$Sw) !== false;
$rbe            = get_argument('r',$Sw) !== false;
if ($rbe && !function_exists('winnings_super')) {
    fwrite (STDERR,"RBE bespoke function winnings_super() does not exist\n");
    exit (101);
}
if (!function_exists('prize_amount')) {
    fwrite (STDERR,"Warning: calculating prize amounts without a bespoke prize_amount() function\n");
}

if (!$quiet) {
	echo "Running verbosely - use -q (after config file) to shut up\n";
    echo "Also as well, use -s for single shot (one draw only) mode\n";
}

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}


// Get a list of draw closed dates having entries
// TODO tighten up the insurance check by making sure that every
// blotto_entry has a corresponding blotto_insurance.  As it stands
// it assumes that as long as one ticket is insured they all are.
$qs = "
    SELECT
      `e`.`draw_closed`
     ,`e`.`id_min`
     ,`e`.`id_max`
     ,`ins`.`draw_closed` IS NOT NULL AS `insured`
     ,`won`.`draw_closed` IS NOT NULL AS `won`
    FROM (
      SELECT
        `draw_closed`
       ,MIN(`id`) AS `id_min`
       ,MAX(`id`) AS `id_max`
      FROM `blotto_entry`
      WHERE `draw_closed` IS NOT NULL
      GROUP BY `draw_closed`
      ORDER BY `draw_closed`
    ) AS `e`
    LEFT JOIN (
      SELECT
        `draw_closed`
      FROM `blotto_insurance`
      GROUP BY `draw_closed`
      ORDER BY `draw_closed`
    ) AS `ins`
    ON `ins`.`draw_closed`=`e`.`draw_closed`
    LEFT JOIN (
      SELECT
        `e`.`draw_closed`
      FROM `blotto_entry` AS `e`
      JOIN `blotto_winner` AS `w`
        ON `w`.`entry_id`=`e`.`id`
      GROUP BY `draw_closed`
      ORDER BY `draw_closed`
    ) AS `won`
    ON `won`.`draw_closed`=`e`.`draw_closed`
    ORDER BY `e`.`draw_closed`
";
if ($single) {
    $qs .= "
      LIMIT 0,1
    ";
}
try {
    $ds = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}
if ($ds->num_rows==0 && !$quiet) {
    echo "No draw entries found\n";
}

echo "    Processing draws\n";
$now                = gmdate ('Y-m-d H:i:s');
$amounts            = [];
register_shutdown_function ('finish_up');
while ($d=$ds->fetch_assoc()) {
    if (!$quiet) {
        echo "Draw = ";
        print_r ($d);
    }
    if ($d['won']) {
        // Assume full draw process is done
        if (!$quiet) {
            echo "All done\n";
        }
        continue;
    }
    try {
        // Draw object with current prizes keyed by level
        $draw       = draw ($d['draw_closed']);
        if (!$quiet) {
            echo "Draw = ";
            print_r ($draw);
        }
        // Draw time, insurance and manual result checks
        $bail       = false;
        if ($now<$draw->time) {
            $bail = true;
            fwrite (STDERR,"WARNING: bail before {$draw->closed} - must wait until {$draw->time}\n");
        }
        if (count($draw->insure) && !$d['insured']) {
            $bail   = true;
            fwrite (STDERR,"WARNING: bail before {$draw->closed} - prize".plural(count($draw->insure))." ".implode(', ',$draw->insure)." insurance requirement\n");
        }
        if ($draw->manual && count($draw->results)==0) {
            foreach ($draw->prizes as $level=>$p) {
                if ($p['function_name']) {
                    try {
                        // Run the manual results function for this prize level
                        prize_function ($draw->prizes[$level],$draw->closed);
                        $draw->results[$level] = true;
                    }
                    catch (\Exception $e) {
                        fwrite (STDERR,"Prize $level' for {$draw->closed} = ".print_r($p,true));
                        fwrite (STDERR,$draw->closed.' '.$e->getMessage()."\n");
                        exit (106);
                    }
                }
            }
            if (count($draw->results)==0) {
                $bail   = true;
                fwrite (STDERR,"Warning bail before {$draw->closed} - manual prize group {$draw->manual} has no results - see README.md 'Manually inserting external number-matches'\n");
             }
        }
        if ($bail) {
            // Bail without an error (no more draws, continue build)
            tee ("    Bailing without error from draw result engine at {$draw->closed}\n");
            exit (0);
        }
        if (!$quiet) {
            echo "{$draw->closed} --------\n";
        }
        // Associative array entry_id => row
        $entries    = entries ($draw->closed);
    }
    catch (\Exception $e) {
        fwrite (STDERR,$e->getMessage()."\n");
        exit (104);
    }
    if (notarised($draw->closed)) {
        if (!$quiet) {
            echo "Entries previously notarised\n";
        }
    }
    else {
        try {
            notarise ($draw->closed,$draw->prizes,'prizes.json',false,false);
            notarise ($draw->closed,$entries,'draw.csv','CSV','HEADERS');
            if (!$quiet) {
                echo "Just notarised\n";
            }
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (105);
        }
    }
    if (notarised($draw->closed,true)) {
        if (!$quiet) {
            echo "Results previously notarised\n";
        }
        continue;
    }
    // Generate winners, do rollovers, do results notarisation
    $manualmatchprizes = $nrmatchprizes = $raffleprizes = $rolloverprizes = [];
    $nrmatchtickets = $manuals = [];
    // Collate the prizes into number matches and raffles
    foreach ($draw->prizes as $level=>$p) {
        if ($p['quantity_percent']) {
            $p['quantity'] = ceil (count($entries)*$p['quantity_percent']/100);
        }
        if ($p['level_method']=='RAFF') {
            // Raffle prizes
            for ($i=0;$i<$p['quantity'];$i++) {
                $raffleprizes[] = $p;
            }
        }
        elseif ($p['results_manual']) {
            // Manuals are number-matches and number-matches only have one match per prize level
            $manuals[$p['group']] = $p['results'][0];
            $manualmatchprizes[$p['level']] = $p;
        }
        else {
            $nrmatchtickets[$p['group']] = $placeholder;
            $nrmatchprizes[$p['level']] = $p;
        }
    }
    $m = count ($nrmatchtickets); // often just 1.  NB count of tickets versus count of prizes.
    $r = count ($raffleprizes); // probably quite a lot.
    // Firstly do number-match prizes so we can run additional raffle if required
    // Don't *think* you can just add additional numbers and prizes.
    if (!$quiet) {
        echo $draw->closed."   Results required:\n";
        echo $draw->closed."     ".count($manualmatchprizes)." number-match prizes requiring ".count($manuals)." manual results\n";
        echo $draw->closed."     ".count($nrmatchprizes)." number-match prizes requiring $m generated results\n";
        echo $draw->closed."     ".count($raffleprizes)." raffle prizes requiring $r generated results\n";
    }
    if ($m) {
        try {
            // Returns both request and response including signature etc.
            $results_nrmatch = random_numbers (
                intval (BLOTTO_TICKET_MIN),
                intval (BLOTTO_TICKET_MAX),
                $m,
                false,
                prize_payout_max ($nrmatchprizes),
                $trng_proof
            );
            $ms = $results_nrmatch->response->result->random->data;
            if (count($ms)!=$m) {
                fwrite (STDERR,"$m tickets needed for number-match prizes but got ".print_r($ms, true)."\n");
                exit (107);
            }
            foreach ($ms as $k=>$v) {
                $ms[$k] = str_pad ($v,strlen(BLOTTO_TICKET_MAX),'0',STR_PAD_LEFT);
            }
            foreach ($nrmatchtickets as $group=>$placeholder) {
                $nrmatchtickets[$group] = array_pop ($ms); // identifies group
            }
            // Random results seem to have worked so:
            notarise (
                $draw->closed,
                [
                    'prizes' => $nrmatchprizes,
                    'results' => $results_nrmatch,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ],
                'results_nrmatch.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw->closed.'/random_nrmatch.info.txt',$trng_proof);
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (108);
        }
    }
    // Add manual prizes to other number-match prizes
    foreach ($manualmatchprizes as $level=>$p) {
        $nrmatchprizes[$level] = $p;
    }
    // Add manual results to other number-match results
    foreach ($manuals as $grp=>$result) {
        $nrmatchtickets[$grp] = $result;
    }
    if (!$quiet) {
        echo $draw->closed." Winners required:\n";
        echo $draw->closed."     ".count($nrmatchprizes)." number-match prize levels with ".count($nrmatchtickets)." perfect match numbers\n";
        echo $draw->closed."     ".count($raffleprizes)." raffle prize levels with ".count($r)." winners to insert\n";
        echo $draw->closed." Doing number-match winners\n";
    }
    // Do number-match winners
    try {
        $as             = winnings_nrmatch ($nrmatchprizes,$entries,$nrmatchtickets,$rbe,!$quiet);
        $levels_matched = array_keys ($as);
        $as             = winnings_add ($amounts,$draw->closed,$as);
        $amounts        = $as;
    }
    catch (\Exception $e) {
        fwrite (STDERR,$e->getMessage()."\n");
        exit (109);
    }
    if (!$quiet) {
        echo $draw->closed." Levels matched = ".count($levels_matched)."\n";
    }
    // Calculate rollovers
    foreach ($nrmatchprizes as $k=>$mp) {
        $roll                   = 0;
        $abf                    = $mp['amount_brought_forward'];
        if (in_array($mp['level'],$levels_matched)) {
            // Number-match has just been won
            $abf                = 0;
        }
        elseif ($mp['rollover_cap'] && $mp['rollover_count']>=$mp['rollover_cap']) {
            // Rollover becomes an ad hoc raffle
            $abf                = 0;
            $rolloverprizes[]   = $mp;
        }
        elseif ($mp['rollover_count']<$mp['rollover_cap']) {
            // Prize has rollover capability to do it
            $roll           = intval($mp['rollover_count']) + 1;
        }
        $qr = "
          UPDATE `blotto_prize`
          SET
            `rollover_count`=$roll
           ,`amount_brought_forward`=$abf
          WHERE `starts`='{$mp['starts']}'
            AND `level`={$mp['level']}
        ";
        try {
            $zo->query ($qr);
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$qr."\n".$e->getMessage()."\n");
            exit (110);
        }
    }
    // Count rolloverprizes and do ad hoc raffle
    $ro = count ($rolloverprizes);
    if (!$quiet) {
        echo $draw->closed." Rollovers:\n";
        echo $draw->closed."     ".count($rolloverprizes)." capped rollovers need forced winners\n";
    }
    if ($ro) {
        try {
            // Returns both request and response. including signature etc.
            $results_rollover = random_numbers (
                $d['id_min'],
                $d['id_max'],
                $ro,
                false,
                prize_payout_max ($rolloverprizes),
                $trng_proof
            );
            $rolloverwinners = $results_rollover->response->result->random->data; // array of IDs
            // That seems to have worked so:
            notarise (
                $draw->closed,
                [
                    'prizes' => $rolloverprizes,
                    'results' => $results_rollover,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ],
                'results_rollover.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw->closed.'/random_rollover.info.txt',$trng_proof);
            // UPDATE blotto_result and insert blotto_winner
            if (!$quiet) {
                echo $draw->closed." Doing capped rollovers by raffle\n";
            }
            $as         = winnings_raffle ($rolloverprizes,$entries,$rolloverwinners,$rbe,'ADHOC',!$quiet);
            $as         = winnings_add ($amounts,$draw->closed,$as);
            $amounts    = $as;
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (111);
        }
    }
    // Do raffles
    if ($r) {
        try {
            // Returns both request and response. including signature etc.
            $results_raffle = random_numbers (
                $d['id_min'],
                $d['id_max'],
                $r,
                false,
                prize_payout_max ($raffleprizes),
                $trng_proof
            );
            $rafflewinners = $results_raffle->response->result->random->data; // array of IDs
            // That seems to have worked so:
            notarise (
                $draw->closed,
                [
                    'prizes' => $raffleprizes,
                    'results' => $results_raffle,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ],
                'results_raffle.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw->closed.'/random_raffle.info.txt',$trng_proof);
            // Update blotto_result and blotto_winner
            if (!$quiet) {
                echo $draw->closed." Doing standard raffle winners\n";
            }
            $as         = winnings_raffle ($raffleprizes,$entries,$rafflewinners,$rbe,false,!$quiet);
            $as         = winnings_add ($amounts,$draw->closed,$as);
            $amounts    = $as;
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (112);
        }
    }
    
}

tee ("    Finished with draw result engine\n");

