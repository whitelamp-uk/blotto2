<?php

// Cumulative canvassing company activity

// Show-off with a double-dataset doughnut
$data       = [[],[],[]];
$blank      = 'rgba(255,255,255,1)';
$q = "
  SELECT
    `ccc`
   ,COUNT(`current_client_ref`) AS `imports`
   ,SUM(`cancelled`='') AS `retained`
  FROM (
    SELECT
      `ccc`
     ,`current_client_ref`
     ,`cancelled`
    FROM `Supporters`
    GROUP BY `current_client_ref`
  ) AS `s`
  GROUP BY `ccc`
  ORDER BY `imports` DESC
  ;
";
try {
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->backgroundColor = [];
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->backgroundColor = [];
    $cdo->datasets[2] = new stdClass ();
    $cdo->datasets[2]->backgroundColor = [];
    $color          = 0;
    $rows           = $zo->query ($q);
    $rs             = [];
    while ($row=$rows->fetch_assoc()) {
        array_push ($rs,$row);
    }
    foreach ($rs as $row) {
        $color++;
        array_push ($labels,$row['ccc'].' (imported)');
        array_push ($data[0],1*$row['imports']);
        array_push ($cdo->datasets[0]->backgroundColor,$color);
        array_push ($data[1],1*$row['imports']);
        array_push ($cdo->datasets[1]->backgroundColor,$color);
        array_push ($data[2],0);
        array_push ($cdo->datasets[2]->backgroundColor,$color);
    }
    array_push ($labels,'');
    array_push ($data[0],0);
    array_push ($cdo->datasets[0]->backgroundColor,$blank);
    array_push ($data[1],0);
    array_push ($cdo->datasets[1]->backgroundColor,$blank);
    array_push ($data[2],0);
    array_push ($cdo->datasets[2]->backgroundColor,$blank);
    foreach ($rs as $row) {
        $color++;
        array_push ($labels,$row['ccc'].' (retained)');
        array_push ($data[0],1*$row['retained']);
        array_push ($cdo->datasets[0]->backgroundColor,$color);
        array_push ($data[1],0);
        array_push ($cdo->datasets[1]->backgroundColor,$color);
        array_push ($data[2],1*$row['retained']);
        array_push ($cdo->datasets[2]->backgroundColor,$color);
    }
    $cdo->labels = $labels;
    $cdo->datasets[0]->label = 'All';
    $cdo->datasets[0]->weight = 0;
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[1]->label = 'Imported';
    $cdo->datasets[1]->weight = 0.5;
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[2]->label = 'Retained';
    $cdo->datasets[2]->weight = 0.5;
    $cdo->datasets[2]->data = $data[2];
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

