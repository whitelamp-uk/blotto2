<?php

require '/home/blotto/config/dbh.cfg.php';

if (defined('BLOTTO_INSURE_DAYS')) {
echo "BLOTTO_INSURE_DAYS = ".BLOTTO_INSURE_DAYS."\n";
}

require '/home/mark/blotto/blotto2/scripts/functions.php';

echo draw_first_asap ($argv[1]);

echo "\n";

