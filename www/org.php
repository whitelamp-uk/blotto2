<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
if (defined('BLOTTO_SNAILMAIL') && BLOTTO_SNAILMAIL) {
    require BLOTTO_SNAILMAIL_API_STANNP;
}
if (defined('VOODOOSMS') && VOODOOSMS) {
    // Make class \SMS available for www_auth_reset_prep() and www_auth()
    require_once VOODOOSMS;
}


// Maintenance per software instance
$maintenance   = file_exists (dirname(BLOTTO_WWW_FUNCTIONS).'/../blotto.maintenance');
// Inhibit per org
$inhibit       = file_exists (BLOTTO_WWW_CONFIG.'.inhibit');
$options       = [
    'wait',
    'summary',
    'reconcile',
    'report',
    'list',
    'download',
    'supporter',
    'gratis',
    'gratiscollect',
    'bacs',
    'search',
    'update',
    'anlreset',
    'support',
    'guide',
    'privacy',
    'terms',
    'drawreports',
    'drawreport',
    'invoices',
    'invoice',
    'statements',
    'statement',
    'about'
];
$list          = [
    'ANLs',
    'Cancellations',
    'Changes',
    'Draws',
    'Insurance',
    'MoniesWeekly',
    'Supporters',
    'SupportersView',
    'Updates',
    'UpdatesLatest',
    'Wins'
];
$session       = null;
$opt           = false;
$table         = false;
$results       = [];
$msg           = [];
$err           = '';


// Log out if a request
if (array_key_exists('logout',$_GET)) {
    www_logout ();
}


// Authenticate if a request
if (array_key_exists('auth',$_POST)) {
    www_auth (BLOTTO_DB,$timestamp,$err,$msg);
}

// Parse options and table
foreach ($options as $o) {
    if (array_key_exists($o,$_GET)) {
        $opt   = $o;
        if ($opt!='list') {
            break;
        }
        if (in_array($_GET['list'],$list)) {
            $table   = $_GET['list'];
        }
        else {
            $opt = false;
        }
        break;
    }
}

if ($maintenance) {
    $path = './?maintenance';
}
elseif ($inhibit) {
    $path = './?wait';
}
elseif ($session=www_session($timestamp)) {
    $path = './?load';
    // Report if that option
    if ($opt=='report') {
        $err = report ();
        $err = 'Sorry - report failed: '.$err;
    }
    // Download if that option
    if ($opt=='download') {
        $err = download_csv ();
        $err = 'Sorry - download failed: '.$err;
    }
    // Gratis tickets if that option
    if ($opt=='gratiscollect') {
        $err = gratis_collect ();
        $err = 'Sorry - download failed: '.$err;
    }
    // Search if that option
    if ($opt=='search') {
        echo search ();
        exit;
    }
    // Update if that option
    if ($opt=='update') {
        $str = update ();
        error_log (__FILE__.' '.__LINE__.' org.update '.print_r($str, true));
        echo $str;
        exit;
    }
    if ($opt=='anlreset') {
        $str = anl_reset ();
        error_log (__FILE__.' '.__LINE__.' org.anlreset '.print_r($str, true));
        echo $str;
        exit;
    }
    // Invoice if that option
    if ($opt=='invoice') {
        invoice_serve ($_GET['invoice']);
        exit;
    }
    // Statement if that option
    if ($opt=='statement') {
        statement_serve ($_GET['statement']);
        exit;
    }
    // Draw report if that option
    if ($opt=='drawreport') {
        draw_report_serve ($_GET['drawreport']);
        exit;
    }
    // Test processes available in the user session
    if (array_key_exists('test_email_anl',$_POST)) {
        if ($anl=www_anl_random($msg)) {
            $api = email_api ();
            $api->keySet (org()['signup_cm_key']);
            $emref = $api->send (
                $_POST['template_ref'],
                $_POST['email'],
                $anl
            );
            if ($emref) {
                $msg[] = "ANL for {$anl['ClientRef']} sent to {$_POST['email']} ref: $emref";
            }
            else {
                $msg[] = $api->errorLast;
            }
        }
    }
}
else {
    if ($opt && array_key_exists($opt,['download','report','invoice','statement','drawreport','gratiscollect'])) {
        header ('Content-Type: text/plain');
        echo "Your login has expired\n";
        exit;
    }
    elseif ($opt && array_key_exists($opt,['search','update'])) {
        header ('Content-Type: text/plain');
        echo "{ \"error\" : 127, \"errorMessage\" : \"Your login has expired\" }";;
        exit;
    }
    else {
        $path = './?login';
    }
}


?><!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <title>
        <?php 
        if (strpos ($_SERVER['SERVER_NAME'],'dev.')===0) {
            echo "dev:";
        }
        echo strtoupper(BLOTTO_ORG_USER).' @ '.htmlspecialchars (BLOTTO_BRAND); ?> <?php version(__DIR__); 
        ?>
    </title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script src="./media/global.js"></script>
    <script defer src="./media/draw_calendar.js"></script>
    <script>
function init ( ) {
    if (!('setFrame' in window.self)) {
        setTimeout ('init()',500);
        return;
    }
<?php if($maintenance || $inhibit || !$session || !count($_GET) || array_key_exists('P',$_GET)): ?>
<?php     if($session && !$maintenance && !$inhibit): ?>

    setFrame ('<?php echo $path; ?>');
<?php     endif; ?>

    topCheck ();
<?php else: ?>

    frameCheck ();
<?php endif; ?>

    handlers ();
}

    </script>

    <link rel="stylesheet" href="./media/style.css" />
    <link rel="stylesheet" href="./media/override.css" />

  </head>

  <body onload="init()">
    <script>0</script>

<?php

if ($maintenance) {
    require __DIR__.'/views/maintenance.php';
}
elseif ($inhibit) {
    require __DIR__.'/views/wait.php';
}
elseif (!$session) {
    require __DIR__.'/views/login.php';
}
elseif ($opt=='summary') {
    require __DIR__.'/views/summary.php';
}
elseif ($opt=='list') {
    require __DIR__.'/views/list.php';
}
elseif ($opt=='reconcile') {
    require __DIR__.'/views/reconcile.php';
}
elseif ($opt=='supporter') {
    require __DIR__.'/views/supporter.php';
}
elseif ($opt=='gratis') {
    require __DIR__.'/views/gratis.php';
    require __DIR__.'/views/draw_calendar.php';
}
elseif ($opt=='bacs') {
    require __DIR__.'/views/bacs.php';
}
elseif ($opt=='support') {
    require __DIR__.'/views/support.php';
}
elseif ($opt=='guide') {
    require __DIR__.'/views/guide.php';
}
elseif ($opt=='privacy') {
    require __DIR__.'/views/privacy.php';
}
elseif ($opt=='terms') {
    require __DIR__.'/views/terms.php';
}
elseif ($opt=='invoices') {
    require __DIR__.'/views/invoices.php';
}
elseif ($opt=='statements') {
    require __DIR__.'/views/statements.php';
}
elseif ($opt=='drawreports') {
    require __DIR__.'/views/draw_reports.php';
}
elseif ($opt=='about') {
    require __DIR__.'/views/about.php';
    require __DIR__.'/views/draw_calendar.php';
}
elseif (array_key_exists('load',$_GET) || array_key_exists('login',$_GET)) {
    require __DIR__.'/views/load.php';
}
elseif (count($_GET) && !array_key_exists('P',$_GET)) {
    require __DIR__.'/views/error.php';
    if (strpos ($_SERVER['SERVER_NAME'],'dev.')===0) {
        echo "<pre>";
        echo "POST: "; print_r($_POST);
        echo "GET: "; print_r($_GET);
        echo "backtrace: "; debug_print_backtrace();
        echo "defined vars: "; print_r(get_defined_vars());
        echo "</pre>";
    }
}
else {
    require __DIR__.'/views/top.php';
}

require __DIR__.'/views/messages.php';

?>

  </body>

</html>


