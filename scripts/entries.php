<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

tee ("    Draw entry engine\n");

$ticket_db  = BLOTTO_TICKET_DB;
$price      = BLOTTO_TICKET_PRICE;
$org_ref    = strtoupper (BLOTTO_ORG_USER);
$quiet      = get_argument('q',$Sw) !== false;
$rbe        = get_argument('r',$Sw) !== false;
if ($rbe && !function_exists('enter')) {
    fwrite (STDERR,"Bespoke function enter() was not found\n");
    exit (101);
}

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}


echo "    Using database ".BLOTTO_MAKE_DB."\n"; 


echo "    Getting draws outstanding\n";

// Draw processed the day AFTER `draw_closed`
$close_dates            = draws_outstanding ();
if (!count($close_dates)) {
    tee ("    No entries to process\n");
    exit;
}


if ($rbe) {
    tee ("    Rule-based entry generation\n");
}
else {
    tee ("    Standard entry generation\n");
}


foreach ($close_dates as $date) {
    if ($rbe) {
        try {
            enter ($date);
        }
        catch (\Exception $e) {
            fwrite (STDERR,$e->getMessage()."\n");
            exit (103);
        }
        continue;
    }

    $date_dd = new DateTime ($date);
    $date_dd->sub (new DateInterval(BLOTTO_PAY_DELAY));  
    $date_dd = $date_dd->format ('Y-m-d');
    // If we get to this point, the draw has already closed
    // No insurance means no entry.
    $q = "
      SELECT
        `p`.`client_ref`
       ,`p`.`opening_balance` + IFNULL(`c_sum`.`PaidTotal`,0) - IFNULL(`e_summary`.`paid_out`,0) AS `balance`
       ,GROUP_CONCAT(DISTINCT(`tk`.`number`)) AS `ticket_numbers`

-- NEW INSURANCE BIT
       ,COUNT(DISTINCT(`tk`.`number`))<=`i`.`tickets_insured` AS `is_insured`

      FROM `blotto_player` AS `p`
      LEFT JOIN (
        SELECT
          ROUND($price*COUNT(`e`.`id`)/100,2) AS `paid_out`
         ,`e`.`client_ref`
        FROM `blotto_entry` AS `e`
        GROUP BY `e`.`client_ref`
      ) AS `e_summary`
        ON `e_summary`.`client_ref`=`p`.`client_ref`
      LEFT JOIN (
          SELECT
            SUM(`PaidAmount`) AS `PaidTotal`
           ,`ClientRef`
          FROM `blotto_build_collection`

-- HORRIBLE HACK
-- TODO: we need a better logical distinction between DD players and online players
-- Freq='Single' was one idea but eventually we will support online players with repeat payments
          WHERE (`ClientRef` LIKE 'STRP%' AND `DateDue`<='$date')
             OR (`ClientRef` NOT LIKE 'STRP%' AND `DateDue`<='$date_dd')

          GROUP BY `ClientRef`
      ) AS `c_sum`
        ON `c_sum`.`ClientRef`=`p`.`client_ref`
      JOIN `$ticket_db`.`blotto_ticket` AS `tk`
        ON `tk`.`client_ref`=`p`.`client_ref`

-- NEW INSURANCE BIT
      LEFT JOIN (
        SELECT
          `client_ref`
         ,COUNT(`ticket_number`) AS `tickets_insured`
        FROM `blotto_insurance`
        WHERE `org_ref`='$org_ref'
          AND `draw_closed`='$date'
        GROUP BY `client_ref`
           ) AS `i`
             ON `i`.`client_ref`=`tk`.`client_ref`

      WHERE `p`.`first_draw_close`<='$date'
      GROUP BY `p`.`id`
    ";
    echo rtrim($q)."\n";
    try {
        $result = $zo->query ($q);
        if (!$quiet) {
            echo "b";
        }
        $q = "
          INSERT INTO `blotto_entry`
          (`draw_closed`,`ticket_number`,`client_ref`)
          VALUES
        ";
        $n = 0;
        while ($r=$result->fetch_assoc()) {
            $insured        = $r['is_insured'];
            $cref           = $r['client_ref'];
            $balance        = $r['balance'];
            $ticket_numbers = explode (',',$r['ticket_numbers']);
            $chances        = count ($ticket_numbers);
            if ($balance>=round($price*$chances/100,2)) {
                if (!$insured && defined('BLOTTO_INSURE') && BLOTTO_INSURE && $date>=BLOTTO_INSURE_FROM) {
                    fwrite (STDERR,"WARNING: '$date', refusing to enter uninsured player '{$r['client_ref']}'\n");
                    fwrite (STDERR,"This could be a catch 22 if you have not thought about BLOTTO_INSURE_FROM\n");
                }
                else {

                    foreach ($ticket_numbers as $key => $ticket_number) {
                        $q     .= "( '$date', '$ticket_number', '$cref' ), ";
                        $n++;
                    }

                }
            }
            elseif ($balance<0) {
                fwrite (STDERR,"WARNING: ClientRef '$cref' has a negative balance!\n");
            } 
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$q."\n".$e->getMessage()."\n");
        exit (104);
    }
    if ($n) {
        $q              = substr ($q,0,-2);
        try {
            $result     = $zo->query ($q);
            if (!$quiet) {
                echo "c";
            }
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$q."\n".$e->getMessage()."\n");
            exit (105);
        }
        echo "$n tickets inserted into `blotto_entry` for `draw_closed`='$date'\n";
    }
    else {
        echo "no tickets for `draw_closed`='$date'\n";
    }
}


