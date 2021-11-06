<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!is_dir(BLOTTO_DIR_INVOICE)) {
    fwrite (STDERR,"Draw report directory BLOTTO_DIR_INVOICE=".BLOTTO_DIR_INVOICE." does not exist\n");
    exit (102);
}

$rdb = BLOTTO_RESULTS_DB;

$first = '0000-00-00';
if (defined('BLOTTO_INVOICE_FIRST') && BLOTTO_INVOICE_FIRST) {
    $first = BLOTTO_INVOICE_FIRST;
}

$qs = "
  SELECT
    `r`.`draw_closed`
   ,`w`.`ticket_number` IS NOT NULL AS `has_winners`
  FROM `$rdb`.`blotto_result` AS `r`
  LEFT JOIN `Wins` AS `w`
         ON `w`.`draw_closed`=`r`.`draw_closed`
  WHERE `r`.`draw_closed`>='$first'
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
        // Raise invoice for game services
        $file   = BLOTTO_DIR_INVOICE.'/';
        $file  .= BLOTTO_ORG_USER.'_'.$draw['draw_closed'].'_game.html';
        if (!file_exists($file)) {
            if ($inv=invoice_game($draw['draw_closed'],false)) {
                $fp = fopen ($file,'w');
                fwrite ($fp,$inv);
                fclose ($fp);
            }
        }
        // Raise invoice (pass on cost) of paying out to winners
        if ($draw['has_winners']) {
            $file   = BLOTTO_DIR_INVOICE.'/';
            $file  .= BLOTTO_ORG_USER.'_'.$draw['draw_closed'].'_payout.html';
            if (!file_exists($file)) {
                if ($inv=invoice_payout($draw['draw_closed'],false)) {
                    $fp = fopen ($file,'w');
                    fwrite ($fp,$inv);
                    fclose ($fp);
                }
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}

