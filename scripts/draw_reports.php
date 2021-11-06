<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!is_dir(BLOTTO_DIR_DRAW)) {
    fwrite (STDERR,"Draw report directory BLOTTO_DIR_DRAW=".BLOTTO_DIR_DRAW." does not exist\n");
    exit (102);
}

$rdb = BLOTTO_RESULTS_DB;

$qs = "
  SELECT
    `r`.`draw_closed`
   ,`w`.`ticket_number` IS NOT NULL AS `has_winners`
  FROM (
    SELECT
      DISTINCT `draw_closed`
    FROM `blotto_entry`
  ) AS `draws`
  JOIN `$rdb`.`blotto_result` AS `r`
    ON `r`.`draw_closed`=`draws`.`draw_closed`
  LEFT JOIN `Wins` AS `w`
         ON `w`.`draw_closed`=`r`.`draw_closed`
  GROUP BY `r`.`draw_closed`
  ORDER BY `r`.`draw_closed`
  ;
";

$draws = [];
try {
    $ds = $zo->query ($qs);
    while ($d=$ds->fetch_assoc()) {
        $draws[] = $d;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}

try {
    foreach ($draws as $draw) {
        // Write draw report
        $file   = BLOTTO_DIR_DRAW.'/';
        $file  .= BLOTTO_ORG_USER.'_'.$draw['draw_closed'].'_draw_report.html';
        if (!file_exists($file)) {
            if ($dr=draw_report_render($draw['draw_closed'],false)) {
                $fp = fopen ($file,'w');
                fwrite ($fp,$dr);
                fclose ($fp);
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}

