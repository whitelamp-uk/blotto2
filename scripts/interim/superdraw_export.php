<?php

require __DIR__.'/../functions.php';
cfg ();
require $argv[1];

tee ("    Generating Zaffo superdraw results (interim solution)\n");

// Get first Wednesday of this month
$today = date ('Y-m-d');
$dt = new DateTime (date('Y-m').'-01');
while (1) {
    if ($dt->format('w')==3) {
        $wed = $dt->format('Y-m-d');
        break;
    }
    $dt->add (new DateInterval('P1D'));
}
if ($wed<$today) {
    // Get first Wednesday of next month
    $dt = new DateTime (date('Y-m').'-01');
    $dt->add (new DateInterval('P1M'));
    while (1) {
        if ($dt->format('w')==3) {
            $wed = $dt->format ('Y-m-d');
            break;
        }
        $dt->add (new DateInterval('P1D'));
    }
}
// First day of Wednesday's month
$dt = new DateTime ($wed);
$dt = new DateTime ($dt->format('Y-m').'-01');
// Last Friday of previous month
while (1) {
    $dt->sub (new DateInterval('P1D'));
    if ($dt->format('w')==5) {
        $fri = $dt->format ('Y-m-d');
        break;
    }
}
if ($today<=$fri) {
    echo "    Superdraw entries cannot be calculated until after $fri\n";
    exit (0);
}
$month = substr ($fri,0,7);

// Superdraw entries for (last month,draw date)
$qs = "CALL zaffoSuperdrawEntries('$month','$wed')";
echo "    $qs\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}
try {
    $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (102);
}

