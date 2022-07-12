<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

echo "    Fetching mandate and collection data\n";

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

try {
    $constants      = get_defined_constants (true);
    $apis           = 0;
    $api            = null;
    foreach ($constants['user'] as $name => $classfile) {
        if (!preg_match('<^BLOTTO_PAY_API_[A-Z]+$>',$name)) {
            continue;
        }
        if (!is_readable($classfile)) {
            fwrite (STDERR,"Payment API file '$classfile' is not readable - aborting\n");
            exit (101);
        }
        fwrite (STDERR,__FILE__.": processing payment API class file: $classfile\n");
        require $classfile;
        $class      = constant ($name.'_CLASS');
        if (!class_exists($class)) {
            fwrite (STDERR,"Payment API class '$class' does not exist - aborting\n");
            exit (102);
        }
        $api        = new $class ($zo);
        echo "    Instantiated $class\n";
        $api->import (BLOTTO_DAY_FIRST);
        $apis++;
    }
    if (!$apis) {
        fwrite (STDERR,"No payment APIs - aborting\n");
        exit (103);
    }
    echo "    Imported payments using $apis APIs\n";
}
catch (\Exception $e) {
    fwrite (STDERR,"Failed to fetch payments: ".$e->getMessage()."\n");
    if (!$api) {
        fwrite (STDERR,"(No API was instantiated)\n");
        exit (104);
    }
    if (!property_exists($api,'errorCode')) {
        fwrite (STDERR,"(Unexpected error)\n");
        // Unexpected error
        exit (105);
    }
    exit ($api->errorCode);
}

