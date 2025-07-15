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
$dgo = false;
if (array_key_exists(3,$argv) && strpos($argv[3],'-')===0) {
    if (strpos($argv[3],'z')) {
        $pfz = true;
    }
    if (strpos($argv[3],'d')) {
        // digest only
        $dgo = true;
    }
}
$pfzc = defined('BLOTTO_DEV_PAY_FREEZE') && BLOTTO_DEV_PAY_FREEZE;
$dc1 = strlen(BLOTTO_DRAW_CLOSE_1) > 0;
$anl = defined('BLOTTO_ANL_EMAIL') && BLOTTO_ANL_EMAIL;
$pzc = count(prizes(draw_upcoming())) && true;


$headers = null;
if (defined('BLOTTO_EMAIL_FROM')) {
    $headers = "From: ".BLOTTO_EMAIL_FROM."\n";
}

$warning = '';
if ($pfz || $pfzc || !$dc1 || !$anl || !$pzc) {
    $warning = " with warning(s)";
}

$dt = date ('Y-m-d H:i:s');
$brd = BLOTTO_BRAND;
$org = BLOTTO_ORG_NAME;
$mc = BLOTTO_MC_NAME;




// digest
$fp = fopen (BLOTTO_DIGEST,'a');
if ($dgo) {
    if ($warning) {
        $line = "$dt $org OK $warning\n";
    }
    else {
        $line = "$dt $org OK\n";
    }
}
else {
    $line = "$dt $org FAIL\n";
}
fwrite ($fp,$line);
fclose ($fp);




// mail
if ($dgo) {
    // digest only
    exit (0);
}
$body = "Message$warning at $dt\n".$argv[2]."\n";
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
if (!$pzc) {
    $body .= "
Warning: no prizes are defined for next draw";
}
mail (
    BLOTTO_EMAIL_WARN_TO,
    "$brd - Status report for $org from $mc",
    $body,
    $headers
);

