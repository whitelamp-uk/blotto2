<?php

// Players by their first draw

$t0             = time ();
// Draws closed "around" this date
$ma             = $p[0];
if (!$ma) {
    $ma         = gmdate('Y-m').'-01';
}
$test = $ma;
$ma             = new \DateTime ($ma);
$ma->sub (new \DateInterval ('P2M'));
$mfrom          = $ma->format ('Y-m-d');
$ma->add (new \DateInterval ('P4M'));
$mto            = $ma->format ('Y-m-d');
error_log ("$mfrom -> $test -> $mto");
$data           = [[],[]];
$bgcs           = [];
$q = "
  SELECT
    `player_draw_closed`
   ,COUNT(`player_id`) AS `players`
   ,SUM(`tickets`) AS `tickets`
   ,`player_draw_closed`>=CURDATE() AS `pending`
  FROM `Journeys`
  {{WHERE}}
  GROUP BY `player_draw_closed`
  ORDER BY `player_draw_closed`
";
if ($ma) {
    $where      = "  WHERE `player_draw_closed`<'$mto' AND `player_draw_closed`>='$mfrom'";
}
else {
    $where      = "";
}
$q              = str_replace ('{{WHERE}}',$where,$q);
try {
    $cdo->datasets              = [];
    $cdo->datasets[0]           = new stdClass ();
    $cdo->datasets[0]->label    = 'Supporters';
    $cdo->datasets[0]->backgroundColor = 1;
    $cdo->datasets[1]           = new stdClass ();
    $cdo->datasets[1]->label    = 'Tickets';
    $rows                       = $zo->query ($q);
    while ($row=$rows->fetch_assoc()) {
        $dt                     = new DateTime ($row['player_draw_closed']);
        $label                  = $dt->format ('j M Y');
        if ($row['pending']) {
            $label              = 'pending: '.$label;
            $bgcs[]             = 3;
        }
        else {
            $bgcs[]             = 2;
        }
        $labels[]               = $label;
        $data[0][]              = 1*$row['players'];
        $data[1][]              = 1*$row['tickets'];
    }
    $cdo->labels                = $labels;
    $cdo->datasets[1]->backgroundColor = $bgcs;
    $cdo->datasets[0]->data     = $data[0];
    $cdo->datasets[1]->data     = $data[1];
    $cdo->seconds_to_execute    = time() - $t0;
}
catch (\mysqli_sql_exception $e) {
    error_log ($q.' '.$e->getMessage());
    return $error;
}

