<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

tee ("    Draw entry engine\n");

$ticket_db  = BLOTTO_TICKET_DB;
$price      = BLOTTO_TICKET_PRICE;
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

/*

TODO: below is all very hacky

We have bespoke PHP function:
    draw_first ($first_collection_date,$ccc)
which is no use in the SQL restrict-before-SUM() problem below:
    `DateDue`<='not just one date'

Which suggests we really to replace current PHP functions with SQL:
    drawClosedFirst(collectedFirst,ccc) - org.bespoke.functions.sql
    drawClosedFirstAsap(collectedFirst) - db.functions.sql
    drawClosedFirstZaffoModel(collectedFirst) - db.functions.sql

Then the SQL below can use drawClosedFirst() rather than
  * rely on generic, un-customisable BLOTTO_PAY_DELAY
  * have to hack the sum of PaidAmount as below

Like this:
    `DateDue` <= drawClosedFirst('$date',`blotto_supporter`.`canvas_code`)

*/

    $date_dd = new DateTime ($date);
    $date_dd->sub (new DateInterval(BLOTTO_PAY_DELAY));  
    $date_dd = $date_dd->format ('Y-m-d');
    $q = "
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
          SELECT
            SUM(`PaidAmount`) AS PaidTotal
           ,`ClientRef`
          FROM `blotto_build_collection`

-- HORRIBLE HACK
          WHERE (`ClientRef` LIKE 'STRP%' AND `DateDue`<='$date')
             OR (`ClientRef` NOT LIKE 'STRP%' AND `DateDue`<='$date_dd')

          GROUP BY `ClientRef`
      ) AS `c_sum`
        ON `c_sum`.`ClientRef`=`p`.`client_ref`
      JOIN `$ticket_db`.`blotto_ticket` AS `tk`
        ON `tk`.`client_ref`=`p`.`client_ref`
      WHERE `p`.`first_draw_close`<='$date'
      GROUP BY `p`.`id`
    ";
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
            $cref           = $r['client_ref'];
            $balance        = $r['balance'];
            $ticket_numbers = explode (',',$r['ticket_numbers']);
            $chances        = count ($ticket_numbers);
            if ($balance>=round($price*$chances/100,2)) {
                foreach ($ticket_numbers as $key => $ticket_number) {
                    $q     .= "( '$date', '$ticket_number', '$cref' ), ";
                    $n++;
                }
            }
            elseif ($balance<0) {
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


