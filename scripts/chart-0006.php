<?php

// Canvassing company throughput

$me    = $p[0];
if ($me) {
    $where = "WHERE `signed`<='$me' AND `signed`>DATE_SUB('$me',INTERVAL 12 MONTH)";
}
else {
    $where = "WHERE `signed`<CONCAT(SUBSTR(CURDATE(),1,7),'-01')";
}
$data       = [];

try {

    // Get canvassing company codes and data sets
    $q = "
      SELECT
        `canvas_code` AS `ccc`
      FROM `blotto_supporter`
      $where
      GROUP BY `ccc`
      ORDER BY `ccc`
      ;
    ";
    $rows           = $zo->query ($q);
    $cdo->datasets  = [];
    $cdo->labels    = [];
    $cccs           = [];
    $i              = 0;
    while ($row=$rows->fetch_assoc()) {
        $cccs[$row['ccc']]  = $i;
        $cdo->datasets[$i]  = new stdClass ();
        $cdo->datasets[$i]->backgroundColor = $i + 1;
        $cdo->datasets[$i]->label = $row['ccc'];
        $cdo->datasets[$i]->data = [];
        $i++;
    }

    // Get the months
    $q = "
      SELECT
        SUBSTR(`signed`,1,7) AS `month`
      FROM `blotto_supporter`
      $where
      GROUP BY `month`
      ORDER BY `month`
      ;
    ";
    $rows           = $zo->query ($q);
    $months         = [];
    $i              = 0;
    while ($row=$rows->fetch_assoc()) {
        $months[$row['month']] = $i;
        $dt         = new DateTime ($row['month'].'-01');
        $cdo->labels[] = $dt->format('M Y');
        foreach ($cdo->datasets as $j=>$d) {
            $cdo->datasets[$j]->data[$i] = 0;
        }
        $i++;
    }

    // Get the data
    $q = "
      SELECT
        SUBSTR(`signed`,1,7) AS `month`
       ,`canvas_code` AS `ccc`
       ,COUNT(DISTINCT `id`) AS `imports`
      FROM `blotto_supporter`
      $where
      GROUP BY `month`,`ccc`
      ORDER BY `month`,`ccc`
      ;
    ";
    $rows = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
        $cdo->datasets[$cccs[$row['ccc']]]->data[$months[$row['month']]] = $row['imports'];
    }

}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

