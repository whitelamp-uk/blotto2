<?php

// All time retention - for supporters either remaining - $p[0]=false - or cancelled - $p[0]=true

$t0         = time ();
$interval   = BLOTTO_CANCEL_RULE;
$data       = [];
$labels     = [];
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
        `chances`
       ,SUM(`supporter_total_amount`) AS `revenue`
      FROM (
        SELECT
          `supporter_id`
         ,COUNT(`current_ticket_number`) AS `chances`
         ,`supporter_total_amount`
        FROM `Supporters`
        WHERE `supporter_total_payments`>0
        GROUP BY `supporter_id`
      ) as `s`
      GROUP BY `chances`
      ORDER BY `chances`
      ;
    ";
    $rows = $zo->query ($q);
    $total = 0;
    while ($row=$rows->fetch_assoc()) {
        $labels[] = "{$row['chances']} chances";
        $data[] = 1*$row['revenue'];
        $total += 1*$row['revenue'];
    }
    $datalabels         = [];
    foreach ($data as $i=>$d) {
        $data[$i]       = 100*$data[$i] / $total;
        $data[$i]       = number_format ($data[$i],3,'.','');
        $datalabels[$i] = $data[$i].'%';
    }
    $cdo->labels        = $labels;
    $cdo->datasets      = [];
    $cdo->datasets[0]   = new stdClass ();
    $cdo->datasets[0]->label = 'Revenue';
    $cdo->datasets[0]->data = $data;
    $cdo->datasets[0]->backgroundColor = 2;
    $cdo->game_age      = $game_age;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}


