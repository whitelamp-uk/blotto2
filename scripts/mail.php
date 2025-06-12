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
$dc1 = strlen(BLOTTO_DRAW_CLOSE_1) > 0;
$anl = defined('BLOTTO_ANL_EMAIL') && BLOTTO_ANL_EMAIL;

$headers = null;
if (defined('BLOTTO_EMAIL_FROM')) {
    $headers = "From: ".BLOTTO_EMAIL_FROM."\n";
}

$warning = '';
if ($pfz || $pfzc || !$dc1 || !$anl) {
    $warning = " with warning(s)";
}

$body = "Message$warning at ".date('Y-m-d H:i:s')."\n".$argv[2]."\n";

if ($pfz || $pfzc) {
    $body .= "
Warning: pay freeze is set so the following prevented:
 * DDI instructions
 * DDI collections
 * draw entries
 * draws
 * cancellations
 * CRM milestones
This happened because";
    if ($pfzc) {
        $body .= " - the constant BLOTTO_DEV_PAY_FREEZE is true";
    }
    if ($pfzc && $pfz) {
        $body .= " and also";
    }
    if ($pfz) {
        $body .= " - the build CLI option -z was used";
    }
}
if (!$dc1) {
    $body .= "
Warning: first draw close is not set so the following prevented:
 * player dates for first draw close
 * ANLs";
    if ($pfzc && $pfz) {
        $body .= "
 * draw entries
 * draws";
    }
    $body .= "
This happened because constant BLOTTO_DRAW_CLOSE_1 is empty";
}
if (!$anl) {
    $body .= "
Warning: BLOTTO_ANL_EMAIL is not true so ANL emails are not activated";
}


mail (
    BLOTTO_EMAIL_WARN_TO,
    BLOTTO_BRAND." - Status report for ".BLOTTO_ORG_NAME." from ".BLOTTO_MC_NAME,
    $body,
    $headers
);


