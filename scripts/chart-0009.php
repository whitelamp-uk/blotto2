<?php

// No-show benchmarking

$t0 = time ();
$me = null;
if (count($p)) {
    $me = $p[0];
}
$data = [[],[]];
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
    $cdo->headings_tabular = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Benchmark / no-shows per 100 sign-ups';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = null;
    $cdo->headings_tabular[] = 'Outcome';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = $colors;
    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

