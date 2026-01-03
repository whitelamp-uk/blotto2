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
$cdo->labels[3] = 'Last draw';

try {

    // get players
    $q = "
      SELECT  
        SUM(`status`='importing') AS `importing`
       ,SUM(`status`='collecting') AS `collecting`
       ,SUM(`status`='entering' OR `status`='loading') AS `loading`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[0]->data[0] = $params['importing'];
    $cdo->datasets[0]->data[1] = $params['collecting'];
    $cdo->datasets[0]->data[2] = $params['loading'];

    // get chances
    $q = "
      SELECT  
        SUM(IF (`status`='importing',`tickets`,0)) as `importing`
       ,SUM(IF (`status`='collecting',`tickets`,0)) as `collecting`
       ,SUM(IF (`status`='entering' OR `status`='loading',`tickets`,0)) as `loading`
      FROM `Journeys`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[1]->data[0] = $params['importing'];
    $cdo->datasets[1]->data[1] = $params['collecting'];
    $cdo->datasets[1]->data[2] = $params['loading'];

    // get last draw
    $q = "
      SELECT
        COUNT(DISTINCT `client_ref`) AS `supporters`
       ,COUNT(`id`) AS `tickets`
      FROM (
        SELECT  
          MAX(`draw_closed`) AS `draw_closed`
        FROM `blotto_entry`
      ) AS `last`
      JOIN `blotto_entry` AS `e`
        ON `e`.`draw_closed`=`last`.`draw_closed`
      ;
    ";
    $rows = $zo->query ($q);
    $params = $rows->fetch_assoc ();
    $cdo->datasets[0]->data[3] = $params['supporters'];
    $cdo->datasets[1]->data[3] = $params['tickets'];

    $cdo->seconds_to_execute = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

