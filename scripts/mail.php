<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

if (!BLOTTO_EMAIL_WARN_ON) {
    fwrite (STDERR,"mail.php() emailing is off - see BLOTTO_EMAIL_WARN_ON\n");
    exit (0);
}

if (!array_key_exists(2,$argv) || !trim($argv[2])) {
    fwrite (STDERR,"mail.php() no message given\n");
    exit (123);
}

$pfz = false;
if (array_key_exists(3,$argv) && $argv[3]=='-z') {
    $pfz = true;
}
$pfzc = defined('BLOTTO_DEV_PAY_FREEZE') && BLOTTO_DEV_PAY_FREEZE;


$headers = null;
if (defined('BLOTTO_EMAIL_FROM')) {
    $headers = "From: ".BLOTTO_EMAIL_FROM."\n";
}

$warning = '';
if ($pfz || $pfzc) {
    $warning = " with warning(s)";
}

$body = "Message$warning at ".date('Y-m-d H:i:s')."\n".$argv[2]."\n";

if ($pfz || $pfzc) {
    $body .= "
Warning: pay freeze was used on this build; all the following were prevented:
 * processing of new DDI instructions
 * fetching of payment collection data
 * recording of draw entries
 * uncompleted draws
 * recalculation of cancellations
 * recording of CRM milestones";
}

if ($pfzc) {
    $body .= "
This happened because the constant BLOTTO_DEV_PAY_FREEZE is true";
}
else {
    $body .= "
This happened because the build CLI option -z was used";
}

mail (
    BLOTTO_EMAIL_WARN_TO,
    BLOTTO_BRAND." - Status report for ".BLOTTO_ORG_NAME." from ".BLOTTO_MC_NAME,
    $body,
    $headers
);


