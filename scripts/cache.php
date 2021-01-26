<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

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

