<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$api = false;
$bads = [];
$msg = '';

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

try {
    $constants      = get_defined_constants (true);
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
        $api    = new $class ($zo);
        if (method_exists($api,'bad_mandates')) {
            $bads = $api->bad_mandates ();
        }
        break;
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    if (!$api || !$api->errorCode) {
        // Unexpected error
        exit (104);
    }
    exit ($api->errorCode);
}

if ($count=count($bads)) {
    fwrite (STDERR,"Bad mandates identified: ".implode(',',$bads));
    $msg = "The following mandates are bad and require cancellation asap.\nThey are still live but the supporters are mandate_blocked.\n\n";
    foreach ($bads as $b) {
        $msg .= "{$b['ClientRef']}\t{$b['Name']}\n";
    }
    notify (BLOTTO_EMAIL_WARN_TO,"$count bad mandates",$msg);
}

