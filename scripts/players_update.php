<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

// Set started date
$qs = "
  SELECT
    `m`.`ClientRef`
   ,`m`.`Created`
   ,`m`.`StartDate`
   ,`p`.`supporter_id`
   ,`s`.`projected_first_draw_close`
  FROM `blotto_build_mandate` AS `m`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
  WHERE `p`.`started` IS NULL
";
$new = [];
try {
    $ms = $zo->query ($qs);
    fwrite (STDERR,"{$ms->num_rows} players where started date not set\n");
    echo "-- Update started date\n";
    while ($m=$ms->fetch_assoc()) {
        if (!$m['projected_first_draw_close']) {
            if (!array_key_exists($m['StartDate'],$new)) {
                $new[$m['StartDate']] = [];
            }
            array_push ($new[$m['StartDate']],$m['supporter_id']);
        }
        echo "UPDATE `blotto_player` SET `started`='{$m['Created']}' WHERE `client_ref`='{$m['ClientRef']}';\n";
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}


// Set projected_first_draw_close
foreach ($new as $starts=>$ids) {
    $first = draw_first ($starts);
    echo "UPDATE `blotto_supporter` SET `projected_first_draw_close`='$first' WHERE `id` IN (".implode(',',$ids).");\n";
}


// Set chances
$qs = "
  SELECT
    `m`.`ClientRef`
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
        $crf     = $m['ClientRef'];
        $chances = explode (',',$m['ChancesCsv']);
        $chances = intval (trim(array_pop($chances)));
        if ($chances<1) {
            fwrite (STDERR,"$chances chances is not valid from ChancesCsv={$m['ChancesCsv']} at $crf\n");
            exit (103);
        }
        echo "UPDATE `blotto_player` SET `chances`=$chances WHERE `client_ref`='$crf';\n";
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}


// Set first_draw_close
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
$dates                      = array ();
try {
    $players                = $zo->query ($qs);
    fwrite (STDERR,"{$players->num_rows} players where first draw close not set\n");
    while ($p=$players->fetch_assoc()) {
        $date               = draw_first ($p['first_collected']);
        if (!array_key_exists($date,$dates)) {
            $dates[$date]   = array ();
        }
        array_push ($dates[$date],$p['id']);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}
echo "-- Update first draw dates\n";
foreach ($dates as $date=>$ids) {
    echo "UPDATE `blotto_player` SET `first_draw_close`='$date' WHERE `id` IN (".implode(',',$ids).");\n";
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


