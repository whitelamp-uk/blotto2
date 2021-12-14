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
$org_code = BLOTTO_ORG_USER;
$overwrites = [];
if (defined('BLOTTO_INVOICE_FIRST') && BLOTTO_INVOICE_FIRST) {
    $first = new \DateTime (BLOTTO_INVOICE_FIRST);
}
else {
    $first = day_one ();
}
while ($first->format('D')!='Mon') {
    $first->sub (new \DateInterval('P1D'));
}
// Monday before the first invoice
$first = $first->format ('Y-m-d');
// Now
$day = new \DateTime ();

// Get global and org-specific statements schedule
$qs = "
  SELECT
    *
  FROM `$cdb`.`blotto_schedule`
  WHERE `org_code` IS NULL
     OR `org_code`=''
     OR `org_code`='$org_code'
  ;
";
$statements = [];
$writes = [];
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
    while ($day->format('Y-m-d')>=$first) {
        // From yesterday back to $first
        $day->sub (new \DateInterval('P1D'));
        foreach ($statements as $s) {
            // Each statement this end day
            $s['to'] = $day->format ('Y-m-d');
            $start = new \DateTime ($s['to']);
            $start->sub (new \DateInterval($s['interval']));
            $start->add (new \DateInterval('P1D'));
//echo $s['format'].' == '.$s['start_value'].' ? ';
            if (!strlen($s['format']) || $start->format($s['format'])==$s['start_value']) {
                // Start date matches the schedule
                $s['from'] = $start->format ('Y-m-d');
                if ($s['from']<$first) {
                    $s['from'] = $first;
                }
//echo $s['from']." -- ".$s['to']." ";
                $file = BLOTTO_DIR_STATEMENT.'/'.str_replace('{{d}}',$s['to'],$s['filename']);
                if (!array_key_exists($file,$writes)) {
                    // First (most recent) file wins
                    $writes[$file] = $s;
//echo $file;
                }
            }
//echo "\n";
        }
    }
    foreach ($writes as $file=>$w) {
        if (!file_exists($file) || $w['overwrite']) {
            if ($html=statement_render($w['from'],$w['to'],$w['heading'],false)) {
                echo "    Writing statement '$file' (".strlen($html)." characters)\n";
                $fp = fopen ($file,'w');
                fwrite ($fp,$html);
                fclose ($fp);
            }
            else {
                fwrite (STDERR,"No statement HTML was generated: ({$w['from']},{$w['to']},{$w['heading']})\n");
                exit (104);
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,"Failed to create statements without errors\n");
    fwrite (STDERR,$e->getMessage()."\n");
    // Do not abort build for this
}

