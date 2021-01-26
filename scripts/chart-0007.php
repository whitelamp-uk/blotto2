<?php

// This one is a bit crap but is an example of grouping in interesting ways

$data       = [[],[]];
$q = "
  SELECT
    IF(`s`.`postcode` REGEXP '^[A-z][A-z]',SUBSTR(`s`.`postcode`,1,2),SUBSTR(`s`.`postcode`,1,1)) AS `area`
   ,FORMAT(AVG(`s`.`plays`),1) AS `ppt_mean`
   ,COUNT(`s`.`current_client_ref`) AS `supporters`
  FROM (
    SELECT
      `current_client_ref`
     ,COUNT(`current_ticket_number`) AS `plays`
     ,`postcode`
    FROM `Supporters`
    WHERE LENGTH(`postcode`)>1 AND `postcode` IS NOT NULL
    GROUP BY `client_ref`
  ) AS `s`
  WHERE LENGTH(`s`.`postcode`)>1 AND `s`.`postcode` IS NOT NULL
  GROUP BY `area`
  HAVING `supporters`>10
  ORDER BY SUBSTR(`s`.`postcode`,1,CHAR_LENGTH(`s`.`postcode`)-3)
";

try {
    $rows = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
       array_push ($labels,$row['area']);
       array_push ($data[0],1*$row['ppt_mean']);
       array_push ($data[1],1*$row['supporters']/100);
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Avg plays per ticket';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 2;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = '100s of Supporters';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = 0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

