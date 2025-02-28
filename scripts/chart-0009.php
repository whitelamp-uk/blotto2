<?php

// No-show benchmarking

$me = $p[0];
$data  = [[],[]];
if ($me) {
    $q = "
      SELECT
        *
      FROM `BenchmarkNoShows`
      WHERE `month_commencing`<'$me'
        AND `month_commencing`>DATE_SUB('$me',INTERVAL 1 YEAR)
      ORDER BY `month_commencing`
    ";
}
else {
    $q = "
      SELECT
        *
      FROM `BenchmarkNoShows`
      ORDER BY `month_commencing`
    ";
}
try {
    $colors         = [];
    $rows           = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
        $dt          = new DateTime ($row['month_commencing']);
        $labels[]    = $dt->format ('M Y');
        $data[0][]   = 1*$row['benchmark_performance'];
        $data[1][]   = 1*$row['performance'];
        if ($row['benchmark_performance']<$row['performance']) {
            $colors[] = 3;
        }
        else {
            $colors[] = 2;
        }
    }
    $cdo->labels    = $labels;
    $cdo->datasets  = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Benchmark';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = $colors;
    if ($type=='graph') {
        $cdo->datasets[2] = new stdClass ();
        if ($colors[0]==3) {
            $cdo->datasets[1]->label = 'Worse';
            $cdo->datasets[2]->label = 'Better / no-shows per 100 sign-ups';
            $cdo->datasets[2]->backgroundColor = 2;
        }
        else {
            $cdo->datasets[1]->label = 'Better';
            $cdo->datasets[2]->label = 'Worse / no-shows per 100 sign-ups';
            $cdo->datasets[2]->backgroundColor = 3;
        }
    }
    else {
        $cdo->datasets[1]->label = 'Outcome / no-shows per 100 sign-ups';
    }
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

