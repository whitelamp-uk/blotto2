<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];
$org_id=BLOTTO_ORG_ID;
$mdb = BLOTTO_MAKE_DB;
$tdb = BLOTTO_TICKET_DB;
$csf = BLOTTO_DIR_EXPORT.'/checksum.blotto_ticket.txt';
$price = BLOTTO_TICKET_PRICE;
$rbe = get_argument('r',$Sw) !== false;


$zo = connect ($mdb);
if (!$zo) {
    exit (101);
}

$qc = "
  CHECKSUM TABLE `$tdb`.`blotto_ticket` EXTENDED;
";
try {
    $cks = $zo->query ($qc);
    $cks = $cks->fetch_assoc()['Checksum'];
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qc."\n".$e->getMessage()."\n");
    exit (102);
}
echo $qc.$cks."\n";
try {
    $fp = fopen ($csf,'w');
    fwrite ($fp,"$cks\n");
    fclose ($fp);
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (103);
}
tee ("    Ticket pool `$tdb`.`blotto_ticket` checksum $cks written to $csf\n");


if (defined('BLOTTO_TICKET_CHKSUM')) {

    $csu = BLOTTO_TICKET_CHKSUM;

    tee ("    Comparing checksum $cks with $csu\n");

    $options = array (
        CURLOPT_POST            => 0,
        CURLOPT_HEADER          => 0,
        CURLOPT_URL             => $csu,
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_FRESH_CONNECT   => 1,
        CURLOPT_RETURNTRANSFER  => 1,
        CURLOPT_FORBID_REUSE    => 1,
        CURLOPT_TIMEOUT         => 10,
    );    

    $crl = curl_init ();
    curl_setopt_array ($crl,$options);
    $chk = trim (curl_exec($crl));
    curl_close ($crl);
    if (!$chk) {
        fwrite (STDERR,"Checksum could not be fetched with cURL from $csu\n");
        fwrite (STDERR,"cURL error: #".curl_errno($crl)." ".curl_error($crl)."\n");
        exit (104);
    }
    if ($chk!=$cks) {
        fwrite (STDERR,"Checksum discrepancy between $csu=$chk and $csf=$cks\n");
        exit (105);
    }

}

tee ("    Looking for ticket inconsistencies between `dd_ref_no` and `client_ref`\n");

// This constraint cannot be done in SQL because, like quite a lot of blotto2,
// this table is not fully normalised - typically justified by efficiency needs.
// dd_ref_no:client_ref must be 1:1 but is not a unique key because
// dd_ref_no:number is 1:N
$qs = "
  SELECT
    `t1`.`dd_ref_no`
   ,`t1`.`client_ref`
   ,`t2`.`dd_ref_no`
   ,`t2`.`client_ref`
  FROM `$tdb`.`blotto_ticket` AS `t1`
  JOIN `$tdb`.`blotto_ticket` AS `t2`
    ON `t2`.`org_id`=`t1`.`org_id`
   AND (
         `t2`.`dd_ref_no`=`t1`.`dd_ref_no`
     AND `t2`.`client_ref`!=`t1`.`client_ref`
   )
    OR (
         `t2`.`client_ref`=`t1`.`client_ref`
     AND `t2`.`dd_ref_no`!=`t1`.`dd_ref_no`
    )
  WHERE `t1`.`org_id`=$org_id
  LIMIT 0,1
  ;
";
try {
    $d = $zo->query ($qs);
    if ($d->num_rows) {
        $d = $d->fetch_assoc ();
        fwrite (STDERR,$qs."\n".print_r($d,true)."\n");
        fwrite (STDERR,"`blotto_ticket` has inconsistencies\n");
        exit (106);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (107);
}



if ($rbe) {
    exit (0);
}

tee ("    Looking for discrepancies between player chances and number of tickets (standard game check only)\n");

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
         ON `t`.`dd_ref_no`=`m`.`RefNo`
        AND `t`.`client_ref`=`p`.`client_ref`
  WHERE `p`.`first_draw_close` IS NOT NULL
  GROUP BY `p`.`client_ref`
  HAVING `tickets`!=`chances`
      OR (                             `chances`!=0  AND   `amount`=0       )
      OR ( `frequency`='Monthly'   AND `chances`=1   AND   `amount`!=ROUND($price*434/10000,2)   )
      OR ( `frequency`='Monthly'   AND `chances`=2   AND   `amount`!=ROUND($price*868/10000,2)   )
      OR ( `frequency`='Monthly'   AND `chances`=3   AND ( `amount`<ROUND($price*1300/10000,2)  OR  `amount`>ROUND($price*1302/10000,2) ) )
      OR ( `frequency`='Monthly'   AND `chances`=4   AND   `amount`!=ROUND($price*1736/10000,2)  )
      OR ( `frequency`='Annually'  AND `chances`=1   AND   `amount`!=ROUND($price*5200/10000,2)  )
      OR ( `frequency`='Annually'  AND `chances`=2   AND   `amount`!=ROUND($price*10400/10000,2) )
      OR ( `frequency`='Annually'  AND `chances`=3   AND   `amount`!=ROUND($price*15600/10000,2) )
      OR ( `frequency`='Annually'  AND `chances`=4   AND   `amount`!=ROUND($price*20800/10000,2) )
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
    exit (108);
}

if ($c=count($players)) {
    echo "TICKET DISCREPANCIES\n";
    echo $qs;
    print_r ($players);
    fwrite (STDERR,"$c players have ticket discrepancies (see log)\n");
    exit (109);
}


