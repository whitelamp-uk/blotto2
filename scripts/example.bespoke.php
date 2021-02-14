<?php

function chances ($frequency,$amount) {
    // Calculate ticket entitlement
    // Library function for calculating weekly draw chances:
    return chances_weekly ($frequency,$amount);
}

function draw_first ($first_collection_date) {
    // Calculate player first draw date based on first collection received
    // Library function for the Zaffo model
    return draw_first_zaffo_model ($first_collection_date);
}

function draw_upcoming ($today=null) {
    // Close date of next draw to take place
    // Today is included
    // Library function for weekly draw on a given dow
    // For example, Friday draws
    return draw_upcoming_weekly (5,$today);
}

function prize_amount (&$prize,$verbose) {
    // Do nothing - just use the standard prize table configuration
    return;
    // Or calculate a prize pot at £x per £1k of revenue
    if (!array_key_exists('draw_closed',$prize)) {
        throw new \Exception ('prize[draw_closed] required to calculate prize pot');
        return false;
        if ($prize['level']==1) {
            // 5% of the pot (£50 per $1k)
            $prize['amount']  = prize_pot ($prize['draw_closed'],50,$verbose);
            return;
        }
        if ($prize['level']==2) {
            // 0.5% of the pot (£5 per $1k)
            $prize['amount']  = prize_pot ($prize['draw_closed'],5,$verbose);
            return;
        }
        return;
    }
    // Or calculate another bespoke $prize['amount']
    if (strpos(date('F'),'r')) {
        // If there is an "r" in the month, add a quid
        $prize['amount'] += 1.00;
    }
}

