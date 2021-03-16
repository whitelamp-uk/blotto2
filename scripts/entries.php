<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$quiet      = get_argument('q',$Sw) !== false;
$rbe        = get_argument('r',$Sw) !== false;
$ticket_db  = BLOTTO_TICKET_DB;
$price      = BLOTTO_TICKET_PRICE;

echo "    Draw entry engine\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!function_exists('enter')) {
    fwrite (STDERR,"Bespoke function enter() was not found\n");
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
    $date_due           = new DateTime ($date);
// Removed because this is now the job of rsm-api.git or other API
// reinstated because it is needed for a "historical" build.
    $date_due->sub (new DateInterval(BLOTTO_PAY_DELAY));  
    $date_due_string    = $date_due->format ('Y-m-d');
    $q                  = "
      SELECT
        `p`.`client_ref`
       ,`c_sum`.`PaidTotal` - IFNULL(`e_summary`.`paid_out`,0) AS `balance`
       ,GROUP_CONCAT(DISTINCT(`tk`.`number`)) AS `ticket_numbers`
      FROM `blotto_player` AS `p`
      LEFT JOIN (
        SELECT
          ROUND($price*COUNT(`e`.`id`)/100,2) AS `paid_out`
         ,`e`.`client_ref`
        FROM `blotto_entry` AS `e`
        GROUP BY `e`.`client_ref`
      ) AS `e_summary`
        ON `e_summary`.`client_ref`=`p`.`client_ref`
      JOIN (
          SELECT SUM(`c`.`PaidAmount`) as PaidTotal, `c`.`ClientRef`
          FROM `blotto_build_collection` AS `c`
          WHERE `c`.`DateDue`<='$date_due_string'
          GROUP BY `c`.`ClientRef`
      ) AS `c_sum`
        ON `c_sum`.`ClientRef`=`p`.`client_ref`
      JOIN `$ticket_db`.`blotto_ticket` AS `tk`
        ON `tk`.`client_ref`=`p`.`client_ref`
      WHERE `p`.`first_draw_close`<='$date'
      GROUP BY `p`.`id`
    ";
    try {
        $result         = $zo->query ($q);
        if (!$quiet) {
            echo "b";
        }
        $q                  = "
          INSERT INTO blotto_entry
          (`draw_closed`, `ticket_number`, `client_ref`)
          VALUES
        ";
        $n = 0;
        while ($r = $result->fetch_assoc()) {
            $cref           = $r['client_ref'];
            $balance        = $r['balance'];
            $ticket_numbers = explode (',',$r['ticket_numbers']);
            $chances = count($ticket_numbers);
            if ($balance >= round($price*$chances/100,2)) {
                foreach ($ticket_numbers as $key => $ticket_number) {
                    $q  .= "( '$date', '$ticket_number', '$cref' ), ";
                    $n++;
                }
            }
            elseif ($balance < 0) {
                fwrite (STDERR,"ClientRef '$cref' has a negative balance!\n");
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


