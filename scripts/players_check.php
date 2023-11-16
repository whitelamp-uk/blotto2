<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}


// Final players check
$splitter = BLOTTO_CREF_SPLITTER;
$qs = "
  SELECT
    `c`.`ClientRef`
   ,COUNT(`c`.`DateDue`) AS `payments`
   ,SUM(`c`.`PaidAmount`) AS `paid`
   ,`p`.`id` AS `player_id`
   ,`p`.`chances`
   ,`p`.`started`
   ,`s`.`mandate_blocked`
  FROM `blotto_build_collection` AS `c`
  -- It is now assumed that there must be a supporter so not a left join
  JOIN `blotto_supporter` AS `s`
         ON `s`.`client_ref`=`c`.`ClientRef`
         OR `c`.`ClientRef` LIKE CONCAT(`s`.`client_ref`,'$splitter%')
  LEFT JOIN `blotto_player` AS `p`
         ON `c`.`ClientRef`=`p`.`client_ref`
  WHERE `p`.`chances` IS NULL
     OR `p`.`started` IS NULL
     OR `p`.`started`='0000-00-00'
  GROUP BY `c`.`RefNo`
  HAVING `mandate_blocked`=0
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


