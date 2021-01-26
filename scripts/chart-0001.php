<?php

// Recent draw activity

$data       = [[],[]];
$q = "
  SELECT
    `draw_closed`
   ,SUBSTR(`draw_closed`,1,7) AS `month`
   ,COUNT(`draw_closed`) AS `draws`
   ,ROUND(SUM(`supporters_entered`)/COUNT(`draw_closed`),0) AS `supporters`
   ,ROUND(SUM(`tickets_entered`)/COUNT(`draw_closed`),0) AS `tickets`
  FROM `Draws_Supersummary`
  WHERE SUBSTR(`draw_closed`,1,7)<SUBSTR(CURDATE(),1,7)
  GROUP BY `month`
  ORDER BY `month` DESC
  {{LIMIT}}
";
if ($type=='graph') {
    $limit = 'LIMIT 0,6';
}
else {
    $limit = '';
}
$q = str_replace ('{{LIMIT}}',$limit,$q);
try {
    $rows = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
       $dt = new DateTime ($row['draw_closed']);
       array_unshift ($labels,$dt->format('M Y')." ({$row['draws']})");
       array_unshift ($data[0],1*$row['supporters']);
       array_unshift ($data[1],1*$row['tickets']);
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Avg players/draw';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = 'Avg tickets/draw';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = 2;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

