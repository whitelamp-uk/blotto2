<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$cc_notify_interval = BLOTTO_CC_NOTIFY;

echo "-- New changes data for canvassing companies\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

$qs = "
  SELECT
    IFNULL(`cln1`.`updated`,`s`.`created`) AS `date_from`
   ,`s`.`signed`
   ,`s`.`approved`
   ,`s`.`created`
   ,`s`.`client_ref`
   ,`s`.`canvas_code`
   ,`s`.`canvas_agent_ref`
   ,`s`.`canvas_ref`
   ,`s`.`projected_chances` AS `chances_orig`
   ,DATE_ADD(
      IFNULL(`cln1`.`updated`,`s`.`created`)
     ,INTERVAL $cc_notify_interval
    ) AS `last_interested`
   ,`u`.*
   ,GROUP_CONCAT(CONCAT_WS('::',`p`.`id`,`p`.`chances`,`p`.`client_ref`) ORDER BY `p`.`id` SEPARATOR ';;') AS `players`
  FROM `blotto_update` AS `u`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`u`.`supporter_id`
  JOIN `blotto_player` AS `p`
    ON `p`.`supporter_id`=`s`.`id`
   -- TODO: null chances implies non-DD/one-off/not CSV import or something
   -- like one of those things - ie not relevant to CCR
   AND `p`.`chances` IS NOT NULL
  LEFT JOIN `blotto_update` AS `cln1`
         ON `cln1`.`supporter_id`=`u`.`supporter_id`
        AND `cln1`.`milestone`='first_collection'
  -- Too much data to keep repeating oneself
  LEFT JOIN (
    SELECT
      `update_id`
    FROM `blotto_change`
    GROUP BY `update_id`
  )      AS `c`
         ON `c`.`update_id`=`u`.`id`
  WHERE `c`.`update_id` IS NULL
  GROUP BY `u`.`id`
  HAVING `u`.`updated`<=`last_interested`
  ;
";
try {
    $us = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}

$changes = [];
while ($u=$us->fetch_assoc()) {
    $u['player_old_chances'] = 0;
    $u['player_chances'] = 0;
    $u['is_termination'] = 0;
    $u['player_client_ref'] = '';
    $players = explode (';;',$u['players']);
    foreach ($players as $i=>$p) {
        $p = explode ('::',$p);
        if ($p[0]==$u['player_id']) {
            $u['player_chances'] = $p[1];
            $u['player_client_ref'] = $p[2];
            break;
        }
        $u['player_old_chances'] = $p[1];
    }
    if ($u['milestone']=='created') {
        $u['increment'] = $u['chances_orig'];
    }
    elseif ($u['milestone']=='first_collection') {
        // The actual first player chances is not synonoymous with sign-up chances
        $u['increment'] = $u['player_chances'] - $u['chances_orig'];
    }
    elseif ($u['milestone']=='bacs_change') {
        // Old player loses all old chances as new players gets some
        $u['increment'] = $u['player_chances'] - $u['player_old_chances'];
    }
    elseif ($u['milestone']=='cancellation') {
        $u['increment'] = 0 - $u['player_chances'];
        $u['is_termination'] = 1;
    }
    elseif ($u['milestone']=='reinstatement') {
        $u['increment'] = $u['player_chances'];
    }
    else {
        // The milestone is not relevant to CCRs
        continue;
    }
    if ($u['increment']!=0) {
        $count = abs ($u['increment']);
        if ($u['increment']>0) {
            $u['type_is_increment'] = 1;
            $u['type'] = 'INC';
            for ($i=0;$i<$count;$i++) {
                $u['chance_number'] = $i + 1;
                $u['chance_ref'] = $u['canvas_ref'].'-'.$u['chance_number'];
                array_push ($changes,$u);
            }
        }
        else {
            $u['type_is_increment'] = 0;
            $u['type'] = 'DEC';
            for ($i=0;$i<$count;$i++) {
                $u['chance_number'] = $count - $i;
                $u['chance_ref'] = $u['canvas_ref'].'-'.$u['chance_number'];
                array_push ($changes,$u);
            }
        }
        $qs = "
          SELECT
            SUM(`PaidAmount`) AS `amount_paid_at_update`
          FROM `blotto_build_collection`
          WHERE `ClientRef`='{$u['player_client_ref']}'
            AND `DateDue`<='{$u['date_from']}'
          ;
        ";
        try {
            $amt = $zo->query ($qs);
            $u['amount_paid_at_update'] = $amt->fetch_assoc() ['amount_paid_at_update'];
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
            exit (105);
        }
    }
    else {
        // This does sometimes happen eg:
        // DBH BB6919_163438 x1  --->  BB6919_163438-0001 x1
    }
}


if (!count($changes)) {
    fwrite (STDERR,"No change data to write to blotto_change\n");
    exit (0);
}

echo "
INSERT INTO `blotto_change` (
  `changed_date`
 ,`client_ref`
 ,`ccc`
 ,`canvas_agent_ref`
 ,`signed`
 ,`approved`
 ,`created`
 ,`canvas_ref`
 ,`chance_number`
 ,`chance_ref`
 ,`type`
 ,`type_is_increment`
 ,`is_termination`
 ,`chances_orig`
 ,`supporter_id`
 ,`update_id`
 ,`milestone`
) VALUES
";

foreach ($changes as $i=>$c) {
    echo "('{$c['updated']}','{$c['client_ref']}','{$c['canvas_code']}','{$c['canvas_agent_ref']}','{$c['signed']}','{$c['approved']}','{$c['created']}','{$c['canvas_ref']}',{$c['chance_number']},'{$c['chance_ref']}','{$c['type']}',{$c['type_is_increment']},{$c['is_termination']},{$c['chances_orig']},{$c['supporter_id']},{$c['id']},'{$c['milestone']}')";
    if (array_key_exists($i+1,$changes)) {
        echo ",";
    }
    echo "\n";
}

echo ";\n\n";

