<?php

// Tickets per player

$t0    = time ();
$me    = $p[0];
$data  = [[],[]];
$q = "
  SELECT
    `chances`.`projected_chances` as `tickets`
   ,COUNT(`chances`.`id`) AS `players`
  FROM (
    SELECT
      `id`
     ,`projected_chances`
    FROM `blotto_supporter`
    WHERE `signed`<CURDATE()
    {{WHERE}}
      AND `signed` IS NOT NULL
  ) AS `chances`
  GROUP BY `tickets`
  ORDER BY `tickets`
  ;
";
if ($me) {
    $where = "  AND `signed`<='$me' AND `signed`>DATE_SUB('$me',INTERVAL 12 MONTH)";
}
else {
    $where = "";
}
$q = str_replace ('{{WHERE}}',$where,$q);
try {
    $rows           = $zo->query ($q);
    $values         = [];
    $total          = 0;
    while ($row=$rows->fetch_assoc()) {
        $values[]   = $row;
        $total     += $row['players'];
    }
    foreach ($values as $row) {
       $label       = number_format(100*$row['players']/$total,2).'% of players';
       $label      .= ' with '.$row['tickets'].' ticket'.plural($row['tickets']);
       $labels[]    = $label;
       $data[0][]   = 1 * $row['players'];
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Players by nr of chances';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 0;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

