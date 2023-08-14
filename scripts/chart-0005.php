<?php

// Cnvassing company performance (recent)

$since = $p[0];
$q = "
  SELECT
    `ccc`
   ,COUNT(`current_client_ref`) AS `imports`
  FROM (
    SELECT
      `ccc`
     ,`current_client_ref`
    FROM `Supporters`
    WHERE `Created`>='$since'
    GROUP BY `current_client_ref`
  ) AS `s`
  GROUP BY `ccc`
  ORDER BY `imports` DESC
  ;
";

try {
    $rows           = $zo->query ($q);
    $values         = [];
    $signed         = 0;
    while ($row=$rows->fetch_assoc()) {
        $values[]   = $row;
        $signed    += $row['imports'];
    }
    foreach ($values as $row) {
       $labels[]    = $row['ccc'];
       $data[0][]   = 1 * $row['imports'];
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Supporter imports';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

