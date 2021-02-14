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


