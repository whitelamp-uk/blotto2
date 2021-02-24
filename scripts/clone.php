<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

// Define
$dbm    = BLOTTO_MAKE_DB;
if (!array_key_exists(2,$argv)) {
    fwrite (STDERR,"    Origin database not given\n");
    exit (101);
}
$dbo    = array_pop ($argv);
$ztest  = connect ($dbo);
if (!$ztest) {
    fwrite (STDERR,"    Could not connect to origin database `$dbo`\n");
    exit (102);
}


// Confirm
echo "    Cloning from `$dbo` to `$dbm`\n";
echo "    Continue? [y/N] ";
$ok     = fread (STDIN,2);
if (trim($ok)!='Y' && trim($ok)!='y') {
    exit (0);
}

// Clone
$zo     = connect ($dbm);
if (!$zo) {
    exit (103);
}

$tables = [
    'blotto_change',
    'blotto_contact',
    'blotto_entry',
    'blotto_generation',
    'blotto_insurance',
    'blotto_player',
    'blotto_prize',
    'blotto_result',
    'blotto_supporter',
    'blotto_ticket'
];

foreach ($tables as $table) {
    echo "        Table `$table` ... ";
    $qs = "SELECT * FROM `$dbo`.`$table` LIMIT 0,1";
    try {
        $result = $zo->query ($qs);
        $qs = "SELECT * FROM `$table` LIMIT 0,1";
        $result = $zo->query ($qs);
        if ($result->num_rows) {
            echo "SKIPPED (`$dbm`.`$table` already has data)\n";
            continue;
        }
        $qi = "INSERT INTO `$table` SELECT * FROM `$dbo`.`$table`;";
        try {
            $result = $zo->query ($qi);
            echo "CLONED\n";
        }
        catch (\mysqli_sql_exception $e) {
            echo "            ".$e->getMessage()."\n";
            echo "            Failed to clone (perhaps `$dbm`.`$table` is missing?)\n";
        }
    }
    catch (\mysqli_sql_exception $e) {
        echo "not found in `$dbo`\n";
    }
}

exit (0);


