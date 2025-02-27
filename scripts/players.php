<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

echo "-- Insert new players for existing supporters\n";


$splitter = BLOTTO_CREF_SPLITTER;
$qs = "
  SELECT
    `m`.`ClientRef`
   ,`m`.`Created`
  FROM `blotto_build_mandate` AS `m`
  LEFT JOIN `blotto_player` AS `p`
         ON `p`.`client_ref`=`m`.`ClientRef`
  WHERE `m`.`ClientRef` LIKE '_%$splitter%_'
    AND `m`.`ClientRef` REGEXP '[^0-9][0-9][0-9][0-9][0-9]$'
    AND `p`.`id` IS NULL
";
try {
    $ms = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}


while ($m=$ms->fetch_assoc()) {
    $crf     = $m['ClientRef'];
    $crforig = explode ($splitter,$crf);
    $crforig = $crforig[0];
    $crf     = esc ($crf);
    $crforig = esc ($crforig);
    $qs = "
      SELECT
        `id`
       ,`mandate_blocked`
      FROM `blotto_supporter`
      WHERE `client_ref`='$crforig'
      LIMIT 0,1
      ;
    ";
    try {
        $orig = $zo->query ($qs);
        if ($s=$orig->fetch_assoc()) {
            if ($s['mandate_blocked']) {
                fwrite (STDERR,"Player not required for blocked supporter with original ClientRef '$crforig'\n");
            }
            else {
                echo "INSERT INTO `blotto_player` (`supporter_id`,`client_ref`) VALUES\n";
                echo "  ({$s['id']},'$crf');\n";
            }
        } else {
            fwrite (STDERR,"No supporter found for original ClientRef '$crforig' to create player for '$crf'\n");
            exit (104);
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (105);
    }
}

