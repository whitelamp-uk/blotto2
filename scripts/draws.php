<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

tee ("    Generating draw results\n");

$quiet          = get_argument('q',$Sw) !== false;
$rbe            = get_argument('r',$Sw) !== false;
$single         = get_argument('s',$Sw) !== false;
$placeholder    = str_pad ('',strlen(BLOTTO_TICKET_MAX),'-');
$rdb            = BLOTTO_RESULTS_DB;

if (!$quiet) {
	echo "Running verbosely - use -q (after config file) to shut up\n";
    echo "Also as well, use -s for single shot (one draw only) mode\n";
}

if ($rbe && !function_exists('winnings_super')) {
    fwrite (STDERR,"RBE bespoke function winnings_super() does not exist\n");
    exit (101);
}


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

// Insert any missing manual results by deriving them
// from partial results inserted by hand
echo "    Updating manual results to fill holes\n"; 
$qi ="
  INSERT IGNORE INTO `$rdb`.`blotto_result`
    (`draw_closed`,`prize_level`,`number`)
    SELECT
      `num`.`draw_closed`
     ,`lev`.`level`
     ,`num`.`number`
    FROM (
      -- List of manually entered numbers by date/level group
      SELECT
        `r`.`draw_closed`
       ,`r`.`number`
       ,SUBSTR(`p`.`level_method`,-1) AS `group`
      FROM `$rdb`.`blotto_result` AS `r`
      JOIN `blotto_prize` AS `p`
        ON `p`.`level`=`r`.`prize_level`
       AND `p`.`results_manual`>0
       AND `p`.`level_method`!='RAFF'
      GROUP BY `r`.`draw_closed`,`group`
    ) AS `num`
    -- Expand to the list of levels in each level group
    JOIN `blotto_prize` AS `lev`
      ON substr(`lev`.`level_method`,-1)=`num`.`group`
    ORDER BY `num`.`draw_closed`,`lev`.`level`
  ;
";
try {
    $zo->query ($qi);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qi."\n".$e->getMessage()."\n");
    exit (103);
}


// Get a list of draw closed dates having entries
// TODO tighten up the insurance check by making sure that every 
// blotto_entry has a corresponding blotto_insurance.  As it stands
// it assumes that as long as one ticket is insured they all are.
$qs = "
    SELECT
      `e`.`draw_closed`
     ,drawAfter(`e`.`draw_closed`) AS `draw_after`
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
    exit (104);
}
if ($ds->num_rows==0 && !$quiet) {
    echo "No draw entries found\n";
}


echo "    Processing draws\n"; 
$now                = date ('Y-m-d H:i:s');
$amounts            = [];
while ($d=$ds->fetch_assoc()) {
    if ($now<$d['draw_after']) {
        fwrite (STDERR,"Refusing to draw closed $draw_closed until after {$d['draw_after']}\n");
        fwrite (STDERR,"So draws.php bailing out at this point\n");
        winnings_notify ($amounts);
        exit (0);
    }
    $draw_closed    = $d['draw_closed'];
    $won            = $d['won'];
    $amounts[$draw_closed] = [];
    if (!$quiet) {
        echo "$draw_closed ----------------\n";
    }
    if ($won) {
        // Assume full draw process is done
        if (!$quiet) {
            echo "All done\n";
        }
        continue;
    }
    try {
        // Associative array entry_id => row
        $entries        = entries ($draw_closed);
        // Current prizes by level
        $prizes         = prizes ($draw_closed);
        // Prize and manual result checks
        foreach ($prizes as $p) {
            if ($p['insure'] && !$d['insured']) {
                fwrite (STDERR,"Refusing to do draws on or after $draw_closed because prize {$p['level']}@{$p['starts']} requires insurance\n");
                winnings_notify ($amounts);
                exit (0);
            }
        }
    }
    catch (\Exception $e) {
        fwrite (STDERR,$e->getMessage()."\n");
        exit (106);
    }
    if (notarised($draw_closed)) {
        if (!$quiet) {
            echo "Entries previously notarised\n";
        }
    }
    else {
        try {
            notarise ($draw_closed,$prizes,'prizes.json',false,false,true);
            notarise ($draw_closed,$entries,'draw.csv','CSV','HEADERS');
            if (!$quiet) {
                echo "Just notarised\n";
            }
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (107);
        }
    }
    if (notarised($draw_closed,true)) {
        if (!$quiet) {
            echo "Results previously notarised\n";
        }
        continue;
    }
    // Generate winners, do rollovers, ro results notarisation
    $manualmatchprizes = $nrmatchprizes = $raffleprizes = $rolloverprizes = [];
    $nrmatchtickets = $manuals = [];
    // Collate the prizes into number matches and raffles
    foreach ($prizes as $level=>$p) {
        if ($p['level_method']=='RAFF') {
            // Raffle prizes
            for ($i=0;$i<$p['quantity'];$i++) {
                array_push ($raffleprizes,$p);
            }
            continue;
        }
        $nrmatchtktgroup = substr ($p['level_method'],-1);  
        if ($p['results_manual']) {
            if (!$p['results']) {
                if (!$p['function_name']) {
                    fwrite (STDERR,"Refusing to do draws on or after $draw_closed because manual prize {$p['level']}@{$p['starts']} has no results, and no manual function is given (that is, results must be inserted into blotto_result by hand)\n");
                        winnings_notify ($amounts);
                        exit (0);
                }
                try {
                    // Run the manual results function for this prize level
                    prize_function ($p,$draw_closed);
                }
                catch (\Exception $e) {
                    fwrite (STDERR,"Prize {$p['level']} for $draw_closed = ".print_r($p,true));
                    fwrite (STDERR,$draw_closed.' '.$e->getMessage()."\n");
                    winnings_notify ($amounts);
                    exit (108);
                }
            }
            // Manuals are number-matches and number-matches only have one match per prize level
            $manuals[$nrmatchtktgroup] = $p['results'][0];
            $manualmatchprizes[$p['level']] = $p;
            continue;
        }
        $nrmatchtickets[$nrmatchtktgroup] = $placeholder;
        $nrmatchprizes[$p['level']] = $p;
    }
    $m = count ($nrmatchtickets); // probably just 1.  NB count of tickets versus count of prizes.
    $r = count ($raffleprizes); // probably quite a lot.
    // Firstly do number-match prizes so we can run additional raffle if required
    // Don't *think* you can just add additional numbers and prizes.
    if (!$quiet) {
        echo $draw_closed."   Results required:\n";
        echo $draw_closed."     ".count($manualmatchprizes)." number-match prizes requiring ".count($manuals)." manual results\n";
        echo $draw_closed."     ".count($nrmatchprizes)." number-match prizes requiring $m generated results\n";
        echo $draw_closed."     ".count($raffleprizes)." raffle prizes requiring $r generated results\n";
    }
    if ($m) {
        try {
            // Returns both request and response including signature etc.
            $results_nrmatch = random_numbers (
                intval (BLOTTO_TICKET_MIN),
                intval (BLOTTO_TICKET_MAX),
                $m,
                false,
                $trng_proof
            );
            $ms = $results_nrmatch->response->result->random->data;
            if (count($ms)!=$m) {
                fwrite (STDERR,"$m tickets needed for number-match prizes but got ".print_r($ms, true)."\n");
                winnings_notify ($amounts);
                exit (109);
            }
            foreach ($ms as $k=>$v) {
                $ms[$k] = str_pad ($v,strlen(BLOTTO_TICKET_MAX),'0',STR_PAD_LEFT);
            }        
            foreach ($nrmatchtickets as $group=>$placeholder) {
                $nrmatchtickets[$group] = array_pop ($ms); // identifies group
            }
            // Random results seem to have worked so:
            notarise (
                $draw_closed,
                array (
                    'prizes' => $nrmatchprizes,
                    'results' => $results_nrmatch,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ),
                'results_nrmatch.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw_closed.'/random_nrmatch.info.txt',$trng_proof);
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            winnings_notify ($amounts);
            exit (110);
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
        echo $draw_closed." Winners required:\n";
        echo $draw_closed."     ".count($nrmatchprizes)." number-match prize levels with ".count($nrmatchtickets)." perfect match numbers\n";
        echo $draw_closed."     ".count($raffleprizes)." raffle prize levels with ".count($r)." winners to insert\n";
        echo $draw_closed." Doing number-match winners\n";
    }
    // Do number-match winners
    try {
        $as             = winnings_nrmatch ($nrmatchprizes,$entries,$nrmatchtickets,$rbe,!$quiet);
        $levels_matched = array_keys ($as);
        $amounts        = winnings_add ($amounts,$draw_closed,$as);
    }
    catch (\Exception $e) {
        fwrite (STDERR,$e->getMessage()."\n");
        winnings_notify ($amounts);
        exit (111);
    }
    if (!$quiet) {
        echo $draw_closed." Levels matched = ".count($levels_matched)."\n";
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
            array_push ($rolloverprizes,$mp);
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
            winnings_notify ($amounts);
            exit (112);
        }
    }
    // Count rolloverprizes and do ad hoc raffle
    $ro = count ($rolloverprizes);
    if (!$quiet) {
        echo $draw_closed." Rollovers:\n";
        echo $draw_closed."     ".count($rolloverprizes)." capped rollovers need forced winners\n";
    }
    if ($ro) {
        try {
            // Returns both request and response. including signature etc.
            $results_rollover = random_numbers (
                $d['id_min'],
                $d['id_max'],
                $ro,
                false,
                $trng_proof
            );
            $rolloverwinners = $results_rollover->response->result->random->data; // array of IDs
            // That seems to have worked so:
            notarise (
                $draw_closed,
                array (
                    'prizes' => $rolloverprizes,
                    'results' => $results_rollover,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ),
                'results_rollover.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw_closed.'/random_rollover.info.txt',$trng_proof);
            // UPDATE blotto_result and insert blotto_winner
            if (!$quiet) {
                echo $draw_closed." Doing capped rollovers by raffle\n";
            }
            $as         = winnings_raffle ($rolloverprizes,$entries,$rolloverwinners,$rbe,'ADHOC',!$quiet);
            $amounts    = winnings_add ($amounts,$draw_closed,$as);
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            winnings_notify ($amounts);
            exit (113);
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
                $trng_proof
            );
            $rafflewinners = $results_raffle->response->result->random->data; // array of IDs
            // That seems to have worked so:
            notarise (
                $draw_closed,
                array (
                    'prizes' => $raffleprizes,
                    'results' => $results_raffle,
                    'verify_url' => BLOTTO_TRNG_API_VERIFY
                ),
                'results_raffle.json'
            );
            file_write (BLOTTO_PROOF_DIR.'/'.$draw_closed.'/random_raffle.info.txt',$trng_proof);
            // Update blotto_result and blotto_winner
            if (!$quiet) {
                echo $draw_closed." Doing standard raffle winners\n";
            }
            $as         = winnings_raffle ($raffleprizes,$entries,$rafflewinners,$rbe,false,!$quiet);
            $amounts    = winnings_add ($amounts,$draw_closed,$as);
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            winnings_notify ($amounts);
            exit (114);
        }
    }
}

winnings_notify ($amounts);

