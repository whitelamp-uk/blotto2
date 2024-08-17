<?php

// Revenue / expense (recent)

$start = null;
if (array_key_exists(0,$p)) {
    $end = $p[0];
}
$weekly = array_key_exists(1,$p) && $p[1]=='w';
$price = BLOTTO_TICKET_PRICE;

$end = new \DateTime ($end);
if ($weekly) {
    while ($end->format('w')!=5) {
        // wind end back to last Friday (lottery week ending)
        $end->sub (new \DateInterval ('P1D'));
    }
    $start = clone ($end);
    // wind start back 26 weeks
    $start->sub (new \DateInterval ('P26W'));
    // wind forward to first Saturday (lottery week commencing)
    $start->add (new \DateInterval ('P1D'));
}
else {
    // wind back to start of this month
    $end = new \DateTime ($end->format('Y-m-01'));
    $start = new \DateTime ($end->format('Y-m-d'));
    // wind start back 24 months
    $start->sub (new \DateInterval ('P24M'));
    // wind end back to end of last month
    $end->sub (new \DateInterval ('P1D'));
}
$stats = [];
$end = $end->format ('Y-m-d');
while ($start->format('Y-m-d')<$end) {
    $from = $start->format ('Y-m-d');
    if ($weekly) {
         $start->add (new \DateInterval ('P6D'));
    }
    else {
        $start->add (new \DateInterval ('P1M'));
        $start->sub (new \DateInterval ('P1D'));
    }
    $to = $start->format ('Y-m-d');
    $stat = calculate ($from,$to);
    $stat['period_end'] = $to;
    if ($weekly) {
         $stat['x_label'] = $to;
    }
    else {
         $stat['x_label'] = $start->format ('M Y');
    }
    $stats[] = $stat;
    $start->add (new \DateInterval ('P1D'));
}



$data       = [[],[],[]];
try {
    $rows       = $stats;
    $recruits   = 0;
    $cancels    = 0;
    foreach ($rows as $row) {
        $data[0][] = $row['revenue'];
        $data[1][] = $row['expenses'];
        $data[2][] = $row['payout'];
    }
    if ($type=='graph') {
        $labels[]  = $row['x_label'];
    }
    $cdo->labels = $labels;
    $cdo->datasets = [];
    $cdo->datasets[0] = new stdClass ();
    $cdo->datasets[0]->label = 'Revenue';
    $cdo->datasets[0]->data = $data[0];
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1] = new stdClass ();
    $cdo->datasets[1]->label = 'Expenses';
    $cdo->datasets[1]->data = $data[1];
    $cdo->datasets[1]->backgroundColor = 2;
    $cdo->datasets[2] = new stdClass ();
    $cdo->datasets[2]->label = 'Payout';
    $cdo->datasets[2]->data = $data[2];
    $cdo->datasets[2]->backgroundColor = 3;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

