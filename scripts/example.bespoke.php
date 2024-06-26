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
        throw new \Exception ('chances_weekly(): frequency "$frequency" not recognised');
        return false;
    }
    return intval((100*$amount)/round($ratio*BLOTTO_TICKET_PRICE));
}

function draw_first ($first_collection_date,$ccc) {
    // Calculate player first draw date based on
    // first collection and canvassing company
    if (in_array($ccc,['STRP','CDNT','GRTS'])) {
        // Debit cards and gratis-api tickets require no balance accumulation
        return draw_first_asap ($first_collection_date);
    }
    // Library function for the balance accumulation model
    return draw_first_zaffo_model ($first_collection_date,5);
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
    $day_4 = strtotime ("$month-00 fourth friday");
    $day_5 = strtotime ("$month-00 last friday");
    if ($day_5==$day_4) {
        // fourth Fri is the last Fri so when is the last Tuesday?
        $day_next_alt = strtotime ("$month-00 last tuesday");
        if ($day_next_alt<$day_next) {
            // alternative draw close for today's month is earlier than the "normal" Fri
            return $day_next_alt;
        }
    }
    // in all other cases the "normal" Fri
    return $day_next;
}

function prize_amount (&$prize,$verbose=false) {
    // Do nothing - just use the standard prize table configuration
    return;
}

