<?php

function chances ($frequency,$amount) {
    // Calculate ticket entitlement
    // Library function for calculating weekly draw chances:
    // return chances_weekly ($frequency,$amount);
    // 5 draws per month model:
    if ($frequency=='Monthly' || $frequency==1) {
        $ratio = 5;
    }
    elseif ($frequency=="Quarterly" || $frequency==3) {
        $ratio = 15;
    }
    elseif ($frequency=="Six Monthly" || $frequency==6) {
        $ratio = 30;
    }
    elseif ($frequency=="Annually" || $frequency==12) {
        $ratio = 60;
    }
    else {
        throw new \Exception ('bespoke chances(): frequency "$frequency" not recognised');
        return false;
    }
    return intval((100*$amount)/round($ratio*BLOTTO_TICKET_PRICE));
}

function draw_first ($first_collection_date,$ccc,$opening_balance=0,$chances=1) {
    // calculate player first draw date based on first collection and canvassing company code

    if (in_array($ccc,['STRP','CDNT','GRTS'])) {
        // debit cards and gratis-api tickets require no balance accumulation
        return draw_first_asap ($first_collection_date,null,$opening_balance,$chances);
    }

    // if five draws a month no need to "buffer" the money to deal with months with five Fridays 
    // but still need to deal with BACS jitter (and maybe a bit more)
    return draw_first_asap ($first_collection_date, BLOTTO_PAY_DELAY,$opening_balance,$chances);

    // library function for the balance accumulation model
    return draw_first_zaffo_model ($first_collection_date,5,$opening_balance,$chances);
}

function draw_insuring ($today=null) {
    // Which draw are we insuring today?
    // Customisation is possible here but no purpose for such has been identified
    return insurance_draw_close ($today);
}

function draw_upcoming ($today=null) {
    // Close date of next draw to take place
    if (!$today) {
        // Today is real-time actually today if not specified yyyy-mm-dd
        $today = gmdate ('Y-m-d');
    }
    $month = substr ($today,0,7); // get the month of today yyyy-mm
    // Get next Friday (today is included)
    $day_next = draw_upcoming_weekly (5,$today); // our existing weekly draw function
    // but next draw close might be a Tuesday so:
    $day_4 = gmdate ('Y-m-d',strtotime("$month-00 fourth friday"));
    $day_5 = gmdate ('Y-m-d',strtotime("$month-00 fifth friday"));
    if (substr($day_5,0,7)>substr($day_4,0,7)) {
        // fifth Fri is next month so when is the last Tuesday?
        $day_4 = gmdate ('Y-m-d',strtotime("$month-00 fourth tuesday"));
        $day_5 = gmdate ('Y-m-d',strtotime("$month-00 fifth tuesday"));
        if (substr($day_5,0,7)==substr($day_4,0,7)) {
            // fifth Tue is this month
            $day_next_alt = $day_5;
        }
        else {
            // fifth Tue is next month
            $day_next_alt = $day_4;
        }
        if ($day_next_alt>=$today && $day_next_alt<$day_next) {
            // alternative draw close is earlier than the next "normal" draw day
            $day_next = $day_next_alt;
        }
    }
    return $day_next;
}

function prize_amount (&$prize,$verbose=false) {
    // Do nothing - just use the standard prize table configuration
    return;
}

