<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$interval = BLOTTO_DD_TRY_INTERVAL;
$errors = [];

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
        fwrite (STDERR,__FILE__.": processing payment API class file: $classfile\n");
        require $classfile;
        $class      = constant ($name.'_CLASS');
        if (!class_exists($class)) {
            fwrite (STDERR,"Payment API class '$class' does not exist - aborting\n");
            exit (103);
        }
        $api        = new $class ($zo);
        if (method_exists($api,'reset_fakes')) {
            // Migrating mandates
            $api->reset_fakes ();
        }
        echo "    Instantiated $class\n";
        if (method_exists($api,'insert_mandates')) {
            // Get new candidates
            $mandates = [];
            $select = constant ($name.'_SELECT');
            $qs = "
              SELECT
                `cand`.*
              FROM `tmp_supporter` AS `cand`
--              LEFT JOIN `blotto_supporter` AS `s`
-- a mandate is a weak entity compared to a supporter
              JOIN `blotto_supporter` AS `s`
                ON `s`.`client_ref`=`cand`.`ClientRef`
              LEFT JOIN (
                $select
              ) AS `m`
                ON `m`.`crf`=`cand`.`ClientRef`
              -- No mandate exists
              WHERE `m`.`crf` IS NULL
                AND (
                -- Either no supporter exists
--                     `s`.`id` IS NULL
-- was made obsolete by above change
                     0
                -- Or the supporter was inserted recently
                  OR `s`.`inserted`>DATE_SUB(NOW(),INTERVAL $interval)
              )
            ";
            try {
                $ms = $zo->query ($qs);
                while ($m=$ms->fetch_assoc()) {
                    if (territory_permitted($m['Postcode'])) {
                        $mandates[] = $m;
                    }
                    else {
                        $e = "Postcode '{$m['Postcode']}' is outside territory '".BLOTTO_TERRITORIES_CSV."' - $ccc - for '{$m['ClientRef']}'\n";
                        fwrite (STDERR,$e);
                        $errors[] = $e;
                    }
                }
                if ($bad=count($errors)) {
                    $message = "The following $bad mandates have been rejected:\n";
                    foreach ($errors as $e) {
                        $message .= $e;
                    }
                    notify (BLOTTO_EMAIL_WARN_TO,"$bad rejected mandates",$message);
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

