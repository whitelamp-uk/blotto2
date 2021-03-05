<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];
$mdb = BLOTTO_MAKE_DB;
$tdb = BLOTTO_TICKET_DB;
$csf = BLOTTO_DIR_EXPORT.'/checksum.blotto_ticket.txt';

$zo = connect ($mdb);
if (!$zo) {
    exit (101);
}

$qc = "CHECKSUM TABLE `$tdb`.`blotto_ticket` EXTENDED";
try {
    $cks = $zo->query ($qc);
    $cks = $cks->fetch_assoc()['Checksum'];
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qc."\n".$e->getMessage()."\n");
    exit (102);
}
try {
    $fp = fopen ($csf,'w');
    fwrite ($fp,"$cks\n");
    fclose ($fp);
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (103);
}
tee ("    Ticket pool checksum $cks written to $csf\n");


tee ("    Looking for discrepancies between player chances and number of tickets\n");

$qs = "
  SELECT
    `p`.*
   ,IFNULL(
     `m`.`Freq`
     ,''
    ) AS `frequency`
   ,IFNULL(
     `m`.`Amount`
     ,0
    ) AS `amount`
   ,IFNULL(
      COUNT(`t`.`number`)
     ,0) AS `tickets`
   ,IFNULL(
      GROUP_CONCAT(`t`.`number` ORDER BY `t`.`number` DESC SEPARATOR ',')
     ,''
    ) AS `ticket_numbers`
  FROM `$mdb`.`blotto_player` AS `p`
  JOIN `$mdb`.`blotto_build_mandate` AS `m`
    ON `m`.`ClientRef`=`p`.`client_ref`
  LEFT JOIN `$tdb`.`blotto_ticket` AS `t`
         ON `t`.`client_ref`=`p`.`client_ref`
  WHERE `p`.`first_draw_close` IS NOT NULL
  GROUP BY `p`.`client_ref`
  HAVING `tickets`!=`chances`
      OR (                             `chances`!=0  AND   `amount`=0       )
      OR ( `frequency`='Monthly'   AND `chances`=1   AND   `amount`!=4.34   )
      OR ( `frequency`='Monthly'   AND `chances`=2   AND   `amount`!=8.68   )
      OR ( `frequency`='Monthly'   AND `chances`=3   AND ( `amount`<13.00  OR  `amount`>13.02 ) )
      OR ( `frequency`='Monthly'   AND `chances`=4   AND   `amount`!=17.36  )
      OR ( `frequency`='Annually'  AND `chances`=1   AND   `amount`!=52.00  )
      OR ( `frequency`='Annually'  AND `chances`=2   AND   `amount`!=104.00 )
      OR ( `frequency`='Annually'  AND `chances`=3   AND   `amount`!=156.00 )
      OR ( `frequency`='Annually'  AND `chances`=4   AND   `amount`!=208.00 )
  ;
";

$players = [];
try {
    $ps = $zo->query ($qs);
    while ($p=$ps->fetch_assoc()) {
        array_push ($players,$p);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}

if ($c=count($players)) {
    echo "TICKET DISCREPANCIES\n";
    echo $qs;
    print_r ($players);
    fwrite (STDERR,"$c players have ticket discrepancies (see log)\n");
    exit (105);
}

