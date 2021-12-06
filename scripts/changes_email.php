<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

if (!array_key_exists(4,$argv) || strlen($argv[4])!=3) {
    fwrite (STDERR,"Must be passed a day argument eg Mon or Tue\n");
    exit (101);
}
$dow = $argv[4];

echo "    Emailing changes for canvassing companies\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}


$dt                         = new \DateTime ();
// Only do this if today is $dow
if ($dt->format('D')==$dow) {
    fwrite (STDERR,"    Emailing CCCs because today is $dow\n");
    $dir                    = BLOTTO_TMP_DIR.'/'.BLOTTO_ORG_USER.'/ccc';
    exec ("mkdir -p '$dir'");
    if (!is_dir($dir)) {
        echo "        Failed to make directory '$dir'\n";
        fwrite (STDERR,"Failed to make directory '$dir'\n");
        exit (103);
    }
    $files                  = [];
    $dt->sub (new \DateInterval('P1D'));
    $end                    = $dt->format ('Y-m-d');
    $dt->sub (new \DateInterval('P6D'));
    $start                  = $dt->format ('Y-m-d');
    $qs = "
      SELECT
        DISTINCT `ccc` AS `code`
      FROM `Changes`
      WHERE `changed_date`>='$start'
        AND `changed_date`<='$end'
      ;
    ";
    try {
        echo "CCCs to be emailed\n";
        $codes              = $zo->query ($qs);
        while ($code=$codes->fetch_assoc()) {
            echo "    CCCs w/e $end found CCC=$code\n";
            $code           = $code['code'];
            $changes        = [];
            $qs = "
              SELECT
                *
              FROM `Changes`
              WHERE `changed_date`>='$start'
                AND `changed_date`<='$end'
              ORDER BY `changed_date`,`ccc`,`canvas_ref`,`chance_number`
              ;
            ";
            $rows           = $zo->query ($qs);
            while ($row=$rows->fetch_assoc()) {
                $changes[]  = $row;
            }
            if ($count=count($changes)) {
                echo "        $count changes for $code\n";
                $headers    = [];
                $file       = $dir."/cccs-$end-$code.csv";
                if (file_exists($file)) {
                    echo "        File '$file' already found - presumably already emailed\n";
                }
                else {
                    echo "        Creating CSV file '$file'\n";
                    $fp     = fopen ($file,'w');
                    foreach ($changes[0] as $field=>$v) {
                        $headers[] = $field;
                    }
                    fputcsv ($headers);
                    foreach ($changes as $change) {
                        fputcsv ($change);
                    }
                    fclose ($fp);
                    $files[] = $file;
                }
            }
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (104);
    }
    exec ("rm -r '$dir'");
    echo "    ".count($files)." files to send\n";
    if (count($files)) {
        echo "    Emailing ".count($files)." attachments to ".BLOTTO_EMAIL_CCC."\n";
        mail_attachments (
            BLOTTO_EMAIL_CCC,
            "CCC report(s) from ".BLOTTO_BRAND." w/e $end",
            "Early cancellation data for canvassing company feedback",
            $files
        );
    }
}

