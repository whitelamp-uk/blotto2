<?php

// Fetch password from blotto

require './bridge.php';

if (cookie2end('blotto_end')) {
    define ( 'BLOTTO_ADMINER_PW', '' );
    define ( 'BLOTTO_ADMINER_UN', '' );
    define ( 'BLOTTO_ADMINER_DB', '' );
}
else {
    define ( 'BLOTTO_ADMINER_PW', cookie2pwd ('blotto_key') );
    define ( 'BLOTTO_ADMINER_UN', cookie2value ('blotto_usr') );
    define ( 'BLOTTO_ADMINER_DB', cookie2value ('blotto_dbn') );
}


// Adminer plugin and customisation

function adminer_object ( ) {
    include_once "/var/www/adminer/plugins/plugin.php";
    include_once "/var/www/adminer/plugins/adminer-frames.php";
    include_once "/var/www/adminer/plugins/adminer-unloading.php";
    include_once "/var/www/adminer/plugins/blotto-maintenance.php";
    include_once "/var/www/adminer/plugins/dump-qcsv.php";
    include_once "/var/www/adminer/plugins/select-local-functions.php";
    $plugins = [
        new AdminerFrames,
        new AdminerUnloading,
        new BlottoMaintenance,
        new AdminerDumpQcsv,
        new SelectLocalFunctions,
    ];
    class AdminerCustomization extends AdminerPlugin {
        function loginForm() {
            require __DIR__.'/adminer.login.php';
        }
    }
    return new AdminerCustomization ($plugins);
}


// Go mining!

require "/var/www/adminer/adminer.php";


