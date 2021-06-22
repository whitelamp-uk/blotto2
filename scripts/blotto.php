<?php

// Execute either instantiated SQL or scripts


function get_args (&$argv,&$switches) {
    $switches   = array ();
    $new        = array ();
    foreach ($argv as $v) {
        if (strpos($v,'--')===0) {
            $v = explode ('=',substr($v,2));
            if (!$v[0]) {
                continue;
            }
            $switches[$v[0]] = $v[1];
            continue;
        }
        if (strpos($v,'-')===0) {
            $len = strlen ($v);
            for ($i=1;$i<$len;$i++) {
                $switches[$v[$i]] = true;
            }
            continue;
        }
        array_push ($new,$v);
    }
    $argv = $new;
}
get_args ($argv,$Sw);


$options = array (
    "sql"
   ,"exec"
);

// Allowed SQL and PHP files
$file = array (
    "sql" => array (
        'BESPOKE'
       ,'db.create.sql'
       ,'db.create.std.sql'
       ,'db.functions.sql'
       ,'db.functions.drop.sql'
       ,'db.permissions.sql'
       ,'db.permissions.reports.sql'
       ,'db.permissions.reports.standard.sql'
       ,'db.routines.sql'
       ,'db.routines.admin.sql'
       ,'db.routines.drop.sql'
       ,'db.routines.org.sql'
       ,'db.routines.rbe.sql'
       ,'db.tables.drop.sql'
       ,'import.collection.sql'
       ,'import.mandate.sql'
       ,'import.supporter.sql'
       ,'payment.create.sql'
       ,'payment.update.sql'
       ,'results.export.sql'
    ),
    "exec" => array (
        'bogons.php'
       ,'cache.php'
       ,'changes.php'
       ,'clone.php'
       ,'demo.php'
       ,'draws.php'
       ,'engine.php'
       ,'entries.php'
       ,'legends.php'
       ,'manual.php'
       ,'payment_fetch.php'
       ,'players.php'
       ,'players_check.php'
       ,'players_update.php'
       ,'reports.php'
       ,'supporters.php'
       ,'ticket_discrepancy.php'
       ,'tickets.php'
// INTERIM:
       ,'superdraw_export.php'
    )
);

// SQL instantiation
$vars = array (
    'BLOTTO_ADMIN_USER'
   ,'BLOTTO_ANONYMISER_DB'
   ,'BLOTTO_CANCEL_RULE'
   ,'BLOTTO_CC_NOTIFY'
   ,'BLOTTO_CONFIG_DB'
   ,'BLOTTO_CREF_SPLITTER'
   ,'BLOTTO_CSV_DIR_C'
   ,'BLOTTO_CSV_DIR_M'
   ,'BLOTTO_CSV_DIR_S'
   ,'BLOTTO_CSV_C'
   ,'BLOTTO_CSV_M'
   ,'BLOTTO_CSV_S'
   ,'BLOTTO_DB'
   ,'BLOTTO_DRAWS_AFTER'
   ,'BLOTTO_INSURE_DAYS'
   ,'BLOTTO_MAKE_DB'
   ,'BLOTTO_ORG_USER'
   ,'BLOTTO_ORG_ID'
   ,'BLOTTO_OUTFILE'
   ,'BLOTTO_RESULTS_DB'
   ,'BLOTTO_TICKET_DB'
   ,'BLOTTO_TICKET_PRICE'
 );

if (!array_key_exists(1,$argv)) {
    fwrite (STDERR,"CONFIG FILE NOT GIVEN\n");
    exit (101);
}

if (!array_key_exists(2,$argv) || !in_array($argv[2],$options)) {
    fwrite (STDERR,"INVALID OPTION '".$argv[2]."' - menu = ".implode (', ',$options) ."\n");
    exit (102);
}

if (!array_key_exists(3,$argv) || !in_array($argv[3],$file[$argv[2]])) {
    fwrite (STDERR,"INVALID FILE '".$argv[3]."' - menu = ".implode (', ',$file[$argv[2]]) ."\n");
    exit (103);
}




// EXECUTE PHP

if ($argv[2]=='exec') {
    require __DIR__.'/'.$argv[3];
    exit;
}




// OUTPUT INSTANTIATED SQL


// Get definitions from config
require $argv[1];


// Define import file
if ($argv[3]=='import.supporter.sql') {
    if (!array_key_exists(4,$argv)) {
        fwrite (STDERR,"Supporter import directory argument not given - skipping ".$argv[3]."\n");
        exit (0);
    }
    exec (str_replace('{{DIR}}',$argv[4],BLOTTO_EXEC_LAST_FILE),$m);
    if (!count($m)) {
        fwrite (STDERR,"No file found in '".$argv[4]."' - skipping ".$argv[3]."\n");
        exit (0);
    }
    define ('BLOTTO_CSV_S',$argv[4].'/'.$m[0]);
}
else if ($argv[3]=='import.mandate.sql' || $argv[3]=='import.collection.sql') {
    exec (str_replace('{{DIR}}',BLOTTO_CSV_DIR_M,BLOTTO_EXEC_LAST_FILE),$m);
    if (!count($m)) {
        fwrite (STDERR,"No file found in '".BLOTTO_CSV_DIR_M."' - skipping ".$argv[3]."\n");
        exit (0);
    }
    if ($argv[3]=='import.mandate.sql') {
        define ('BLOTTO_CSV_M',BLOTTO_CSV_DIR_M.'/'.$m[0]);
    }
    else {
        exec (str_replace('{{DIR}}',BLOTTO_CSV_DIR_C,BLOTTO_EXEC_LAST_FILE),$c);
        if (!count($c)) {
            fwrite (STDERR,"No file found in '".BLOTTO_CSV_DIR_M."' - skipping ".$argv[3]."\n");
            exit (0);
        }
        define ('BLOTTO_CSV_C',BLOTTO_CSV_DIR_C.'/'.$c[0]);
    }
}


// Instantiate SQL
if ($argv[3]=='BESPOKE') {
    $sql = file_get_contents ($argv[4]);
}
else {
    $sql = file_get_contents (__DIR__.'/../procedure_sql/'.$argv[3]);
}
foreach ($vars as $var) {
    if (!defined($var)) {
        continue;
    }
    $sql = str_replace ('{{'.$var.'}}',constant($var),$sql);
}


// Output SQL
echo $sql;


