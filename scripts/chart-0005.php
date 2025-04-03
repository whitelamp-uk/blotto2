<?php

// chart 5 is now a workflow meter using `Journeys`; BAs like to call this a "sales funnel"

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
$cdo->labels[3] = 'Onboarded';

try {

    // Get players
    $q = "
      SELECT  
        SUM(`status`='importing') as `importing`
       ,SUM(`status`='collecting') as `collecting`
       ,SUM(`status`='entering' OR `status`='loading') as `loading`
       ,SUM(`status`='entered') as `entered`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[0]->data[0] = $params['importing'];
    $cdo->datasets[0]->data[1] = $params['collecting'];
    $cdo->datasets[0]->data[2] = $params['loading'];
    $cdo->datasets[0]->data[3] = $params['entered'];


    // Get players
    $q = "
      SELECT  
        SUM(IF (`status`='importing',`tickets`,0)) as `importing`
       ,SUM(IF (`status`='collecting',`tickets`,0)) as `collecting`
       ,SUM(IF (`status`='entering' OR `status`='loading',`tickets`,0)) as `loading`
       ,SUM(IF (`status`='entered',`tickets`,0)) as `entered`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[1]->data[0] = $params['importing'];
    $cdo->datasets[1]->data[1] = $params['collecting'];
    $cdo->datasets[1]->data[2] = $params['loading'];
    $cdo->datasets[1]->data[3] = $params['entered'];

    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

