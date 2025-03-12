<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

/*
// chart SQL is faster than it used to be
echo "    Caching slow chart queries for front-end graphics\n";

$since = new DateTime ();
$since->sub (new DateInterval('P3M'));
$since = $since->format ('Y-m-d');
echo "        \$since = $since;\n";
$lines          = file (__DIR__.'/../www/views/summary.php');
$regexp         = '/<\?php\s+echo\s+chart\s*\((.*)\)/';

foreach ($lines as $line) {
    $matches    = [];
    preg_match ($regexp,$line,$matches);
    if (!array_key_exists(1,$matches)) {
        continue;
    }
    $code       = "chart (".$matches[1].");";
    echo "        $code\n";
    eval ($code);
}

profits ();
*/


// but it might help to run reconcile.php functions - which are pretty hardcore.

echo "    Caching SQL by running profits() ... ";
$t0 = time (); profits ();
echo "    done in ".(time()-$t0)." seconds\n";

// reconcile.php first loads with the default date range "this calendar year so far"
$to     = day_yesterday()->format ('Y-m-d');
$from   = substr($to,0,4).'-01-01';

echo "    Caching SQL by running calculate('$from','$to') ... ";
$t0 = time (); calculate ($from,$to);
echo "    done in ".(time()-$t0)." seconds\n";

echo "    Caching SQL by running revenue('$from','$to') ... ";
$t0 = time (); revenue ($from,$to);
echo "    done in ".(time()-$t0)." seconds\n";

echo "    Caching SQL by running draws('$from','$to') ... ";
$t0 = time (); draws ($from,$to);
echo "    done in ".(time()-$t0)." seconds\n";

/*
user date selections will still be slow but at least
the first page load of the day should be quicker

NB reconcile.php logs subsequent execution times to the browser console
*/

