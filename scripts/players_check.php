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
    `c`.`ClientRef`
   ,COUNT(`c`.`DateDue`) AS `payments`
   ,SUM(`c`.`PaidAmount`) AS `paid`
   ,`p`.`id` AS `player_id`
   ,`p`.`chances`
   ,`p`.`started`
  FROM `blotto_build_collection` AS `c`
  LEFT JOIN `blotto_player` AS `p`
    ON `c`.`ClientRef`=`p`.`client_ref`
  WHERE `p`.`chances` IS NULL
     OR `p`.`started` IS NULL
     OR `p`.`started`='0000-00-00'
  GROUP BY `c`.`RefNo`
  ;
";
try {
    $errors = $zo->query ($qs);
    if ($errors->num_rows) {
      fwrite (STDERR,$errors->num_rows." players have errors!\n");
      fwrite (STDERR,$qs);
      exit (102);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}


