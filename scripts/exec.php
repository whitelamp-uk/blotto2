<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

if (!array_key_exists(2,$argv)) {
    exit (121);
}

if (!function_exists($argv[2])) {
    exit (122);
}

$f = new ReflectionFunction ($argv[2]);
if (realpath($f->getFileName())!=realpath(BLOTTO_BESPOKE_FUNC)) {
    exit (123);
}

$a = $argv;
array_shift ($a);
array_shift ($a);
$f = array_shift ($a);

echo trim ($f(...$a)) ."\n";


