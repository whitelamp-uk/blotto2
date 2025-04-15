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
       ,COUNT(DISTINCT `supporter_id`) AS `supporters`
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
    $revenue = 0;
    $supporters = 0;
    while ($row=$rows->fetch_assoc()) {
        $data[] = $row;
        $supporters += 1*$row['supporters'];
        $revenue += 1*$row['revenue'];
    }
    foreach ($data as $i=>$d) {
        $labels[$i] = "{$data[$i]['chances']} chances";
        $data[$i]['supporters'] = 100*$data[$i]['supporters'] / $supporters;
        $data[$i]['supporters'] = number_format ($data[$i]['supporters'],3,'.','');
        $data[$i]['revenue'] = 100*$data[$i]['revenue'] / $revenue;
        $data[$i]['revenue'] = number_format ($data[$i]['revenue'],3,'.','');
    }
    $cdo->labels        = $labels;
    $cdo->datasets      = [];
    $cdo->datasets[0]   = new stdClass ();
    $cdo->datasets[0]->label = 'Supporters %';
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1]   = new stdClass ();
    $cdo->datasets[1]->label = 'Revenue %';
    $cdo->datasets[1]->backgroundColor = 2;
    foreach ($data as $i=>$d) {
        $cdo->datasets[0]->data[] = $data[$i]['supporters'];
        $cdo->datasets[1]->data[] = $data[$i]['revenue'];
    }
    $cdo->game_age      = $game_age;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}


