<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo                 = connect (BLOTTO_RESULTS_DB);
if (!$zo) {
    exit (101);
}

// Define
if ($rehearse=get_argument('r',$Sw)) {
    echo "    Rehearsal mode\n";
}
if (!array_key_exists(6,$argv)) {
    fwrite (STDERR,"    Usage: draw_closed_date number-match_group perfect_number\n");
    exit (102);
}
else {
    try {
        $draw       = draw ($argv[4]);
    }
    catch (\Exception $e) {
        fwrite (STDERR,"    Draw could not be found; is '{$argv[4]}' a valid draw-closed date?\n");
        exit (103);
    }
    if (draw_upcoming($argv[4])!=$argv[4]) {
        fwrite (STDERR,"    '{$argv[4]}' is not a valid draw-closed date\n");
        exit (105);
    }
    if ($draw->date>date('Y-m-d')) {
        fwrite (STDERR,"    Derived draw day {$draw->date} is in the future so manual results cannot be known\n");
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
        fwrite (STDERR,"    Number-match group '{$argv[5]}' has no prizes\n");
        exit (106);
    }
    $manual         = false;
    foreach ($draw->groups[$group] as $level) {
        if ($draw->prizes[$level]['results_manual']) {
            $manual = true;
            break;
        }
    }
    if (!$manual) {
        fwrite (STDERR,"    Number-match group '$group' is not manual\n");
        exit (107);
    }
    if (array_key_exists($group,$draw->results)) {
        fwrite (STDERR,"    Group '$group' @ $draw->closed already has results\n");
        exit (108);
    }
    $number         = $argv[6];
    if (!preg_match('<^[0-9]+$>',$number)) {
        fwrite (STDERR,"number can only contain digits 0 thru 9\n");
        exit (109);
    }
    if ($number<BLOTTO_TICKET_MIN || $number>BLOTTO_TICKET_MAX) {
        fwrite (STDERR,"    Number must be ".BLOTTO_TICKET_MIN." thru".BLOTTO_TICKET_MAX."\n");
        exit (110);
    }
    echo "    Building SQL for `".BLOTTO_RESULTS_DB."`\n";
    $qi             = "INSERT INTO `blotto_result`";
    $qi            .= " (`draw_closed`,`draw_date`,`prize_level`,`number`)";
    $qi            .= " VALUES\n";
    $count          = 0;
    foreach ($draw->groups[$group] as $level) {
        $count++;
        $qi         .= "('{$draw->closed}','{$draw->date}',$level,'$number'),\n";
    }
}

if (!$count) {
    fwrite (STDERR,"    No results to insert\n");
    exit (0);
}
$qi = substr($qi,0,-2).";\n";
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
    exit (111);
}


