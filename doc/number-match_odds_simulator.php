<?php

$example    = "1234567890";
$matches    = [ 3, 4, 5, 6, 7 ];
$ranges     = [ 4, 5, 6, 7, 8 ];

// Add column headings
fputcsv (STDOUT,['Match','of','Length','Type','Matches','','Tickets','Chances/1,000,000','Readable','']);

foreach ($ranges as $lenr) {
    foreach ($matches as $lenm) {
        $dof = $lenr - $lenm;
        $count = ['L'=>0,'LR'=>0,'LMR'=>0];
        $range = pow (10,$lenr);
        $match = substr ($example,0,$lenr);
        if ($dof<0) {
            continue;
        }
        if ($dof==0) {
            fputcsv (
                STDOUT,
                [
                    $lenm,
                    'of',
                    $lenr,
                    '-',
                    1,
                    ':',
                    $range,
                    number_format (1000000*1/$range,1,'',''),
                    '1 : ',
                    number_format ($range/1,0,'','')
                ]
            );
            continue;
        }
        for ($i=0;$i<$range;$i++) {
            $i = str_pad ($i,$lenr,'0',STR_PAD_LEFT);
            for ($j=0;$j<=$dof;$j++) {
                if (substr($i,$j,$lenm)!=substr($match,$j,$lenm)) {
                    continue;
                }
                $count['LMR']++;
                if ($j==0) {
                    $count['LR']++;
                    $count['L']++;
                }
                if (($j+1)==$dof) {
                    $count['LR']++;
                }
            }
        }
        foreach ($count as $type=>$perms) {
            if ($dof==1 && $type=='LMR') {
                continue;
            }
            fputcsv (
                STDOUT,
                [
                    $lenm,
                    'of',
                    $lenr,
                    $type.'+',
                    $perms,
                    ':',
                    $range,
                    number_format (1000000*$perms/$range,1,'',''),
                    '1 : ',
                    number_format ($range/$perms,0,'','')
                ]
            );
        }
    }
}

