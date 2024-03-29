<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!is_dir(BLOTTO_DIR_STATEMENT)) {
    fwrite (STDERR,"Statements directory BLOTTO_DIR_STATEMENT='".BLOTTO_DIR_STATEMENT."'' does not exist\n");
    exit (102);
}



// Statements

$cdb = BLOTTO_CONFIG_DB;
$org_code = BLOTTO_ORG_USER;
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
    `id`
   ,`org_code`
   ,`filename`
   ,`format`
   ,`start_value`
   ,`interval`
   ,`type`
   ,`statement_overwrite`
   ,`statement_heading`
  FROM `$cdb`.`blotto_schedule`
  WHERE `type`='statement'
    AND (
        `org_code` IS NULL
     OR `org_code`=''
     OR `org_code`='$org_code'
    )
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
//fwrite (STDERR,"--------\n".$day->format('Y-m-d'))."\n--------\n";
        foreach ($statements as $s) {
//fwrite (STDERR,$s['filename']);
            // Each statement this end day
            $s['to'] = $day->format ('Y-m-d');
            $start = new \DateTime ($s['to']);
            $start->sub (new \DateInterval($s['interval']));
            $start->add (new \DateInterval('P1D'));
            $s['from'] = $start->format ('Y-m-d');
//fwrite (STDERR,' from:'.$s['from'].' to: '.$s['to']);
            $s['from_formatted'] = false;
            if (strlen($s['format'])) {
                $s['from_formatted'] = $start->format ($s['format']);
            }
            if (!strlen($s['format'])) {
//fwrite (STDERR,' no date matching constraint ');
            }
            else {
//fwrite (STDERR,$s['from_formatted'].' == '.$s['start_value'].' ? ');
            }
            if (!strlen($s['format']) || $s['from_formatted']==$s['start_value']) {
                // Start date matches the schedule
                if ($s['from']<$first) {
                    $s['from'] = $first;
                }
//fwrite (STDERR,' from:'.$s['from']." to:".$s['to']);
                $file = str_replace('{{d}}',$s['to'],$s['filename']);
                $file = str_replace('{{o}}',BLOTTO_ORG_USER,$file);
                $file = BLOTTO_DIR_STATEMENT.'/'.$file;
                if (!array_key_exists($file,$writes)) {
                    // First (most recent) file wins
                    $writes[$file] = $s;
                }
            }
            else {
//fwrite (STDERR,' did not match date constraint');
            }
//fwrite (STDERR,"\n");
        }
    }
    foreach ($writes as $file=>$w) {
        if (!file_exists($file) || $w['statement_overwrite']) {
            if ($html=statement_render($w['from'],$w['to'],$w['statement_heading'],false)) {
                echo "    Writing statement '$file' (".strlen($html)." characters)\n";
                $fp = fopen ($file,'w');
                fwrite ($fp,$html);
                fclose ($fp);
                if (!$w['statement_overwrite']) {
                    // Email the one-off statement
                    mail_attachments (
                        BLOTTO_FEE_EMAIL,
                        BLOTTO_BRAND." statement {$w['from']} thru {$w['to']}",
                        "Please find your statement attached.\n\n",
                        [$file]
                    );
                }
            }
            else {
                fwrite (STDERR,"No statement HTML was generated: ({$w['from']},{$w['to']},{$w['statement_heading']})\n");
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

