<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

// Final players check
$splitter = BLOTTO_CREF_SPLITTER; // usually '-' (dash)
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

// Check if we have e.g. BB1111_310829 and BB1111_310829-0001 both LIVE 
// TODO? make list of active statuses a global config
$qs = "
      SELECT 
        `m1`.`ClientRef` AS `CR1`
       ,`m1`.`Status`    AS `S1`
       ,`m2`.`ClientRef` AS `CR2`
       ,`m2`.`Status`    AS `S2`
      FROM   `blotto_build_mandate` AS `m1`
      JOIN `blotto_build_mandate` AS `m2`
       ON `m2`.`ClientRef` = SUBSTRING_INDEX(`m1`.`ClientRef`,'$splitter',1)
      AND `m2`.`Status` = `m1`.`Status`
      WHERE  `m1`.`ClientRef` LIKE '%$splitter%' 
        AND  `m1`.`Status` IN ('Active', 'LIVE')
  ;
";
try {
    $dupes = $zo->query ($qs);
    if ($dupes->num_rows) {
        $subj = "Warning: ".strtoupper(BLOTTO_ORG_USER)." has ".$dupes->num_rows." player(s) with uncancelled mandates";
        $mailbody = "Looks like we have two live players / mandates for one supporter with the following ClientRefs\n";
        $mailbody .= "Probably not what we want\n";
        $mailbody .= "Ref : Status - Ref : Status\n";

        foreach ($dupes as $dupe) {
            $mailbody .= $dupe['CR1'].' : '.$dupe['S1'].' - '.$dupe['CR2'].' : '.$dupe['S2']."\n";
        }
        mail(BLOTTO_EMAIL_WARN_TO, $subj, $mailbody);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}



