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
  FROM `blotto_build_mandate` AS `m`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
  WHERE `p`.`started` IS NULL
     OR `s`.`projected_first_draw_close` IS NULL
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
            array_push ($starts[$m['Created']],$m['player_id']);
        }
        if (!$m['projected_first_draw_close']) {
            $close = draw_first ($m['StartDate'],$m['canvas_code']);
            if (!array_key_exists($close,$firsts)) {
                $firsts[$close] = [];
            }
            array_push ($firsts[$close],$m['supporter_id']);
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}
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



// Set player actual first draw
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
        $date               = draw_first ($p['first_collected'],$p['canvas_code']);
        if (!array_key_exists($date,$firsts)) {
            $firsts[$date]  = [];
        }
        array_push ($firsts[$date],$p['id']);
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
    AND `m`.`ChancesCsv`!=''
";
try {
    $ps = $zo->query ($qs);
    fwrite (STDERR,"{$ps->num_rows} players where chances not set but could be\n");
    echo "-- Update chances\n";
    while ($p=$ps->fetch_assoc()) {
        $chances = explode (',',$p['ChancesCsv']);
        $chances = intval (trim(array_pop($chances)));
        if ($chances<1) {
            fwrite (STDERR,"$chances chances is not valid from ChancesCsv={$p['ChancesCsv']} for {$p['ClientRef']}\n");
            exit (105);
        }
        if (!array_key_exists($chances,$chances_options)) {
            $chances_options[$chances]  = [];
        }
        array_push ($chances_options[$chances],$p['id']);
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

