<?php
//

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

/* caching now done inside functions by writing a file to /tmp like so:
    /tmp/xyz-profits-2025-12-11.json
    /tmp/xyz-revenue-2025-01-01-2025-11-28.json (and calculate)
    But we trim here in the build so that "manual" calculations get cached for the rest of the day
    only unlink org-profits-Y-m-d (etc) except for quarterly calculations.
*/


$files = glob(BLOTTO_TMP_DIR.'/'.BLOTTO_ORG_USER.'-*.json');
foreach ($files as $file) {
  $fileparts = explode('-',$file,3);
  // if paranoid then check 
  if (DateTime::createFromFormat('Y-m-d',substr($fileparts[2],0,10))) { // check first part of expected date(s) is a date
    switch ($fileparts[1]) {
      case 'calculate':
      case 'revenue':
        $from = substr($fileparts[2],5,5);
        $to =  substr($fileparts[2],16,5);
        //echo $from.' '.$to."\n";
        if (
             ($from == '01-01' && $to == '03-31') ||
             ($from == '04-01' && $to == '06-30') ||
             ($from == '07-01' && $to == '09-30') ||
             ($from == '10-01' && $to == '12-31')
             ) {
          echo "skip $file\n";
          break;
        }
      case 'profits':
        echo 'unlink '.$file."\n";
    }
  }
}

exit; // temporary

echo "    Caching default data by running profits() ... ";
$t0 = time (); profits (true); // argument=true puts timing diagnostic in the error log
echo "    done in ".(time()-$t0)." seconds\n";

// reconcile.php first loads with the default date range "this calendar year so far"
$to     = day_yesterday()->format ('Y-m-d');
$from   = substr($to,0,4).'-01-01';

echo "    Caching default data by running calculate('$from','$to') ... ";
$t0 = time (); calculate ($from,$to);
echo "    done in ".(time()-$t0)." seconds\n";

echo "    Caching default data by running revenue('$from','$to') ... ";
$t0 = time (); revenue ($from,$to);
echo "    done in ".(time()-$t0)." seconds\n";

$md = substr($to,5,5); // month-day
if (in_array($md, ['06-30', '09-30', '12-31'])) { // end of quarter. '03-31' not needed - done above.
    $mto = substr($to,5,2);
    $mfrom= str_pad($mto-2, 2, '0', STR_PAD_LEFT);
    $from = substr($to,0,4).'-'.$mfrom.'-01';
    echo "    Caching quarterly data by running calculate('$from','$to') ... ";
    $t0 = time (); calculate ($from,$to);
    echo "    done in ".(time()-$t0)." seconds\n";
    echo "    Caching quarterly data by running revenue('$from','$to') ... ";
    $t0 = time (); revenue ($from,$to);
    echo "    done in ".(time()-$t0)." seconds\n";
}

/*
user date selections will still be slow but at least
the first page load of the day should be quicker

draws() is fast so no need to cache.

NB reconcile.php logs subsequent execution times to the browser console
*/

