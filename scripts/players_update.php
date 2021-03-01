<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
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
   ,`s`.`projected_first_draw_close`
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
                $firsts[$m['Created']] = [];
            }
            array_push ($starts[$m['Created']],$m['player_id']);
        }
        if (!$m['projected_first_draw_close']) {
            if (!array_key_exists($m['StartDate'],$firsts)) {
                $firsts[$m['StartDate']] = [];
            }
            array_push ($firsts[$m['StartDate']],$m['supporter_id']);
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}
echo "-- Update player started date\n";
foreach ($starts as $date=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `started`='$date' WHERE `id` IN (".implode(',',$ids).");\n";
}
echo "-- Update projected first draw close\n";
foreach ($firsts as $closed=>$ids) {
    if (!count($ids)) {
        continue;
    }
    $date = draw_first ($closed);
    echo "UPDATE `blotto_supporter` SET `projected_first_draw_close`='$date' WHERE `id` IN (".implode(',',$ids).");\n";
}



// Set player actual first draw
$firsts                     = [];
$qs = "
  SELECT
    `p`.`id`
   ,MIN(`c`.`DateDue`) AS `first_collected`
  FROM `blotto_player` AS `p`
  JOIN `blotto_build_collection` as `c`
    ON `c`.`ClientRef`=`p`.`client_ref`
   AND `c`.`PaidAmount`>0
  WHERE `p`.`first_draw_close` IS NULL
  GROUP BY `p`.`id`
";
try {
    $players                = $zo->query ($qs);
    fwrite (STDERR,"{$players->num_rows} players where first draw close not set\n");
    while ($p=$players->fetch_assoc()) {
        $date               = draw_first ($p['first_collected']);
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
    $ms = $zo->query ($qs);
    fwrite (STDERR,"{$ms->num_rows} players where chances not set but could be\n");
    echo "-- Update chances\n";
    while ($m=$ms->fetch_assoc()) {
        $chances = explode (',',$m['ChancesCsv']);
        $chances = intval (trim(array_pop($chances)));
        if ($chances<1) {
            fwrite (STDERR,"$chances chances is not valid from ChancesCsv={$m['ChancesCsv']} for {$m['ClientRef']}\n");
            exit (103);
        }
        if (!array_key_exists($chances,$chances_options)) {
            $chances_options[$chances]  = [];
        }
        array_push ($chances_options[$chances],$p['id']);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}
echo "-- Update player chances\n";
foreach ($chances_options as $chances=>$ids) {
    if (!count($ids)) {
        continue;
    }
    echo "UPDATE `blotto_player` SET `chances`=$chances WHERE `id` IN (".implode(',',$ids).");\n";
}


// TEMPORARY DOUBLE CHECK
$qs = "
  SELECT
    `p`.`id`
  FROM `blotto_player` AS `p`
  JOIN `blotto_build_mandate` AS `m`
    ON `m`.`ClientRef`=`p`.`client_ref`
  WHERE `p`.`chances` IS NULL
     OR `p`.`started` IS NULL
     OR `p`.`started`='0000-00-00'
";
try {
    $errors = $zo->query ($qs);
    if ($errors->num_rows) {
      fwrite (STDERR,$qs."\nplayers_update.php: this should not happen!\n");
      exit (105);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (106);
}


