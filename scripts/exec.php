<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

// At least a function name argument
if (!array_key_exists(2,$argv)) {
    exit (121);
}

// Function must exist
if (!function_exists($argv[2])) {
    exit (122);
}

// Must be a bespoke function
$f = new ReflectionFunction ($argv[2]);
if (realpath($f->getFileName())!=realpath(BLOTTO_BESPOKE_FUNC)) {
    exit (123);
}

// Execute
$a = $argv;
array_shift ($a);
array_shift ($a);
$f = array_shift ($a);

// Return
echo trim ($f(...$a)) ."\n";


