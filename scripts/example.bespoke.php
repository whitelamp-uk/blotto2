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
    // Do nothing - just use the standard prize configuration
    return;
    // Or calculate a bespoke $prize['amount']
    if (strpos(date('F'),'r')) {
        // If there is an "r" in the month, add a quid
        $prize['amount'] += 1.00;
    }
}

