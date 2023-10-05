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

$headers = null;
if (defined('BLOTTO_EMAIL_FROM')) {
    $headers = "From: ".BLOTTO_EMAIL_FROM."\n";
}

$warning = '';
if (defined('BLOTTO_DEV_PAY_FREEZE') && BLOTTO_DEV_PAY_FREEZE) {
    $warning = " with warning(s)";
}
$body = "Message$warning at ".date('Y-m-d H:i:s')."\n".$argv[2]."\n";
if (defined('BLOTTO_DEV_PAY_FREEZE') && BLOTTO_DEV_PAY_FREEZE) {
    $body .= "Warning: BLOTTO_DEV_PAY_FREEZE=true so no payment data was processed and no cancellations were calculated.\n";
}
echo $body; exit;
mail (
    BLOTTO_EMAIL_WARN_TO,
    BLOTTO_BRAND." - Status report for ".BLOTTO_ORG_NAME." from ".BLOTTO_MC_NAME,
    $body,
    $headers
);


