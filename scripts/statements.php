<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!is_dir(BLOTTO_DIR_STATEMENT)) {
    fwrite (STDERR,"Draw report directory BLOTTO_DIR_STATEMENT=".BLOTTO_DIR_STATEMENT." does not exist\n");
    exit (102);
}



// Statements

$cdb = BLOTTO_CONFIG_DB;
if (defined('BLOTTO_INVOICE_FIRST') && BLOTTO_INVOICE_FIRST) {
    $first = BLOTTO_INVOICE_FIRST;
}
else {
    $first = day_one()->format ('Y-m-d');
}
$start = new \DateTime ($first);
while ($start->format('D')!='Mon') {
    $start->sub (new \DateInterval('P1D'));
}
$today = new \DateTime ();
$today = $today->format ('Y-m-d');

$qs = "
  SELECT
    *
  FROM `$cdb`.`blotto_schedule`
  ;
";
$statements = [];
try {
    $rows = $zo->query ($qs);
    while ($row=$rows->fetch_assoc()) {
        $statements[] = $row;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}

try {
    while ($start->format('Y-m-d')<$today) {
        foreach ($statements as $s) {
            if ($start->format($s['format'])==$s['start_value']) {
                $from = $start->format('Y-m-d');
                $to = new \DateTime ($from);
                $to->add (new \DateInterval($s['interval']));
                $to->sub (new \DateInterval('P1D'));
                $to = $to->format('Y-m-d');
                if ($to<$today) {
                    // Statement ends is in the past
                    $html = statement_render ($from,$to,false);
                    $file = BLOTTO_DIR_STATEMENT.'/'.str_replace('{{d}}',$to,$s['filename']);
                    if (!file_exists($file) || $s['overwrite']>0) {
                        echo "    Writing statement '$file'\n";
                        $fp = fopen ($file,'w');
                        fwrite ($fp,$html);
                        fclose ($fp);
                    }
                }
            }
        }
        $start->add ('P1D');
    }
}
catch (\Exception $e) {
    fwrite (STDERR,"Failed to create statements without errors\n");
    fwrite (STDERR,$e->getMessage()."\n");
    // Do not abort build for this
}

