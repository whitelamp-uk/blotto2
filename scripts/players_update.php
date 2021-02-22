<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

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
    fwrite (STDERR,"{$players->num_rows} players where first draw close is not set\n");
    while ($p=$players->fetch_assoc()) {


    $chances = explode (',',$m['ChancesCsv']);
    $chances = intval (trim(array_pop($chances)));
    if (!preg_match('<^[0-9]+$>',$chances)) {
        fwrite (STDERR,"Mandate for {$m['ClientRef']} does not have valid chances in its ChancesCsv column\n");
        exit (103);
    }


        $date               = draw_first ($p['first_collected']);
        if (!array_key_exists($date,$dates)) {
            $dates[$date]   = array ();
        }
        array_push ($dates[$date],$p['id']);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
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
";
try {
    $errors = $zo->query ($qs);
    if ($errors->num_rows) {
      fwrite (STDERR,$qs."\nplayers_update.php: this should not happen!\n");
      exit (103);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}


