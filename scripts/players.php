<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

echo "-- Insert new players\n";


$splitter = BLOTTO_CREF_SPLITTER;
$qs = "
  SELECT
    `m`.`ClientRef`
   ,`m`.`ChancesCsv`
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
    if (!$m['Created'] || $m['Created']=='0000-00-00') {
        fwrite (STDERR,"Mandate for {$m['ClientRef']} does not have a valid Created column\n");
        exit (103);
    }
    $started = $m['Created'];
    $crf     = $m['ClientRef'];
    $crforig = explode ($splitter,$crf);
    $crforig = $crforig[0];
    $crf     = esc ($crf);
    $crforig = esc ($crforig);
    $chances = explode (',',$m['ChancesCsv']);
    $chances = intval (trim(array_pop($chances)));
    if (!preg_match('<^[0-9]+$>',$chances)) {
        fwrite (STDERR,"Mandate for {$m['ClientRef']} does not have valid chances in its ChancesCsv column\n");
        exit (103);
    }
    $qs = "
      SELECT `id`
      FROM `blotto_supporter`
      WHERE `client_ref`='$crforig'
      LIMIT 0,1
      ;
    ";
    try {
        $orig = $zo->query ($qs);
        if ($s=$orig->fetch_assoc()) {
            echo "INSERT INTO `blotto_player` (`started`,`supporter_id`,`client_ref`,`chances`) VALUES\n";
            echo "  ('$started',{$s['id']},'$crf',$chances);\n";
            continue;
        }
        fwrite (STDERR,"No supporter found for original ClientRef '$crforig' to create player for '$crf'\n");
        exit (104);
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (105);
    }
}

