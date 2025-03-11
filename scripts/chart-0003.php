<?php

// Recent recruitment and cancellation

$t0    = time ();
$me    = $p[0];
$cum   = array_key_exists(1,$p) && $p[1];
$data  = [[],[],[]];
$q = "
  SELECT
    `r`.`month`
   ,`r`.`recruits`
   ,`c`.`cancellations`
  FROM (
    SELECT
      SUBSTR(`m`.`Created`,1,7) AS `month`
     ,COUNT(`m`.`ClientRef`) AS `recruits`
    FROM `blotto_build_mandate` as `m`
    -- One-off payments are not viewed as retentions
    WHERE `m`.`Freq`!='Single'
    GROUP BY `month`
  ) AS `r`
  JOIN (
    SELECT
      SUBSTR(`cancelled_date`,1,7) AS `month`
     ,COUNT(DISTINCT `client_ref`) AS `cancellations`
    -- One-off payments are not added to cancellations table
    FROM `Cancellations`
    GROUP BY `month`
  ) AS `c`
    ON `c`.`month`=`r`.`month`
  WHERE `r`.`month`<SUBSTR(CURDATE(),1,7)
  {{WHERE}}
  ORDER BY `r`.`month`
";
if ($me) {
    $where = "  AND `r`.`month`<=SUBSTR('$me',1,7) AND `r`.`month`>SUBSTR(DATE_SUB('$me',INTERVAL 12 MONTH),1,7)";
}
else {
    $where = "";
}
$q = str_replace ('{{WHERE}}',$where,$q);
try {
    $rows       = $zo->query ($q);
    $recruits   = 0;
    $cancels    = 0;
    while ($row=$rows->fetch_assoc()) {
        $dt = new DateTime ($row['month'].'-01');
        array_push ($labels,$dt->format('M Y'));
        if ($cum) {
            $recruits += $row['recruits'];
            $cancels  += $row['cancellations'];
            array_push ($data[0],1*$recruits);
            array_push ($data[1],1*$cancels);
            array_push ($data[2],$recruits-$cancels);
         }
        else {
            array_push ($data[0],1*$row['recruits']);
            array_push ($data[1],1*$row['cancellations']);
            array_push ($data[2],$row['recruits']-$row['cancellations']);
        }
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Supporters recruited';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = 'Cancellations';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = 2;
    $cdo->datasets[2] = new stdClass ();
    $cdo->datasets[2]->label = 'Nett';
    $cdo->datasets[2]->data = $data[2];
    $cdo->datasets[2]->backgroundColor = 3;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

