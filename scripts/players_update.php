<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}


if (!function_exists('draw_first')) {
    fwrite (STDERR,"Bespoke function draw_first() was not found\n");
    exit (102);
}


// Set player start date and supporter projected first draw
$starts = [];
$firsts = [];
$qs = "
  SELECT
    `m`.`ClientRef`
   ,`m`.`Created`
   ,`m`.`StartDate`
   ,`p`.`id` AS `player_id`
   ,`p`.`supporter_id`
   ,`p`.`started`
   ,`s`.`projected_first_draw_close`
   ,`s`.`canvas_code`
   ,IF(`s`.`client_ref`=`p`.`client_ref`,1,0) AS `is_original_player`
  FROM `blotto_build_mandate` AS `m`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
  WHERE `p`.`started` IS NULL
     OR `s`.`projected_first_draw_close` IS NULL
  ;
";
try {
    $ms = $zo->query ($qs);
    fwrite (STDERR,"{$ms->num_rows} players where started date not set\n");
    echo "-- Update started date\n";
    while ($m=$ms->fetch_assoc()) {
        if (!$m['started']) {
            if (!array_key_exists($m['Created'],$starts)) {
                $starts[$m['Created']] = [];
            }
            $starts[$m['Created']][] = $m['player_id'];
        }
        if (!$m['projected_first_draw_close']) {
            // Build-up some player balance before entering the game

            if ($m['is_original_player']) {
                // Original player needs to build up some balance
                // Use the bespoke function
                $close = draw_first ($m['StartDate'],$m['canvas_code']);
            }
            else {
                // For now, use the bespoke function
                $close = draw_first ($m['StartDate'],$m['canvas_code']);
/*
TODO:
If you have either no change or a reduction in chances from old player
to new, the new player should be able to play immediately.
What should happen if there is an increase in chances? Probably the same as a new supporter.
We now have `blotto_player`.`opening_balance` and I think we need `balance_transferred` too.
Then a new build process should:
 * find all superceded players with a non-zero balance
 * set `balance_transferred` to that value
 * increment `opening_balance` of latest player by that value
 * Modify all existing logic to substract `balance_transferred` in balance calculations.
BTW the last bit of this script (updating chances line 151) might want to happen before this bit.
*/
            }
            if (!array_key_exists($close,$firsts)) {
                $firsts[$close] = [];
            }
            $firsts[$close][] = $m['supporter_id'];
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}
/*
blotto_player(supporter_id,started) is unique so `started` identifies and orders
the players that make the history of any given supporter. It has no more subtle
meaning than that - it has no implications around money, draw entries etc
*/
echo "-- Update player started date\n";
foreach ($starts as $date=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `started`='$date' WHERE `id` IN (".implode(',',$ids).");\n";
}
echo "-- Update projected first draw close\n";
foreach ($firsts as $close=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_supporter` SET `projected_first_draw_close`='$close' WHERE `id` IN (".implode(',',$ids).");\n";
}



// Set the player first draw close
$firsts                     = [];
$qs = "
  SELECT
    `p`.`id`
   ,`s`.`canvas_code`
   ,MIN(`c`.`DateDue`) AS `first_collected`
  FROM `blotto_player` AS `p`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
  JOIN `blotto_build_collection` as `c`
    ON `c`.`ClientRef`=`p`.`client_ref`
   AND `c`.`PaidAmount`>0
  WHERE `p`.`first_draw_close` IS NULL
  GROUP BY `p`.`id`
";
try {
    $ps                     = $zo->query ($qs);
    fwrite (STDERR,"{$ps->num_rows} players where first draw close not set\n");
    while ($p=$ps->fetch_assoc()) {
        // Use the bespoke function
        $date               = draw_first ($p['first_collected'],$p['canvas_code']);
        if (!array_key_exists($date,$firsts)) {
            $firsts[$date]  = [];
        }
        $firsts[$date][]    = $p['id'];
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}
echo "-- Update player actual first draw close\n";
foreach ($firsts as $date=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `first_draw_close`='$date' WHERE `id` IN (".implode(',',$ids).");\n";
}


// Set player chances
$chances_options = [];
$qs = "
  SELECT
    `p`.`id`
   ,`m`.`ClientRef`
   ,`m`.`ChancesCsv`
  FROM `blotto_build_mandate` AS `m`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  WHERE `p`.`chances` IS NULL
";
try {
    $ps = $zo->query ($qs);
    fwrite (STDERR,"{$ps->num_rows} players where chances not set but could be\n");
    echo "-- Update chances\n";
    while ($p=$ps->fetch_assoc()) {
        $chances = explode (',',$p['ChancesCsv']);
        $chances = intval (trim(array_pop($chances)));
        if ($chances<1) {
            fwrite (STDERR,"$chances chances is not valid from mandate ChancesCsv={$p['ChancesCsv']} for {$p['ClientRef']}. Trying mandate amount instead\n");
        } else {
            if (!array_key_exists($chances,$chances_options)) {
                $chances_options[$chances]  = [];
            }
            $chances_options[$chances][] = $p['id'];
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (106);
}
echo "-- Update player chances\n";
foreach ($chances_options as $chances=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `chances`=$chances WHERE `id` IN (".implode(',',$ids).");\n";
}

// Set player chances using mandate amount
$chances_options = [];
$qs = "
  SELECT
    `p`.`id`
   ,`m`.`ClientRef`
   ,`m`.`Amount`
   ,`m`.`Freq`
  FROM `blotto_build_mandate` AS `m`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  WHERE `p`.`chances` IS NULL
";
try {
    $ps = $zo->query ($qs);
    fwrite (STDERR,"{$ps->num_rows} players where chances not set but could be\n");
    echo "-- Update chances\n";
    while ($p=$ps->fetch_assoc()) {
        $amount = $p['Amount'];
        $freq = $p['Freq'];
        if ($freq == 'Monthly') {
            if ($amount == '13.00') {
                $amount = 13.02; // hack for that one stupid SHC mandate
            }
            $chances = intval($amount / 4.34); // works up to six tickets
        } else if ($freq == 'Annually') {
            $chances = intval($amount / 52); // works up to six tickets
        }
        if ($chances<1) {
            fwrite (STDERR,"$chances chances is not valid from mandate Amount={$p['Amount']} for {$p['ClientRef']}.\n");
            exit (105);
        } else {
            if (!array_key_exists($chances,$chances_options)) {
                $chances_options[$chances]  = [];
            }
            $chances_options[$chances][] = $p['id'];
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (106);
}
echo "-- Update player chances, part 2\n";
foreach ($chances_options as $chances=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `chances`=$chances WHERE `id` IN (".implode(',',$ids).");\n";
}

