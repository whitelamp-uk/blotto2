<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];
$cdb = BLOTTO_CONFIG_DB;
$org_code = BLOTTO_ORG_USER;
$dow = BLOTTO_EMAIL_REPORT_DAY;

echo "    Emailing changes for canvassing companies\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}


$today = new \DateTime ();
// Only do this if today is $dow
if ($today->format('D')==$dow) {
    fwrite (STDERR,"    Emailing CCRs because today is $dow\n");
    // Get global and org-specific CCR schedule
    $qs = "
      SELECT
        `id`
       ,`org_code`
       ,`filename`
       ,`format`
       ,`start_value`
       ,`interval`
       ,`type`
       ,`ccr_email`
       ,`ccr_ccc`
      FROM `$cdb`.`blotto_schedule`
      WHERE `type`='ccr'
        AND `org_code`='$org_code'
      ;
    ";
    echo $qs."\n";
    $changes = [];
    $ccrs = [];
    $emails = [];
    try {
        $rows = $zo->query ($qs);
        while ($row=$rows->fetch_assoc()) {
            $ccrs[] = $row;
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (102);
    }
    try {
        foreach ($ccrs as $r) {
            $code = $r['ccr_ccc'];
            $day = new \DateTime ($today->format('Y-m-d'));
            // Wind forward to next start
            while ($day->format($r['format'])!=$r['start_value']) {
                $day->add (new \DateInterval('P1D'));
            }
            // At this start:
            $day->sub (new \DateInterval($r['interval']));
            // At this week start:
            $end = new \DateTime ($day->format('Y-m-d'));
            $end->sub (new \DateInterval('P1D'));
            $end = $end->format ('Y-m-d');
            $day->sub (new \DateInterval($r['interval']));
            // At last start:
            $start = $day->format ('Y-m-d');
            echo "    CCR for {$r['ccr_ccc']} $start thru $end\n";
            $dir = BLOTTO_TMP_DIR.'/'.BLOTTO_ORG_USER.'/'.$code;
            exec ("mkdir -p '$dir'");
            if (!is_dir($dir)) {
                fwrite (STDERR,"Failed to make directory '$dir'\n");
                exit (103);
            }
            echo "        Created directory '$dir'\n";
            $qs = "
              SELECT
                *
              FROM `Changes`
              WHERE `changed_date`>='$start'
                AND `changed_date`<='$end'
                AND `ccc`='$code'
              ORDER BY `changed_date`,`canvas_ref`,`chance_number`
              ;
            ";
            echo $qs."\n";
            $rows           = $zo->query ($qs);
            while ($row=$rows->fetch_assoc()) {
                $changes[]  = $row;
            }
            $r['count']     = count ($changes);
            echo "        {$r['count']} changes for $code\n";
            $headers        = [];
            $file           = $dir.'/'.$r['filename'];
            $file           = str_replace('{{o}}',BLOTTO_ORG_USER,$file);
            $file           = str_replace('{{d}}',$end,$file);
            $file           = str_replace('{{c}}',strtoupper($code),$file);
            if ($r['count']>0) {
                echo "        Creating CSV file '$file'\n";
                $fp             = fopen ($file,'w');
                if (!$fp) {
                    fwrite (STDERR,"Could not open file '$file' for writing\n");
                    exit (105);
                }
                foreach ($changes[0] as $field=>$v) {
                    $headers[] = $field;
                }
                fputcsv ($fp,$headers);
                foreach ($changes as $change) {
                    fputcsv ($fp,$change);
                }
                fclose ($fp);
                echo "    Successfully wrote ".count($changes)." rows of data to file '$file'\n";
            }
            else {
                echo "    No data for file '$file'\n";
            }
            $r['start'] = $start;
            $r['end'] = $end;
            $emails[$file] = $r;
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (104);
    }
    echo "    ".count($emails)." files to send\n";
    foreach ($emails as $file=>$r) {
        echo "    Emailing CCR for {$r['ccr_ccc']} to {$r['ccr_email']}\n";
        if ($r['count']>0) {
            mail_attachments (
                $r['ccr_email'],
                "Canvassing Company Return from ".strtoupper($org_code)."@".BLOTTO_BRAND." w/e {$r['end']}",
                "The canvassing company return - CCR - reports any ticket changes logged last period for recently-joined supporters (".BLOTTO_CC_NOTIFY." from sign-up)",
                [$file]
            );
            exec ("rm -f '$file'");
        }
        else {
            mail (
                $r['ccr_email'],
                "Canvassing Company Return from ".BLOTTO_BRAND." for ".strtoupper(BLOTTO_ORG_USER)."/".$r['ccr_ccc']." w/e {$r['end']}",
                "The canvassing company return - CCR - reports that no ticket changes were logged for ".strtoupper(BLOTTO_ORG_USER)."/".$r['ccr_ccc']." last period",
                "From: ".BLOTTO_EMAIL_FROM.PHP_EOL
            );
        }
    }
}

