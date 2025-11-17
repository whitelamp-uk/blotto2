<?php

require './config.www.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;

header ('Content-Type: application/json');

if (!array_key_exists('cumlog',$_GET) || !preg_match('<\.log$>',$_GET['cumlog']) || !is_readable($_GET['cumlog'])) {
    echo '{ "cumlog" : "should be valid log file path" }';
    exit;
}


$remote_addr = explode (',',BLOTTO_MONITOR_IPS);
if (!in_array($_SERVER['REMOTE_ADDR'],$remote_addr)) {
    echo '{ "remote_addr" : "'.$_SERVER['REMOTE_ADDR'].'" }';
    exit;
}

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

$org = org ();
$rdb = BLOTTO_RESULTS_DB;
$price = BLOTTO_TICKET_PRICE;
$report = [
    'Alerts' => [],
    'Warnings' => [],
    'Activity' => [],
    'Data' => [],
    'Build Summary' => [],
];
$today = gmdate ('Y-m-d');
$yesterday = new \DateTime ($today);
$yesterday->sub (new \DateInterval ('P1D'));
$yesterday = $yesterday->format ('Y-m-d');






/* summary */

// build details
$report['Build Summary']['Cumulative log'] = $_GET['cumlog'];
$report['Build Summary']['Last build log'] = glob (BLOTTO_LOG_DIR.'/blotto.run.*.log');
$report['Build Summary']['Last build log'] = array_pop ($report['Build Summary']['Last build log']);
$report['Build Summary']['Last build SQL logs'] = substr($report['Build Summary']['Last build log'],0,-4) . '.sql/';
preg_match_all ('<([0-9]+)>',$report['Build Summary']['Last build log'],$ms);
$report['Build Summary']['Started at'] = $ms[1][0].'-'.$ms[1][1].'-'.$ms[1][2].' '.$ms[1][3].':'.$ms[1][4].':'.$ms[1][5];
$report['Build Summary']['Still running'] = true;
$report['Build Summary']['Run time'] = null;
$report['Build Summary']['Ended at'] = null;
exec ('grep '.escapeshellarg('Script run time').' '.escapeshellarg($report['Build Summary']['Cumulative log']).' | tail -1',$rtn);
if (array_key_exists(0,$rtn)) {
    $report['Build Summary']['Still running'] = null;
    $dt = new \DateTime ($report['Build Summary']['Started at']);
    unset ($ms);
    preg_match ('<([0-9]+)\s+seconds\s*$>',$rtn[0],$ms);
    $m = intval ($ms[1]/60);
    $h = intval ($m/60);
    $m = str_pad ($m%60,2,'0',STR_PAD_LEFT);
    $s = str_pad ($ms[1]%60,2,'0',STR_PAD_LEFT);
    $report['Build Summary']['Run time'] = "$h:$m:$s";
    $dt->add (new DateInterval('PT'.$ms[1].'S'));
    $report['Build Summary']['Ended at'] = $dt->format ('Y-m-d H:i:s');
}
$report['Build Summary']['Exit status'] = null;
$report['Build Summary']['Build abort step'] = null;
// TODO check if still running
if (!$report['Build Summary']['Still running']) {
    $report['Build Summary']['Exit status'] = 0;
    unset ($rtn);
    exec ('grep Aborting '.escapeshellarg($report['Build Summary']['Last build log']),$rtn);
    if (array_key_exists(0,$rtn)) {
        unset ($ms);
        preg_match ('<status\s+=\s+([0-9]+)\s*$>',$rtn[0],$ms);
        if (array_key_exists(1,$ms)) {
            $report['Build Summary']['Exit status'] = $ms[1];
        }
        unset ($ms);
        preg_match ('<step\s+([0-9]+[A-z]*)\s>',$rtn[0],$ms);
        if (array_key_exists(1,$ms)) {
            $report['Build Summary']['Build abort step'] = $ms[1];
        }
    }
}






/* activity */

// supporters
$s = $zo->query ("SELECT COUNT(*) AS `s` FROM `blotto_supporter` WHERE DATE(`inserted`)='$today'");
$s = $s->fetch_assoc () ['s'];
$report['Activity']['Supporters inserted today'] = $s;

// mandates
$report['Activity']['Mandates created today'] = 0;
try {
    $m = $zo->query ("SELECT COUNT(*) AS `m` FROM `paysuite_mandate` WHERE `CustomerGuid` IS NOT NULL AND DATE(`MandateCreated`)='$today'");
}
catch (\mysqli_sql_exception $e) {
    // not an error condition - rsm-api orgs will not have paysuite-api tables
    try {
        $m = $zo->query ("SELECT COUNT(*) AS `m` FROM `rsm_mandate` WHERE `Created`='$today'");
    }
    catch (\mysqli_sql_exception $e) {
        // not an error condition - for example bwc and whc have tables for neither paysuite-api nor rsm-api
    }
    if (gettype($m)=='object') {
        $m = $m->fetch_assoc () ['m'];
        $report['Activity']['Mandates created today'] = $m;
    }
}

// collections (pending)
$report['Activity']['Collections pending today'] = 0;
try {
    $c = $zo->query ("SELECT COUNT(*) AS `c` FROM `paysuite_collection` WHERE `DateDue`='$today'");
}
catch (\mysqli_sql_exception $e) {
    // not an error condition - rsm-api orgs will not have paysuite-api tables
    try {
        $c = $zo->query ("SELECT COUNT(*) AS `c` FROM `rsm_collection` WHERE `PaidAmount`>0 AND `DateDue`='$today'");
    }
    catch (\mysqli_sql_exception $e) {
        // not an error condition - for example bwc and whc have tables for neither paysuite-api nor rsm-api
    }
    if (gettype($c)=='object') {
        $c = $c->fetch_assoc () ['c'];
        $report['Activity']['Collections pending today'] = $c;
    }
}

// collections (confirmed)
$earlier = new \DateTime ($today);
$earlier->sub (new \DateInterval (BLOTTO_PAY_DELAY_REVERSE));
$earlier = $earlier->format ('Y-m-d');
$c = $zo->query ("SELECT COUNT(*) AS `c` FROM `blotto_build_collection` WHERE `DateDue`='$earlier'");
$c = $c->fetch_assoc () ['c'];
$report['Activity']['Collections confirmed today'] = $c;



/* data */

// draws
$report['Data']['First draw close'] = BLOTTO_DRAW_CLOSE_1;
$report['Data']['Latest scheduled draw'] = null;
$report['Data']['Latest completed draw'] = null;
$report['Data']['Latest draw results'] = null;
$report['Data']['Latest draw tickets'] = null;
$report['Data']['Previous draw tickets'] = null;
$report['Data']['Next draw to complete'] = null;
//$report['Data']['Next draw predicted tickets'] = null; // TODO
if (!array_key_exists('No first draw',$report['Warnings']) && $today>$report['Data']['First draw close']) {
    $d = draw_previous ($today);
    $d = draw ($d);
    $report['Data']['Latest scheduled draw'] = $d;
    $d = $zo->query ("SELECT MAX(`draw_closed`) AS `d` FROM `$rdb`.`blotto_result`");
    $d = $d->fetch_assoc () ['d'];
    $d = draw ($d);
    $report['Data']['Latest completed draw'] = $d;
    $dlatest = $d->closed;
    if ($report['Data']['Latest completed draw']) {
        $rs = $zo->query ("SELECT COUNT(*) AS `rs` FROM `$rdb`.`blotto_result` WHERE `draw_closed`='{$d->closed}'");
        $rs = $rs->fetch_assoc () ['rs'];
        $report['Data']['Latest draw results'] = $rs;
        $ts = $zo->query ("SELECT COUNT(*) AS `ts` FROM `blotto_entry` WHERE `draw_closed`='{$d->closed}'");
        $ts = $ts->fetch_assoc () ['ts'];
        $report['Data']['Latest draw tickets'] = $ts;
        $d = new \DateTime ($d->closed);
        $d->add (new \DateInterval ('P1D'));
        $d = draw_upcoming ($d->format('Y-m-d'));
        $d = draw ($d);
        $report['Data']['Next draw to complete'] = $d;
/*
        $ts = $zo->query (
          "
            SELECT
              SUM(`supporter`.`tickets`) AS `ts`
            FROM `UpdatesLatest` AS `supporter`
            JOIN (
              SELECT
                `p`.`supporter_id`
               ,`c`.`PaidAmount`
              FROM
              GROUP BY `supporter_id`
            ) AS `funds`
              ON `funds`.`supporter_id`=`supporter`.`id`
            WHERE (`supporter`.`balance`+`funds`.`PaidAmount`) >= (`tickets`*$price/100)
            ;
          "
        );
            $report['Data']['Next draw predicted tickets'] = ???;
*/
        $d = $zo->query ("SELECT MAX(`draw_closed`) AS `d` FROM `$rdb`.`blotto_entry` WHERE `draw_closed`<'$dlatest'");
        $d = $d->fetch_assoc () ['d'];
        if ($d) {
            $ts = $zo->query ("SELECT COUNT(*) AS `ts` FROM `$rdb`.`blotto_entry` WHERE `draw_closed`='$d'");
            $ts = $ts->fetch_assoc () ['ts'];
            $report['Data']['Previous draw tickets'] = $ts;
            $dprevious = $d;
        }
    }
    else {
        // no draw results
        $d = draw (BLOTTO_DRAW_CLOSE_1);
        $report['Data']['Next draw to complete'] = $d;
    }
}
$ds = $zo->query ("
  SELECT
    DAYNAME(`draw_closed`) AS `dow_closed`
   ,MIN(`draw_closed`) AS `since`
   ,COUNT(DISTINCT `draw_closed`) AS `draws`
  FROM `blotto_result`
  GROUP BY `dow_closed`
  ORDER BY `since`
");
$report['Data']['Draw DoW summary'] = $ds->fetch_all (MYSQLI_ASSOC);



/* alerts */

// draw overdue
if (($c=$report['Data']['Latest completed draw']->closed)<($s=$report['Data']['Latest scheduled draw']->closed)) {
    $report['Alerts']['Draw overdue'] = "Draw results are overdue\nLatest scheduled: $s\nLatest results found: $c";
}
else {
    $report['Alerts']['Draw overdue'] = null;
}

// build error
if ($report['Build Summary']['Exit status']>0) {
    $report['Alerts']['Build error'] = "Abort step={$report['Build Summary']['Build abort step']} status={$report['Build Summary']['Exit status']}";
}
else {
    $report['Alerts']['Build error'] = null;
}

/*

// rejected imports
 - Last build rejected new imports for one or more CCCs
    * suck from logs or get supporters.php to store something?

// rejected mandates
 - DD API rejected one or more mandates
    * suck from logs or get PayApi.php classes to store something?

*/




/* warnings */

// no first draw close date
if (!preg_match('<^[0-9]{4}-[0-9]{2}-[0-9]{2}$>',BLOTTO_DRAW_CLOSE_1)) {
    $report['Warnings']['No first draw close date'] = "First draw close date BLOTTO_DRAW_CLOSE_1 is not configured\nANLs cannot be generated";
}

// pay freeze in place
if (defined('BLOTTO_DEV_PAY_FREEZE') && BLOTTO_DEV_PAY_FREEZE) {
    $report['Warnings']['Pay freeze in place'] = "Pay freeze is in place which prevented:
     * DDI instructions
     * DDI collections
     * draw entries
     * draws
     * cancellations
     * CRM milestones";
}

// build is overdue
$time = gmdate ('H:i:s');
if ($time<BLOTTO_MONITOR_CUT_OFF) {
    $day_due = $yesterday;
}
else {
    $day_due = $today;
}
$day_built = new \DateTime ($report['Build Summary']['Started at']);
$day_built = $day_built->format ('Y-m-d');
if (($report['Build Summary']['Still running'] && $time>=BLOTTO_MONITOR_CUT_OFF) || ($day_built<$day_due)) {
    $report['Warnings']['Build overdue'] = "Build overdue since $day_due ".BLOTTO_MONITOR_CUT_OFF." GMT";
}

// ANL emails not activated
if (!defined('BLOTTO_ANL_EMAIL') ||!BLOTTO_ANL_EMAIL) {
    $report['Warnings']['ANL emails not activated'] = "ANL emails not activated\nBLOTTO_ANL_EMAIL is configured off";
}


// small draw size (historic)
if ($report['Data']['Previous draw tickets']) {
    if (($report['Data']['Latest draw tickets']/$report['Data']['Previous draw tickets'])<0.95) {
        $from = $report['Data']['Previous draw tickets'].' @ '.$dprevious;
        $to   = $report['Data']['Latest draw tickets'].' @ '.$dlatest;
        $report['Warnings']['Small draw size'] = "Draw entries down from $from to $to";
    }
}

/*
// small draw size (predicted)
if (($report['Data']['Next draw tickets']/$report['Data']['Latest draw tickets'])<0.95) {
    $from = $report['Data']['Latest draw tickets'].' @ '.$dlatest;
    $to   = $report['Data']['Next draw tickets'].' @ '.$dnext;
    $report['Warnings']['Small predicted draw size'] = "Draw entries predicted down from $from to $to";
}
*/

// no prizes for next draw
if ($report['Data']['Latest completed draw'] && count($report['Data']['Latest completed draw']->prizes)==0) {
    $report['Warnings']['No prizes for next draw'] = "No prizes for next draw\nCloses on {$report['Data']['Latest completed draw']->closed}";
}

// label the right org code for remote monitors
$report['o'] = BLOTTO_ORG_USER;

foreach ($report as $section=>$r) {
    if (is_array($r) && !count($r)) {
        // json_encode assumes an empty array is numeric so force "associative"
        $report[$section] = new \stdClass ();
    }
}

echo json_encode ($report,JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

