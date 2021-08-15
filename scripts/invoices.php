<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_RESULTS_DB);
if (!$zo) {
    exit (101);
}

$qs = "
  SELECT
    DISTINCT `draw_closed`
  FROM `blotto_result`
  ORDER BY `draw_closed`
  ;
";

$draws = array ();
try {
    $ds = $zo->query ($qs);
    while ($d=$ds->fetch_assoc()) {
        $draws[] = $d['draw_closed'];
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}

try {
    foreach ($draws as $draw_closed) {
        // Raise invoice for game services
        $file   = BLOTTO_DIR_INVOICE.'/';
        $file  .= BLOTTO_ORG_USER.'_'.$draw_closed.'_game.html';
        if (!file_exists($file)) {
            if ($inv=invoice_game($draw_closed,false)) {
                $fp = fopen ($file,'w');
                fwrite ($fp,$inv);
                fclose ($fp);
            }
        }
        // Raise invoice (pass on cost) of paying out to winners
        $file   = BLOTTO_DIR_INVOICE.'/';
        $file  .= BLOTTO_ORG_USER.'_'.$draw_closed.'_payout.html';
        if (!file_exists($file)) {
            if ($inv=invoice_payout($draw_closed,false)) {
                $fp = fopen ($file,'w');
                fwrite ($fp,$inv);
                fclose ($fp);
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
// TODO: let this error cause an abort
//            exit (103);
}

