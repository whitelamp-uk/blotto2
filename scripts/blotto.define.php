<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];



if (!array_key_exists(4,$argv)) {
	echo "\n";
}

if (!defined($argv[4])) {
	echo "\n";
}

echo constant($argv[4])."\n";

