<?php

// chart 5 is now a workflow meter using `Journeys`; BAs like to call this a "sales funnel"
// 2025-04-15 it is now a sort-of-sales funnel; replacing "entered" with "playing"
// means that the dormant players have disappeared from the funnel without trace...

$t0 = time ();
$data = [];
$cdo->datasets[0] = new stdClass ();
$cdo->datasets[0]->backgroundColor = 1;
$cdo->datasets[0]->label = 'Players';
$cdo->datasets[0]->data = [];
$cdo->datasets[1] = new stdClass ();
$cdo->datasets[1]->backgroundColor = 2;
$cdo->datasets[1]->label = 'Tickets';
$cdo->datasets[1]->data = [];
$cdo->labels[0] = 'Imported';
$cdo->labels[1] = 'DDI requested';
$cdo->labels[2] = 'Draw 1 selected';
$cdo->labels[3] = 'Playing';

try {

    // Get players
    $q = "
      SELECT  
        SUM(`status`='importing') AS `importing`
       ,SUM(`status`='collecting') AS `collecting`
       ,SUM(`status`='entering' OR `status`='loading') AS `loading`
       ,SUM(`status`='entered') AS `entered`
       ,SUM(`status`='entered' AND `dormancy_date` IS NOT NULL) AS `dormant`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[0]->data[0] = $params['importing'];
    $cdo->datasets[0]->data[1] = $params['collecting'];
    $cdo->datasets[0]->data[2] = $params['loading'];
    $cdo->datasets[0]->data[3] = $params['entered'] - $params['dormant'];

    // Get chances
    $q = "
      SELECT  
        SUM(IF (`status`='importing',`tickets`,0)) as `importing`
       ,SUM(IF (`status`='collecting',`tickets`,0)) as `collecting`
       ,SUM(IF (`status`='entering' OR `status`='loading',`tickets`,0)) as `loading`
       ,SUM(IF (`status`='entered',`tickets`,0)) as `entered`
       ,SUM(IF (`status`='entered' AND `dormancy_date` IS NOT NULL,`tickets`,0)) as `dormant`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[1]->data[0] = $params['importing'];
    $cdo->datasets[1]->data[1] = $params['collecting'];
    $cdo->datasets[1]->data[2] = $params['loading'];
    $cdo->datasets[1]->data[3] = $params['entered'] - $params['dormant'];

    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

