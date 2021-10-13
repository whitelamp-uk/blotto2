<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}


// Final players check
$qs = "
  SELECT
    COUNT(`m`.`RefNo`) AS `total`
   ,COUNT(`p`.`id` IS NULL) AS `missing`
   ,COUNT(`p`.`chances` IS NULL)-COUNT(`p`.`id` IS NULL) AS `no_chances`
   ,COUNT(`p`.`started` IS NULL OR `p`.`started`='0000-00-00')-COUNT(`p`.`id` IS NULL) AS `no_start_date`
   ,GROUP_CONCAT(`m`.`ClientRef`) AS `refs`
  FROM `blotto_build_mandate` AS `m`
  LEFT JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`m`.`ClientRef`
  WHERE `p`.`chances` IS NULL
     OR `p`.`started` IS NULL
     OR `p`.`started`='0000-00-00'
  ;
";
try {
    $errors = $zo->query ($qs);
    $errors = $errors->fetch_assoc ();
    if ($errors['total']) {
        fwrite (STDERR,"    ".$errors['total']." player errors:\n");
        fwrite (STDERR,"    ".$errors['missing']." mandates are missing a player\n");
        fwrite (STDERR,"    ".$errors['no_chances']." players have no chances\n");
        fwrite (STDERR,"    ".$errors['no_start_date']." players have no start date\n");
        fwrite (STDERR,$qs);
        exit (102);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}


