<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;

header ('Access-Control-Allow-Origin: *');

$f = empty($_GET['f']) ? null : $_GET['f'];

$w = www_winners ($f);

if (isset($w->date)) {
    table (
        'lottery-winners-latest-table',
        '',
        'Winners for '.$w->date,
        ['Ticket','Prize'],
        $w->winners,
        true,
        false,
        $w->classes->winners
    );

    if ($w->number_matches && count($w->number_matches)) {
        table (
            'lottery-results-latest-table',
            '',
            'Draw results for '.$w->date,
            ['Prize','Number'],
            $w->number_matches,
            true,
            false,
            $w->classes->number_matches
        );
    }
}
