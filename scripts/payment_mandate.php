<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

echo "    Generating/posting mandate data\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

try {
    $constants      = get_defined_constants (true);
    $apis           = 0;
    $mandate_count  = 0;
    foreach ($constants['user'] as $name => $classfile) {
        if (!preg_match('<^BLOTTO_PAY_API_[A-Z]+$>',$name)) {
            continue;
        }
        if (!is_readable($classfile)) {
            fwrite (STDERR,"Payment API file '$classfile' is not readable - aborting\n");
            exit (102);
        }
        fwrite (STDERR,"Processing payment API class file: $classfile\n");
        require $classfile;
        $class      = constant ($name.'_CLASS');
        if (!class_exists($class)) {
            fwrite (STDERR,"Payment API class '$class' does not exist - aborting\n");
            exit (103);
        }
        $api        = new $class ($zo);
        echo "    Instantiated $class\n";
        if (method_exists($api,'insert_mandates')) {
            foreach (glob(BLOTTO_CSV_DIR_S.'/*/supporters-flc.*.csv') as $csv) {
                // Get new candidates
                $mandates = [];
                $qs = "
                  SELECT
                    `cand`.*
                  FROM `tmp_supporter` AS `cand`
                  LEFT JOIN (
                    SELECT
                      DISTINCT(`ClientRef`) AS `crf`
                    FROM `rsm_mandate`
                  ) AS `m`
                    ON `m`.`crf`=`cand`.`ClientRef`
                  WHERE `m`.`crf` IS NULL
                ";
                try {
                    $ms = $zo->query ($qs);
                    while ($m=$ms->fetch_assoc()) {
                        $mandates[] = $m;
                    }
                }
                catch (\mysqli_sql_exception $e) {
                    fwrite (STDERR, $qs."\n".$zo->error."\n");
                    exit (104);
                }
                $api->insert_mandates ($mandates);
                $mandate_count += count ($mandates);
                $apis++;
            }
            echo "    Exported $mandate_count mandates using $class\n";
            echo "    Only one mandate-creation API is allowed currently\n";
            break;
        }
    }
    if ($apis) {
        echo "    Imported payments using $apis APIs\n";
    }
    else {
        echo ("   Warning: no API method for creating mandates was found\n");
    }
    echo "    Imported payments using $apis APIs\n";
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    if (!$api->errorCode) {
        // Unexpected error
        exit (104);
    }
    exit ($api->errorCode);
}

