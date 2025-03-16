<?php

// Cumulative recruitment and cancellation

$t0         = time ();
$bw         = $p[0];
$cancels    = array_key_exists(1,$p) && $p[1];
$dbcfg      = BLOTTO_CONFIG_DB;
$interval   = BLOTTO_CANCEL_RULE;
$q = "
  SELECT
    TIMESTAMPDIFF(
      MONTH
     ,IFNULL(MIN(`created`),CURDATE())
     ,CURDATE()
    ) AS `game_age`
  FROM `blotto_supporter`
  ;
";
try {
    $game_age   = $zo->query ($q);
    $game_age   = $game_age->fetch_assoc ();
    $game_age   = $game_age['game_age'];
    $q = "
      SELECT
        `m`.`months`
       ,COUNT(`m`.`client_ref`) AS `quantity`
      FROM (
        SELECT
          `s`.`client_ref`
         ,IF(
            `c`.`DateDue` IS NULL
           ,0
           ,TIMESTAMPDIFF(MONTH,MIN(`c`.`DateDue`),MAX(`c`.`DateDue`)) + 1
         ) AS `months`
        FROM `blotto_supporter` AS `s`
        JOIN `blotto_player` AS `p`
          ON `p`.`supporter_id`=`s`.`id`
        LEFT JOIN (
          SELECT
            `cm`.`ClientRef`
           ,`cm`.`Freq`
           ,`cc`.`DateDue`
          FROM `blotto_build_mandate` AS `cm`
          LEFT JOIN `blotto_build_collection` AS `cc`
                 ON `cc`.`Provider`=`cm`.`Provider`
                AND `cc`.`RefNo`=`cm`.`RefNo`
        ) AS `c`
          ON `c`.`ClientRef`=`p`.`client_ref`
        LEFT JOIN (
          SELECT
            `client_ref`
          FROM `Cancellations`
          GROUP BY `client_ref`
        ) AS `cs`
          ON `cs`.`client_ref`=`s`.`client_ref`
        WHERE {{WHERE}}
          AND `c`.`Freq`!='Single'
        GROUP BY `s`.`id`
      ) AS `m`
      GROUP BY `m`.`months`
      ORDER BY `m`.`months` {{DESC}}
    ";
    if ($cancels) {
        $where  = "`cs`.`client_ref` IS NOT NULL";
        $desc   = "";
    }
    else {
        $where  = "`c`.`DateDue`<DATE_SUB(CURDATE(),INTERVAL $interval) AND `cs`.`client_ref` IS NULL";
        $desc   = "DESC";
    }
    $q = str_replace ('{{WHERE}}',$where,$q);
    $q = str_replace ('{{DESC}}',$desc,$q);

    $rows = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
        if ($row['months']==0) {
            array_push ($labels,'Never played');
        }
        else {
            array_push ($labels,$row['months'].' month'.plural($row['months']));
        }
        array_push ($data[0],1*$row['quantity']);
    }
    $cdo->labels        = $labels;
    $cdo->datasets      = [];
    $cdo->datasets[0]   = new stdClass ();
    if ($cancels) {
        $cdo->datasets[0]->label = 'Cancelled supporter retention';
    }
    else {
        $cdo->datasets[0]->label = 'Current supporter retention';
    }
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    if ($cancels) {
        $cdo->datasets[0]->backgroundColor = 2;
    }
    $cdo->game_age = $game_age;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

