<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$cc_notify_interval = BLOTTO_CC_NOTIFY;

function insert_changes ($zo,$changes) {
    $rows = 0;
    if (!is_array($changes)) {
        fwrite (STDERR,"insert_changes(): not given an array\n");
        exit (101);
    }
    if (!count($changes)) {
        return $rows;
    }
    $insert = "
      INSERT IGNORE INTO `blotto_change`
      (`changed_date`,`ccc`,`agent_ref`,`canvas_ref`,`chance_number`,`client_ref_original`,`type`,`is_termination`,`reinstatement_for`,`amount_paid_before_this_date`,`supporter_signed`,`supporter_approved`,`supporter_created`,`supporter_first_paid`)
      VALUES
    ";
    $count = 0;
    $qi = $insert;
    foreach ($changes as $c) {
        $qi .= "('".implode("','",$c)."'),";
        $count++;
        if ($count==BLOTTO_ROWS_PER_QRY) {
            try {
                $zo->query (substr($qi,0,-1));
                $rs = $zo->query ('SELECT ROW_COUNT() AS `rows`');
                $rows += $rs->fetch_assoc()['rows'];
            }
            catch (\mysqli_sql_exception $e) {
                fwrite (STDERR,$insert." ...\n".$e->getMessage()."\n");
                exit (102);
            }
            $count = 0;
            $qi = $insert;
        }
    }
    if ($count) {
        try {
            $zo->query (substr($qi,0,-1));
            $rs = $zo->query ('SELECT ROW_COUNT() AS `rows`');
            $rows += $rs->fetch_assoc()['rows'];
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$insert." ...\n".$e->getMessage()."\n");
            exit (102);
        }
    }
    return $rows;
}

function push_changes (&$chs,$date,$qty_before,$qty_after,$amt,$s,$is_termination=0,$reinstatement_for='') {
    if ($qty_before==$qty_after) {
        return;
    }
    if ($qty_before<$qty_after) {
        for ($i=$qty_before+1;$i<=$qty_after;$i++) {
            array_push (
                $chs,
                array (
                    $date,
                    $s['ccc'],
                    $s['agent_ref'],
                    $s['canvas_ref'],
                    $i,
                    $s['client_ref_original'],
                    'INC',
                    $is_termination,
                    $reinstatement_for,
                    $amt,
                    $s['supporter_signed'],
                    $s['supporter_approved'],
                    $s['supporter_created'],
                    $s['supporter_first_paid']
                )
            );
        }
        return;
    }
    for ($i=$qty_before;$i>$qty_after;$i--) {
        array_push (
            $chs,
            array (
                $date,
                $s['ccc'],
                $s['agent_ref'],
                $s['canvas_ref'],
                $i,
                $s['client_ref_original'],
                'DEC',
                $is_termination,
                $reinstatement_for,
                $amt,
                $s['supporter_signed'],
                $s['supporter_approved'],
                $s['supporter_created'],
                $s['supporter_first_paid']
            )
        );
    }
}



echo "    Generating changes for canvassing companies\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}


// Get results from changesGenerate()
$qs = "
  SELECT
    *
  FROM `tmp_changes_by_supporter`
  ORDER BY `supporter_created` 
  ;   
";
try {
    $ss = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}
$qs = "
  SELECT
    *
  FROM `tmp_changes_termination`
  ORDER BY `Cancelled_Date` 
  ;   
";
try {
    $ts = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (105);
}


$supporters = [];
$terminations = [];
while ($s=$ss->fetch_assoc()) {
    $ps = explode (';;',$s['players']);
    $s['players'] = [];
    foreach ($ps as $i=>$p) {
        $p = explode ('::',$p);
        if (!array_key_exists(5,$p)) {
            fwrite (STDERR,"Player has too few properties: ".print_r($p,true)."\n");
            exit (106);
        }
        $s['players'][$p[0]] = array (
            'created' => $p[0],
            'client_ref' => $p[1],
            'chances' => $p[2],
            'first_payment' => $p[3],
            'last_payment' => $p[4],
            'amount_paid' => $p[5]
        );
    }
    ksort ($s['players']);
    array_push ($supporters,$s);
}
while ($t=$ts->fetch_assoc()) {
    array_push ($terminations,$t);
}


$changes = [];
echo "    ".count($supporters)." supporter changes\n";
foreach ($supporters as $s) {
    $chs                = 0;
    $amount             = 0;
    foreach ($s['players'] as $p) {
        push_changes ($changes,$p['created'],$chs,$p['chances'],$amount,$s);
        $chs            = $p['chances'];
        $amount        += $p['amount_paid'];
    }
}
echo "    ".count($terminations)." termination changes\n";
foreach ($terminations as $t) {
    push_changes ($changes,$t['Cancelled_Date'],$t['chances'],0,$t['amount_paid'],$t,1);
}
echo "    ".insert_changes($zo,$changes)." new supporter/termination rows (1 per chance)\n";


/*
    If a change with is_termination > 0:
     * has related collection(s) after its change date
     * is not related to a reinstatement change
           then it needs one inserting with:
            * change date = first post-termination collection date
            * reinstatement_for = change date of the termination
*/

$qs = "
  SELECT
    `t`.*
   ,MIN(`r`.`DateDue`) AS `reinstatement_date`
  FROM (
    SELECT
      `ct`.`changed_date`
     ,`ct`.`ccc`
     ,`ct`.`agent_ref`
     ,`ct`.`canvas_ref`
     ,MAX(`ct`.`chance_number`) AS `chances` 
     ,`ct`.`client_ref_original`
     ,`ct`.`supporter_signed`
     ,`ct`.`supporter_approved`
     ,`ct`.`supporter_created`
     ,`ct`.`supporter_first_paid`
     ,`ct`.`amount_paid_before_this_date`
    FROM `blotto_change` AS `ct`
    LEFT JOIN `blotto_change` AS `cr`
           ON `cr`.`ccc`=`ct`.`ccc`
          AND `cr`.`canvas_ref`=`ct`.`canvas_ref`
          AND `cr`.`chance_number`=`ct`.`chance_number`
          AND `cr`.`reinstatement_for`=`ct`.`changed_date`
    WHERE `ct`.`is_termination`>0
      AND `cr`.`changed_date` IS NOT NULL
    GROUP BY `ct`.`changed_date`,`ct`.`ccc`,`ct`.`canvas_ref`
  ) AS `t`
  JOIN (
    SELECT
      `s`.`client_ref` AS `client_ref_original`
     ,`c`.`DateDue`
    FROM `blotto_supporter` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
    JOIN `blotto_build_collection` AS `c`
      ON `c`.`ClientRef`=`p`.`client_ref`
  ) AS `r`
    ON `r`.`client_ref_original`=`t`.`client_ref_original`
   AND `r`.`DateDue`<=DATE_ADD(
          IF(
            `t`.`supporter_first_paid` IS NULL
           ,`t`.`supporter_created`
           ,`t`.`supporter_first_paid`
           )
          ,INTERVAL $cc_notify_interval
        )
   AND `r`.`DateDue`>=`t`.`changed_date`
  GROUP BY `t`.`changed_date`,`t`.`ccc`,`t`.`canvas_ref`
  ;
";

try {
    $cs = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (107);
}


$changes = [];
while ($t=$ts->fetch_assoc()) {
    push_changes ($changes,$t['reinstatement_date'],0,$t['chances'],$t['amount_paid_before_this_date'],$t,0,$t['changed_date']);
}
echo "    ".count($changes)." reinstatement changes\n";
echo "    ".insert_changes($zo,$changes)." new reinstatement rows (1 per chance)\n";

$qd = "
  DELETE FROM `blotto_change`
  WHERE `changed_date`='0000-00-00'
     OR `changed_date` IS NULL
  ;
";
try {
    $zo->query ($qd);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qd."\n".$e->getMessage()."\n");
    exit (108);
}

$qu = "
  UPDATE `blotto_change`
  SET
    `supporter_first_paid`=null
  WHERE `supporter_first_paid`='0000-00-00'
  ;
";
try {
    $zo->query ($qu);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qu."\n".$e->getMessage()."\n");
    exit (109);
}

