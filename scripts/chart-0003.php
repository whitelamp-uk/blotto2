<?php

// Recent recruitment and cancellation

$cumulative = array_key_exists(0,$p) && $p[0];
$data       = [[],[],[]];
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
  ORDER BY `r`.`month`
";

try {
    $rows       = $zo->query ($q);
    $recruits   = 0;
    $cancels    = 0;
    while ($row=$rows->fetch_assoc()) {
        $dt = new DateTime ($row['month'].'-01');
        array_push ($labels,$dt->format('M Y'));
        if ($cumulative) {
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
    if ($type=='graph') {
        $labels  = array_slice ($labels,-6);
        $data[0] = array_slice ($data[0],-6);
        $data[1] = array_slice ($data[1],-6);
        $data[2] = array_slice ($data[2],-6);
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Supporters recruited';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[0]->stack = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = 'Cancellations';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = 2;
    $cdo->datasets[1]->stack = 2;
    $cdo->datasets[2] = new stdClass ();
    $cdo->datasets[2]->label = 'Nett';
    $cdo->datasets[2]->data = $data[2];
    $cdo->datasets[2]->backgroundColor = 3;
    $cdo->datasets[2]->stack = 3;



    $cdo->datasets[3] = $cdo->datasets[0];
    $cdo->datasets[3]->backgroundColor = 6;
    $cdo->datasets[3]->stack = 3;
/*

    $cdo->datasets[4] = $cdo->datasets[0];
    $cdo->datasets[4]->backgroundColor = 5;
    $cdo->datasets[4]->stack = 1;

    $cdo->datasets[5] = $cdo->datasets[0];
    $cdo->datasets[5]->backgroundColor = 6;
    $cdo->datasets[5]->stack = 2;
*/

}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

