<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$file = BLOTTO_DAILY_CFG;
if (is_readable($file) && gmdate("Y-m-d",filemtime($file))==gmdate('Y-m-d')) {
        error_log("daily config ".BLOTTO_DAILY_CFG." from today");
        return;
}
error_log("new daily config ".BLOTTO_DAILY_CFG." required");
$today = date('Y-m-d');

$end_date = days_working_date ($today, BLOTTO_WORKING_DAYS_DELAY);

$d1 = new DateTime($today);
$d2 = new DateTime($end_date);
$interval = $d1->diff($d2);
$num = $interval->format("%d");

$output  = "<?php\n";
$output .= "define ( 'BLOTTO_PAY_DELAY', 'P".$num."D' );";
file_put_contents($file, $output);

