<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


if (!array_key_exists(2,$argv)) {
    exit (121);
}

if (!defined($argv[2])) {
    echo "\n";
    exit;
}

if (constant($argv[2])===false) {
    echo "\n";
    exit;
}

echo constant($argv[2])."\n";

