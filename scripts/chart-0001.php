<?php

// Recent draw activity

$me    = $p[0];
$data  = [[],[]];
$q = "
  SELECT
    `draw_closed`
   ,SUBSTR(`draw_closed`,1,7) AS `month`
   ,COUNT(`draw_closed`) AS `draws`
   ,ROUND(SUM(`supporters_entered`)/COUNT(`draw_closed`),0) AS `supporters`
   ,ROUND(SUM(`tickets_entered`)/COUNT(`draw_closed`),0) AS `tickets`
  FROM `Draws_Supersummary`
  WHERE `draw_closed`<CURDATE()
  {{WHERE}}
    AND `draw_closed` IS NOT NULL
  GROUP BY `month`
  ORDER BY `month` DESC
";
if ($me) {
    $where = "  AND `draw_closed`<='$me' AND `draw_closed`>DATE_SUB('$me',INTERVAL 12 MONTH)";
}
else {
    $where = "";
}
$q = str_replace ('{{WHERE}}',$where,$q);
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

