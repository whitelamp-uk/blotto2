<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

echo "    Generating/posting mandate data\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

try {
    $constants      = get_defined_constants (true);
    $apis = 0;
    foreach ($constants['user'] as $name => $classfile) {
        if (!preg_match('<^BLOTTO_PAY_API_[A-Z]+$>',$name)) {
            continue;
        }
        if (!is_readable($classfile)) {
            fwrite (STDERR,"Payment API file '$classfile' is not readable - aborting\n");
            exit (101);
        }
        fwrite (STDERR,"Processing payment API class file: $classfile\n");
        require $classfile;
        $class      = constant ($name.'_CLASS');
        if (!class_exists($class)) {
            fwrite (STDERR,"Payment API class '$class' does not exist - aborting\n");
            exit (102);
        }
        $api        = new $class ($zo);
        echo "    Instantiated $class\n";
        if (method_exists($api,'mandate')) {
            foreach (glob(BLOTTO_CSV_DIR_S.'/*') as $ccc) {
                $api->mandate ($ccc);
                $apis++;
            }
            // Only one mandate-creation API will be used
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

