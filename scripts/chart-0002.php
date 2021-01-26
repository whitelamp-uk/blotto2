<?php

// Tickets per player

$q = "
  SELECT
    `chances`.`tickets`
   ,COUNT(`chances`.`client_ref`) AS `players`
  FROM (
    SELECT
      MAX(`draw_closed`) AS `draw_closed`
    FROM `blotto_entry`
    WHERE `draw_closed`<CURDATE()
  ) AS `latest`
  JOIN (
    SELECT
      `draw_closed`
     ,`client_ref`
     ,COUNT(`ticket_number`) AS `tickets`
    FROM `blotto_entry`
    GROUP BY `draw_closed`,`client_ref`
  ) AS `chances`
    ON `chances`.`draw_closed`=`latest`.`draw_closed`
  GROUP BY `chances`.`tickets`
  ORDER BY `chances`.`tickets`
  ;
";
// That approach was getting extremely slow so two queries seems the answer:


$q = "
  SELECT
    MAX(`draw_closed`) AS `latest`
  FROM `blotto_entry`
  WHERE `draw_closed`<CURDATE()
  ;
";

try {
    $rows = $zo->query ($q);
    $row  = $rows->fetch_assoc ();
    $date = $row['latest'];
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

$q = "
  SELECT
    `chances`.`tickets`
   ,COUNT(`chances`.`client_ref`) AS `players`
  FROM (
    SELECT
      `client_ref`
     ,COUNT(`ticket_number`) AS `tickets`
    FROM `blotto_entry`
    WHERE `draw_closed`='$date'
    GROUP BY `client_ref`
  ) AS `chances`
  GROUP BY `chances`.`tickets`
  ORDER BY `chances`.`tickets`
  ;
";

try {
    $rows = $zo->query ($q);
    $values = array ();
    $total = 0;
    while ($row=$rows->fetch_assoc()) {
        array_push ($values,$row);
        $total += $row['players'];
    }
    foreach ($values as $row) {
       $label  = number_format(100*$row['players']/$total,2).'% of players';
       $label .= ' with '.$row['tickets'].' ticket'.plural($row['tickets']);
       array_push ($labels,$label);
       array_push ($data[0],1*$row['players']);
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Players by nr of chances';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

