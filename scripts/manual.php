<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo                 = connect (BLOTTO_RESULTS_DB);
if (!$zo) {
    exit (101);
}

// Define
$rehearse           = get_argument ('r');
if (!array_key_exists(6,$argv)) {
    fwrite (STDERR,"    Usage: draw_closed_date number-match_group perfect_number\n");
    exit (102);
}
else {
    $draw_closed    = $argv[4];
    try {
        $draw       = draw ($draw_closed);
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (103);
    }
    if ($draw->date>date('Y-m-d')) {
        fwrite (STDERR,"Derived draw date {$draw->date} is in the future\n");
        exit (104);
    }
    $group          = null;
    foreach ($draw->groups as $g=>$levels) {
        if ($g==$argv[5]) {
            $group  = $g;
            break;
        }
    }
    if (!$group) {
        fwrite (STDERR,"number-match group '$group' has no prizes\n");
        exit (105);
    }
    if (array_key_exists($group,$draw->results)) {
        fwrite (STDERR,"Group '$group' @ $draw_closed already has results\n");
        exit (106);
    }
    $number         = $argv[6];
    if (!preg_match('<^[0-9]+$>',$number)) {
        fwrite (STDERR,"number can only contain digits 0 thru 9\n");
        exit (107);
    }
    if ($number<BLOTTO_TICKET_MIN || $number>BLOTTO_TICKET_MAX) {
        fwrite (STDERR,"number must be ".BLOTTO_TICKET_MIN." thru".BLOTTO_TICKET_MAX."\n");
        exit (108);
    }
    echo "    Building SQL for `".BLOTTO_RESULTS_DB."`\n";
    $qi             = "INSERT INTO `blotto_result`";
    $qi            .= " (`draw_closed`,`draw_date`,`prize_level`,`number`)";
    $qi            .= " VALUES\n";
    $count          = 0;
    foreach ($draw->groups[$group] as $level) {
        if (!$draw->prizes[$level]['results_manual']) {
            continue;
        }
        $count++;
        $qi         .= "('{$draw->closed}','{$draw->date}',$level,'$number'),";
    }
}

if (!$count) {
    fwrite (STDERR,"    No results to insert\n");
    exit (0);
}
$qi = substr($qi,0,-1).";";

echo $qi;
if ($rehearse) {
    echo "    Rehearsal only - quitting\n";
    exit (0);
}

try {
    echo "    Executing SQL\n";
    $zo->query ($qi);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qi."\n".$e->getMessage()."\n");
    exit (109);
}


