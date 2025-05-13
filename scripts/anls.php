<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!strlen(BLOTTO_DRAW_CLOSE_1)) {
    fwrite (STDERR,"-- Warning: first draw close date is not yet configured so ANLs cannot be generated\n");
    exit (0);
}

$org = org ();
$earliest = BLOTTO_ANL_EMAIL_FROM;
$email = false;
$count = 0;
$api = false;


// Find an email API to use
$api = email_api ();
if ($api) {
    if (defined('BLOTTO_ANL_EMAIL') && BLOTTO_ANL_EMAIL) {
        if ($org['signup_cm_key'] && $org['anl_cm_id']) {
            $email = true;
            $api->keySet ($org['signup_cm_key']);
        }
        else {
            tee ("    No email API configuration for ANLs - see `blotto_org`.`signup_cm_key` and `blotto_org`.`anl_cm_id`\n");
        }
    }
    else {
        tee ("    Email API disabled for ANLs\n");
    }
}
else {
    tee ("    No email API found for ANLs\n");
}



// Find ANLs to email
$qs = "
  SELECT
    `a`.*
  FROM `ANLs` AS `a`
  JOIN `blotto_player` AS `p`
    ON (`p`.`letter_status`='' OR `p`.`letter_status` IS NULL)
   AND `p`.`client_ref`=`a`.`ClientRef`
  WHERE `a`.`tickets_issued`>='$earliest'
  ORDER BY `a`.`tickets_issued`,`a`.`ClientRef`
";
echo $qs;
$recipients = [];
try {
    $rows = $zo->query ($qs);
    while ($r=$rows->fetch_assoc()) {
        $recipients[] = $r;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (102);
}



// Send ANL emails
try {
    foreach ($recipients as $i=>$r) {
        $cref = esc ($r['ClientRef']);
        $recipients[$i]['cref'] = $cref;
        $recipients[$i]['emref'] = false;
        if ($email && $r['email']) {
            echo "    Emailing {$r['ClientRef']} {$r['email']}\n";
            $emref = $api->send (
                $org['anl_cm_id'],
                $r['email'],
                $r
            );
            if ($emref) {
                $recipients[$i]['emref'] = $emref;
                $count++;
                $emref = esc ($emref);
                $qu = "
                  UPDATE `blotto_player`
                  SET
                    `letter_batch_ref`='$emref'
                   ,`letter_status`='email_sent'
                  WHERE `client_ref`='$cref'
                  LIMIT 1
                ";
                echo $qu."\n";
                $zo->query ($qu);
                $qu = "
                  UPDATE `ANLs`
                  SET
                    `letter_batch_ref`='$emref'
                   ,`letter_status`='email_sent'
                  WHERE `ClientRef`='$cref'
                  LIMIT 1
                ";
                echo $qu."\n";
                $zo->query ($qu);
            }
            else {
                tee ("    Email for {$r['ClientRef']} failed: {$api->errorLast}\n");
                exit (102);
            }
        }
        else {
            if ($r['email']) {
                echo "    Email skipped for {$r['ClientRef']} {$r['email']}\n";
            }
            else {
                echo "    Email skipped for {$r['ClientRef']} (no email address)\n";
            }
            $qu = "
              UPDATE `blotto_player`
              SET
                `letter_status`='email_skipped'
              WHERE `client_ref`='$cref'
              LIMIT 1
            ";
            echo $qu."\n";
            $zo->query ($qu);
            $qu = "
              UPDATE `ANLs`
              SET
                `letter_status`='email_skipped'
              WHERE `ClientRef`='$cref'
              LIMIT 1
            ";
            echo $qu."\n";
            $zo->query ($qu);
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (103);
}



// SLEEP - Wait for bounces to propagate
if ($count) {
    sleep (BLOTTO_EMAIL_BOUNCE_DELAY);
}



// Check if emails received
if ($count) {
    try {
        foreach ($recipients as $i=>$r) {
            if ($r['emref']) {
                echo "    Get bounce status for {$r['ClientRef']} {$r['email']}\n";
                $received = $api->received ($org['anl_cm_id'],$r['emref']);
                if ($api->errorLast) {
                    tee ("    Email status for {$r['ClientRef']} failed: {$api->errorLast}\n");
                    fwrite (STDERR,$api->errorLast."\n");
                    continue; // Try again next build
                }
                else {
                    $emref = esc ($emref);
                    if ($received) {
                        $qu = "
                          UPDATE `blotto_player`
                          SET
                            `letter_status`='email_received'
                          WHERE `client_ref`='{$r['cref']}'
                          LIMIT 1
                        ";
                        echo $qu."\n";
                        $zo->query ($qu);
                        $qu = "
                          UPDATE `ANLs`
                          SET
                            `letter_status`='email_received'
                          WHERE `ClientRef`='{$r['cref']}'
                          LIMIT 1
                        ";
                        echo $qu."\n";
                        $zo->query ($qu);
                    }
                    else {
                        $qu = "
                          UPDATE `blotto_player`
                          SET
                            -- Snailmail is triggered by this status
                            `letter_status`='email_bounced'
                          WHERE `client_ref`='{$r['cref']}'
                          LIMIT 1
                        ";
                        echo $qu."\n";
                        $zo->query ($qu);
                        $qu = "
                          UPDATE `ANLs`
                          SET
                            `letter_status`='email_bounced'
                          WHERE `ClientRef`='{$r['cref']}'
                          LIMIT 1
                        ";
                        echo $qu."\n";
                        $zo->query ($qu);
                    }
                }
            }
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$e->getMessage()."\n");
        exit (105);
    }
}




// Use snailmail where email not received (checked within snailmail functions)
try {
    if (defined('BLOTTO_SNAILMAIL') && BLOTTO_SNAILMAIL) {
        // Snailmail is active
        if (defined('BLOTTO_SNAILMAIL_TPL_ANL') && BLOTTO_SNAILMAIL_TPL_ANL) {
            tee ("    Updating ANLs.letter_status using snailmail API\n");
            snailmail_anls_status ();
            tee ("    Sending ANLs using snailmail API\n");
            $results = snailmail_anls ();
            tee ("      {$results['recipients']} mailpieces\n");
            print_r ($results);
        }
        else {
            tee ("    No postal API template is active for ANLs\n");
        }
    }
    else {
        tee ("    No postal API is active for ANLs\n");
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (106);
}

