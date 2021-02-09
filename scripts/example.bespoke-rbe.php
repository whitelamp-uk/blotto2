<?php

function draw_upcoming ($today=null) {
    // Close date of next draw to take place
    // Today should be included
    // For example, schedule is Friday 1 in June and December
    return draw_upcoming_dow_nths_in_months (5,[1],[6,12],$today);
}

function enter ($draw_closed) {
    // Entry rule = "all entrants for the same draw_closed"
    foreach (dbs() as $org_id=>$db) {
        enter_super (
            $org_id,
            $db,
            $draw_closed,
            entries ($draw_closed,$db['frontend'])
        );
    }
}

function prize_amount (&$prize,$verbose) {
    // Do nothing - just use the standard prize configuration
    // Or calculate a bespoke $prize['amount']
}


