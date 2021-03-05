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
    `p`.`id`
  FROM `blotto_player` AS `p`
  JOIN `blotto_build_mandate` AS `m`
    ON `m`.`ClientRef`=`p`.`client_ref`
  WHERE `p`.`chances` IS NULL
     OR `p`.`started` IS NULL
     OR `p`.`started`='0000-00-00'
  ;
";
try {
    $errors = $zo->query ($qs);
    if ($errors->num_rows) {
      fwrite (STDERR,$qs.$errors->num_rows." errors!\n");
      exit (102);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}


