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

mail (
    BLOTTO_EMAIL_WARN_TO,
    BLOTTO_BRAND." - Status report for ".BLOTTO_ORG_NAME." from ".BLOTTO_MC_NAME,
    "Message at ".date('Y-m-d H:i:s')." was:\n".$argv[2],
    $headers
);


