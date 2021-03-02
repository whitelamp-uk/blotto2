<?php

function bank_decrypt ($key,$data,&$sortcode,&$accountnr) {
    $method = 'AES-256-CBC';
    $data = base64_decode($data);
    $data = json_decode($data);
    // $data also contains $data->mac which is constructed thus:
    // $mac = $this->hash($iv = base64_encode($iv), $value);
    $out = openssl_decrypt ( $data->value, $method, $key, 0, base64_decode($data->iv)); // , OPENSSL_RAW_DATA 
    $out = unserialize($out);
    if ($out) {
        $sortcode   = $out['sort_code'];
        $accountnr  = $out['account_number'];
        return true;
    }
    else {
        $sortcode   = 'failed';
        $accountnr  = 'decrypt!';
        return false;
    }
}

function built_at ($sql_date_format,$db=BLOTTO_DB) {
    $zo = connect ();
    if (!$zo) {
        return '';
    }
    $f  = esc ($sql_date_format,$db);
    $built_at = $zo->query (
      "
        SELECT
          DATE_FORMAT(MAX(`create_time`),'$f') AS `built_at`
        FROM `information_schema`.`TABLES`
        WHERE `TABLE_SCHEMA`='$db'
        GROUP BY `TABLE_SCHEMA`
        ;
      "
    );
    while ($b=$built_at->fetch_assoc()) {
        return $b['built_at'];
    }
}

function calculate ($start=null,$end=null) {
    // Use temporary connections because mysqli does not like repeated CALL queries
    if (!$start) {
        $month      = month_last ();
        $start      = $month[0];
        $end        = $month[1];
    }
    if (!$end) {
        $dt         = new DateTime ();
        $end        = $dt->format ('Y-m-d');
    }
    $results        = array ();
    $zo         = connect (BLOTTO_DB,BLOTTO_UN,BLOTTO_PW,true);
    if (!$zo) {
        return $results;
    }
    try {
        $c          = $zo->query ("CALL `calculate`('$start','$end')");
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('calculate(): '.$e->getMessage());
        return $results;
    }
    while ($r=$c->fetch_assoc()) {
        $item   = array (
            'units' => $r['units'],
            'amount' => $r['amount'],
            'notes' => $r['notes']
        );
        $results[$r['item']] = $item;
    }
    return $results;
}

function cfg ( ) {
    global $argv;
    if (!array_key_exists(1,$argv)) {
        fwrite(STDERR, "Usage: ".$argv[0]." path_to_config_file\n");
        exit (100);
    }
    if (!is_readable($argv[1])) {
        fwrite(STDERR, "Config file is not readable\n");
        exit (100);
    }
    return true;
}

function chances_weekly ($frequency,$amount) {
    if ($frequency=='Monthly') {
        $ratio = 4.34;
    }
    elseif ($frequency=="Quarterly") {
        $ratio = 13;
    }
    elseif ($frequency=="Six Monthly") {
        $ratio = 26;
    }
    elseif ($frequency=="Annually") {
        $ratio = 52;
    }
    else {
        throw new \Exception ('chances_weekly(): frequency "$frequency" not recognised');
        return false;
    }
    return intval((100*$amount)/round($ratio*BLOTTO_TICKET_PRICE));

}

function chances2Amount ($freq,$chances) {
    if ($freq=='Monthly') {
        return number_format (4.34*$chances,2,'.','');
    }
    if ($freq=='Quarterly') {
        return number_format (13.02*$chances,2,'.','');
    }
    if ($freq=='Six Monthly') {
        return number_format (26*$chances,2,'.','');
    }
    if ($freq=='Annually') {
        return number_format (52*$chances,2,'.','');
    }
    return false;
}

function chart ($number,$type) {
    if (!in_array($type,['graph','table','csv'])) {
        return '';
    }
    $error          = [
        'graph'         => 'null; // See error log [$number]',
        'table'         => '<dialog open>See PHP error log [$number]</dialog>',
        'csv'           => 'See error log [$number]'
    ];
    $error          = $error[$type];
    $zo             = connect ();
    if (!$zo) {
        return $error;
    }
    // Chart Definition Object
    $cdo            = new stdClass ();
    $labels         = [];
    $data           = [[]];
    $file           = __DIR__.'/chart-'.str_pad($number,4,'0',STR_PAD_LEFT).'.php';
    if (!is_readable($file)) {
        error_log ("File '$file' is not readable");
        return str_replace('[n]',"[1]",$error);
    }
    try {
        // Using parameters - $p, print a Chart Definition Object - $cdo
        $p          = func_get_args ();
        array_shift ($p);
        array_shift ($p);
        require $file;
        if ($type=='graph') {
            $obj = null;
            if (property_exists($cdo,'datasets')) {
                $obj = json_encode ($cdo,JSON_PRETTY_PRINT);
            }
            if ($obj) {
                return $obj;
            }
            error_log ("Could not encode a sensible object");
            return str_replace('[n]',"[2]",$error);
        }
        // Other types are array-based
        $data = chart2Array ($cdo);
        $head = chart2Headings ($cdo);
        if ($type=='csv') {
            array_unshift ($data,$head);
            return csv ($data);
        }
        // Tables require a readable identifier
        // for both file name and ID attribute
        $id = 'report-'.$number;
        foreach ($p as $value) {
            $id .= '-'.$value;
        }
        $class = 'report-'.$number;
        return html (
            table ($id,'report',null,$head,$data,false),
            $id,
            false
        );
    }
    catch (\Exception $e) {
        error_log ($e->getMessage());
        return str_replace('[n]',"[4]",$error);
    }
}

function chart2Array ($chartObj) {
    $arr = [];
    foreach ($chartObj->labels as $i=>$l) {
        $row = [$l];
        foreach ($chartObj->datasets as $j=>$d) {
            foreach ($d->data as $k=>$v) {
                if ($k==$i) {
                    array_push ($row,$v);
                    continue;
                }
            }
        }
        array_push ($arr,$row);
    }
    return $arr;
}

function chart2Headings ($chartObj) {
    $arr = ['{{XHEAD}}'];
    foreach ($chartObj->datasets as $i=>$d) {
        if (!array_key_exists('label',$d) || !$d->label) {
            array_push ($arr,'Unspecified');
            continue;
        }
        array_push ($arr,$d->label);
    }
    return $arr;
}

function clientref_advance ($cref) {
    $cref = explode (BLOTTO_CREF_SPLITTER,$cref);
    if (count($cref)==1) {
        return $cref[0].BLOTTO_CREF_SPLITTER.'0001';
    }
    $number = array_pop ($cref);
    $number += 1;
    array_push ($cref,str_pad($number,4,'0',STR_PAD_LEFT));
    return implode (BLOTTO_CREF_SPLITTER,$cref);
}

function connect ($db=BLOTTO_DB,$un=BLOTTO_UN,$pw=BLOTTO_PW,$temp=false,$auth=false) {
    global $Co;
    mysqli_report (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if ($temp) {
        try {
            $temp = new mysqli ('localhost',$un,$pw,$db);
            return $temp;
        }
        catch (\mysqli_sql_exception $e) {
            if ($auth) {
                return false;
            }
            error_log ('connect(): '.$e->getMessage());
            fwrite (STDERR,'Connection error '.$e->getCode().' '.$e->getMessage()."\n");
            return false;
        }
    }
    if (!$Co) {
        $Co = array ();
    }
    if (!array_key_exists($db,$Co)) {
        $Co[$db] = array ();
    }
    if (!array_key_exists($un,$Co[$db])) {
        $Co[$db][$un] = null;
    }
    if ($Co[$db][$un]) {
        return $Co[$db][$un];
    }
    try {
        $Co[$db][$un] = new mysqli ('localhost',$un,$pw,$db);
        return $Co[$db][$un];
    }
    catch (\mysqli_sql_exception $e) {
        if (!$auth) {
            error_log ('connect(): '.$e->getMessage());
            fwrite (STDERR,'Connection error '.$e->getCode().' '.$e->getMessage()."\n");
        }
        $Co[$db][$un] = null;
        return false;
    }
}

function csv ($arrays) {
    $delim = BLOTTO_CSV_FORCE_DELIM;
    $error = false;
    ob_start ();
    try {
        $fp = fopen ('php://output','w');
        foreach ($arrays as $array) {
            foreach ($array as $k=>$v) {
                if (strpos($v,$delim)!==false) {
                    $error = "The data contains the force-delimiter string '$delim'";
                    break;
                }
                $array[$k] = $delim.$v;
            }
            fputcsv ($fp,$array);
        }
        $csv = ob_get_contents ();
    }
    catch (\Exception $e) {
        $error = $e->getMessage ();
    }
    ob_end_clean ();
    if ($error) {
        throw new \Exception ($error);
        return false;
    }
    return str_replace ($delim,'',$csv);
}

function csv_excel_leading_zero ($file,$complement=false) {
    $error                  = false;
    ob_start ();
    try {
        $fpin               = fopen ($file);
        $fpout              = fopen ('php://output','w');
        while ($array=fgetcsv($fpin)) {
            foreach ($array as $k=>$v) {
                if ($complement && preg_match("<^'[0-9]+$>",$v)) {
                    $array[$k] = substr ($v,1);
                }
                if (preg_match('^[0-9]+$')) {
                    $array[$k] = substr ($v,1);
                }
            }
            fputcsv ($fpout,$array);
        }
        $csv = ob_get_contents ();
    }
    catch (\Exception $e) {
        $error = $e->getMessage ();
    }
    ob_end_clean ();
    if ($error) {
        throw new \Exception ($error);
        return false;
    }
    return $csv;
}

function csv_write ($file,$array2d,$headers=false) {
    $arrays = [];
    foreach ($array2d as $array) {
        if ($headers) {
            $headers = [];
            foreach ($array as $h=>$v) {
                array_push ($headers,$h);
            }
            array_push ($arrays,$headers);
            $headers = false;
        }
        array_push ($arrays,$array);
    }
    $csv = csv ($arrays);
    $fp = fopen ($file,'w');
    fwrite ($fp,$csv);
    fclose ($fp);
}

function curl_post ($url,$post,$options=[]) {
/*
    * Send a POST requst using cURL
    * @param string $url to request
    * @param array $post values to send
    * @param array $options for cURL
    * @return string
*/
    if (!is_array($post) || !is_array($options)) {
        throw new \Exception ('Post and option arguments must be arrays');
        return false;
    }
    $defaults = array (
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_POSTFIELDS => http_build_query ($post)
    );

    $ch = curl_init ();
    curl_setopt_array ($ch,$options+$defaults);
    if (!$result=curl_exec($ch)) {
        trigger_error (curl_error($ch));
    }
    curl_close ($ch);
    return $result;
} 

function date_reformat ($d,$f) {
    $dt = new DateTime ($d);
    return $dt->format ($f);
}

function day_cancels_known ($format) {
    $zo = connect ();
    if (!$zo) {
        return '';
    }
    $i = BLOTTO_CANCEL_RULE;
    try {
        // There is no information about today yet (-1 day) and collections can be late (-2 day)
        $c = $zo->query ("SELECT DATE_SUB(DATE_SUB(CURDATE(),INTERVAL 3 DAY),INTERVAL $i) AS `d`");
        $c = $c->fetch_assoc()['d'];
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('day_cancels_known(): '.$e->getMessage());
        return '';
    }
    $dt = new DateTime ($c);
    return $dt->format ($format);
}

function day_one ($for_wins=false) {
    $zo = connect ();
    if (!$zo) {
        return false;
    }
    try {
        if (defined('BLOTTO_RBE_ORGS')) {
            // RBEs only have entries and winners
            $s = $zo->query ("SELECT MIN(`draw_closed`) AS `d1` FROM `blotto_entry`");
            $s = $s->fetch_assoc()['d1'];
        }
        else {
            $s = $zo->query ("SELECT MIN(`created`) AS `d1` FROM `blotto_supporter`");
            $s = $s->fetch_assoc()['d1'];
            $c = $zo->query ("SELECT MIN(`DateDue`) AS `d1` FROM `blotto_build_collection`");
            $c = $c->fetch_assoc()['d1'];
            if ($c<$s) {
                $s = $c;
            }
        }
        if ($for_wins && defined('BLOTTO_WIN_FIRST') && BLOTTO_WIN_FIRST>$s) {
            // Handles legacy scenario (eg. SHC) where tickets got changed
            // Before the change date, winnings are no longer derivable by deterministic calculation
            // So, in the case of winnings (or reconciliation), day one is BLOTTO_WIN_FIRST
            $s = BLOTTO_WIN_FIRST;
        }
    }
    catch (\mysqli_sql_exception $e) {
        $s = null;
    }
    return new DateTime ($s);
}

function day_tomorrow ($date=null) {
    $dt = new DateTime ($date);
    $dt->add (new DateInterval('P1D'));
    return $dt;
}

function day_yesterday ($date=null) {
    $dt = new DateTime ($date);
    $dt->sub (new DateInterval('P1D'));
    return $dt;
}

function dbs ( ) {
    if (!defined('BLOTTO_RBE_DBS')) {
        return [BLOTTO_ORG_ID=>['make'=>BLOTTO_MAKE_DB,'frontend'=>BLOTTO_DB]];
    }
    $org = explode (',',BLOTTO_RBE_ORGS);
    $dbm = explode (',',BLOTTO_RBE_MAKES);
    $dbf = explode (',',BLOTTO_RBE_DBS);
    $dbs = [];
    for ($i=0;array_key_exists($i,$org);$i++) {
        if (!array_key_exists($i,$dbm)) {
            break;
        }
        if (!array_key_exists($i,$dbf)) {
            break;
        }
        $dbs[$org[$i]] = ['make'=>$dbm[$i],'frontend'=>$dbf[$i]];
    }
    return $dbs;
}

function dom_elements_by_class ($elmt,$class_name,&$elmts) {
    if (!$elmts) {
        $elmts          = [];
    }
    if (!method_exists($elmt,'getAttribute')) {
        return;
    }
    $classes            = explode (' ',$elmt->getAttribute('class'));
    if (in_array($class_name,$classes)) {
        array_push ($elmts,$elmt);
    }
    foreach ($elmt->childNodes as $child) {
        dom_elements_by_class ($child,$class_name,$elmts);
    }
}

function download_csv ( ) {
    if (!array_key_exists('table',$_GET) || !$_GET['table']) {
        return "invalid request - no table name";
    }
    if (!array_key_exists('field',$_GET) || !$_GET['field']) {
        return "invalid request - no date field";
    }
    if (!array_key_exists('from',$_GET) || !$_GET['from']) {
        return "invalid request - no start date";
    }
    if (!array_key_exists('to',$_GET) || !$_GET['to']) {
        return "invalid request - no end date";
    }
    $zo = connect ();
    if (!$zo) {
        return "connection failure";
    }
    $of         = BLOTTO_OUTFILE.'.'.getmypid().'.'.time().'.csv';
    $lim        = BLOTTO_WWW_ROWS_MAX_PULL;
    $t          = esc ($_GET['table']);
    $f          = esc ($_GET['field']);
    $d1         = esc ($_GET['from']);
    $d2         = esc ($_GET['to']);
    $cond       = strtolower($t) == 'wins' && defined('BLOTTO_WIN_FIRST') && BLOTTO_WIN_FIRST;
    $gp         = array_key_exists('grp',$_GET) && $_GET['grp']>0 && in_array(strtolower($t),['cancellations','draws','supporters']);
    $elz        = array_key_exists('elz',$_GET) && $_GET['elz']>0
    if ($gp) {
        $file       = $_GET['table'].'_by_member_'.$_GET['from'].'_'.$_GET['to'].'.csv';
    }
    elseif (!in_array(strtolower($t),['anls','wins'])) {
        $file       = $_GET['table'].'_by_ticket_'.$_GET['from'].'_'.$_GET['to'].'.csv';
    }
    else {
        $file       = $_GET['table'].'_'.$_GET['from'].'_'.$_GET['to'].'.csv';
    }
    if (in_array(strtolower($_GET['table']),['draws','insurance'])) {
        $dt1    = new DateTime ($_GET['from']);
        $dt2    = new DateTime ($_GET['to']);
        if ($dt1->diff($dt2)->format('%r%a')>6) {
            return "that is too much data via the web; request help from your account administrator";
        }
    }
    try {
        $fs         = $zo->query ("DESCRIBE `$t`");
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('download_csv(): '.$e->getMessage());
        return "SQL failure";
    }
    $q ="
      SELECT
        `o`.*
      FROM (
        SELECT
{{HEADINGS}}
        UNION
        SELECT
          `i`.*
        FROM (
          SELECT
{{DATA}}
          FROM `$t`
          USE INDEX (PRIMARY)
          WHERE `$f`>='$d1'
            AND `$f`<='$d2'
{{CONDITION}}
{{GROUP}}
          LIMIT 0,$lim
        ) AS `i`
      ) AS `o`
      INTO OUTFILE '$of'
      FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
      LINES TERMINATED BY '\\n'
      ;
    ";
    $fields         = [];
    $headings       = [];
    $data           = [];
    while ($fn=$fs->fetch_assoc()) {
        if ($gp && $fn['Field']=='ticket_number') {
            array_push ($headings,"'ticket_numbers' AS `ticket_numbers`");
            array_push ($data,"IFNULL(GROUP_CONCAT(`ticket_number` SEPARATOR ', '),'')");
            continue;
        }
        array_push ($headings,"'{$fn['Field']}' AS `{$fn['Field']}`");
        array_push ($data,"IFNULL(`{$fn['Field']}`,'')");
    }
    if ($cond) {
        $cond       = "AND `draw_closed`>'".BLOTTO_WIN_FIRST."'\n";
    }
    else {
        $cond       = "";
    }
    if ($gp) {
        $gpby       = "GROUP BY `ClientRef`\n";
    }
    else {
        $gpby       = "";
    }
    $q              = str_replace ('{{HEADINGS}}',implode(",\n",$headings),$q);
    $q              = str_replace ('{{DATA}}',implode(",\n",$data),$q);
    $q              = str_replace ('{{CONDITION}}',$cond,$q);
    $q              = str_replace ('{{GROUP}}',$gpby,$q);
    try {
        $save       = $zo->query ($q);
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('download_csv(): '.$e->getMessage());
        return "SQL failure";
    }
    header ('Content-Type: text/csv');
    header ('Content-Disposition: attachment; filename="'.$file.'"'); 
    header ('Content-Description: File Transfer');
    header ('Expires: 0');
    header ('Cache-Control: must-revalidate');
    header ('Pragma: public');
    if (!$elz) {
        header ('Content-Length: '.filesize($of));
        readfile ($of);
        exit;
    }
    $output = csv_excel_leading_zero ($of);
    header ('Content-Length: '.strlen($output));
    echo $output;
    exit;
}

function draw ($draw_closed) {
    $draw                   = new \stdClass ();
    $draw->closed           = $draw_closed;
    $qs  = "
      SELECT
        DATE(drawOnOrAfter('$draw_closed')) AS `draw_date`
       ,drawOnOrAfter('$draw_closed') AS `draw_time`
      ;
    ";
    try {
        $zo                 = connect (BLOTTO_MAKE_DB);
        $d                  = $zo->query ($qs);
        $d                  = $d->fetch_assoc ();
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs.$e->getMessage()."\n");
        return false;
    }
    $draw->date             = $d['draw_date'];
    if (!$draw->date) {
        throw new \Exception ("Draw could not be created; is '{$draw->closed}' a valid date?\n");
        return false;
    }
    $draw->time             = $d['draw_time'];
    $draw->prizes           = prizes ($draw_closed);
    $draw->insure           = false;
    $draw->manual           = false;
    $draw->results          = [];
    $draw->groups           = [];
    foreach ($draw->prizes as $level=>$p) {
        if ($p['insure']) {
            $draw->insure   = $level;
        }
        if ($p['results_manual'] && !$p['results'] && !$p['function_name']) {
            $draw->manual   = $level;
        }
        // Groups
        $group              = substr ($p['level_method'],-1);
        if ($p['level_method']=='RAFF') {
            if ($p['results']) {
                 $draw->results['RAFF'] = true;
            }
            continue;
        }
        if ($p['results']) {
             $draw->results[$group] = true;
        }
        if (!array_key_exists($group,$draw->groups)) {
            $draw->groups[$group] = [];
        }
        array_push ($draw->groups[$group],$level);
    }
    return $draw;
}

function draw_first_zaffo_model ($first_collection_date) { // TODO: tidy this up
    /*
    The principle behind the Zaffo model is to ensure that once
    an account is active it will always have a ticket every week
    as long as no collections fail.
    Technically we are not certain that this works for more than
    about 10 years but it's not our model so...
    */

/*
Dom's description which Mark found ambiguous (and somewhat
unhelpful given that draw_closed is not a Saturday but a Friday):
    An account becomes active two weeks on Saturday,
    unless collection is Friday or Saturday, in which case three
    weeks.  

Given that blotto_player.first_draw_closed should be a Friday and
this function was returning Saturday, I did the following check on
a zaffo-repair-shop database (can be very slow):
    drop table if exists tmp
    ;
    create table tmp as
    select
      a.*
     ,c.collected
    -- just for readability:
     ,a.activedate as activated
     ,e.entered
     ,datediff(e.entered,a.activedate) as days
    from dom_account as a
    join (
      select
        ClientRef
       ,MIN(DateDue) as collected
      from dd_collection
      where ClientRef like 'BB%'
        and PaidAmount>0
      group by DDRefNo
      order by ClientRef
    ) as c
      on c.ClientRef=a.client_ref
    join (
      select
        client_ref
       ,MIN(draw_day) as entered
      from dom_entry
      where client_ref like 'BB%'
      group by client_ref
      order by client_ref
    ) as e
      on e.client_ref=a.client_ref
    where a.client_ref like 'BB%'
    group by a.id
    order by a.client_ref
    ;
    select
      days
     ,count(id)
    from tmp
    group by days
    ;
This suggests that MIN(dom_entry.draw_day) is 6 days after
dom_account.activedate (with a few unexplained anomalies)

So Mark replaced the old function for generating zaffo-repair-shop
activedate (always a Saturday):
*/
    $fcd = new DateTime ($first_collection_date);
    $fcd->add (new DateInterval(BLOTTO_PAY_DELAY));
    $dayofweek  = $fcd->format ('w'); // 0 = Sunday. 6 = Saturday.
    $daysuntilactive = 20 - $dayofweek;
    if ($daysuntilactive<16) { // if friday or saturday, add a week.
        $daysuntilactive = $daysuntilactive + 7;
    }
    $fcd->add (new DateInterval('P'.$daysuntilactive.'D'));
//    return $fcd->format ('Y-m-d');
$old = $fcd->format ('Y-m-d');
/*
with this new one along with its new algorithmic description for
generating blotto2 first_draw_close (always a Friday):
*/    
    /*
    1. Take first_collection_date
    2. Add pay delay
    3. Move on to next Friday (even if first_collection_date is a Friday)
    4. Move on 21 more days
    */
    $fcd        = new DateTime ($first_collection_date);
    $fcd->add (new DateInterval(BLOTTO_PAY_DELAY));
    // Move on to next Friday
    $days       = (12-$fcd->format('w')) % 7;
    if ($days==0) {
        // Even if collection date is a Friday
        $days  += 7;
    }
    // Move on 21 more days
    $days      += 21;
    $fcd->add (new DateInterval('P'.$days.'D'));
//    return $fcd->format ('Y-m-d');
$new = $fcd->format ('Y-m-d');

/*
Temporary check to make sure that this is always a 6-day difference.
In other words, confirm that Mark did not bungle it.
*/
$date1          = date_create ($old);
$date2          = date_create ($new);
$diff           = date_diff ($date1,$date2);
if ($diff->format("%a")!=6) {
    fwrite (STDERR,"draw_first_zaffo_model() being naughty:\n");
    fwrite (STDERR,"    activedate            = $old\n");
    fwrite (STDERR,"    first_draw_close      = $new\n");
    fwrite (STDERR,"Why is this not a 6-day difference?\n");
    exit (127);
}
return $new;

}

function draw_upcoming_dow_nths_in_months ($dow,$nths,$months,$today=null) {
    $dt             = new DateTime ($today);
    // Allow dow to be loose
    $dow            = intval($dow) %7;
    if (!is_array($nths) || !is_array($months)) {
        // Illegal input type
        throw new \Exception ('Second and third arguments must be arrays');
        return false;
    }
    foreach ($nths as $i=>$o) {
        $nths[$i]   = intval ($o);
        if ($nths[$i]<1 || $nths[$i]>4) {
            // Illegal input
            throw new \Exception ('nth of month can only be 1, 2, 3 or 4');
            return false;
        }
    }
    // Allow months to be loose
    foreach ($months as $i=>$m) {
        $months[$i] = (intval($m-1)%12) +1;
    }
    // Empty array implies every month
    if (!count($months)) {
        $months = [1,2,3,4,5,6,7,8,9,10,11,12];
    }
    $count          = 0;
    while ($count<367) {
        $count++;
        if (in_array($dt->format('n'),$months)) {
            if ($dt->format('w')==$dow) {
                $day = $dt->format('j');
                $ord = (($day-($day%7))/7)+1;
                if (in_array($ord,$nths)) {
                    return $dt->format ('Y-m-d');
                }
            }
        }
        $dt->add (new DateInterval('P1D'));
    }
    // Sanity
    throw new \Exception ('No date found in a whole year');
    return false;
}

function draw_upcoming_weekly ($dow,$today=null) {
    // Get draw close date of next weekly draw
    // in the future (today is in the future)
    // for a given day of week
    $dow = intval($dow) % 7;
    $day = new DateTime ($today);
    for ($i=0;$i<7;$i++) {
        if ($day->format('w')==$dow) {
            return $day->format ('Y-m-d');
        }
        $day->add (new DateInterval('P1D'));
    }
}

function draws ($from,$to) {
    $rows       = array ();
    $q      = "
      SELECT
        DATE_FORMAT(`draw_closed`,'%Y %b %d')
       ,`ccc`
       ,`supporters_entered`
       ,`tickets_entered`
      FROM  `Draws_Summary`
      WHERE `draw_closed`>='$from'
        AND `draw_closed`<='$to'
      ORDER BY `draw_closed`,`ccc`
    ";
    $zo         = connect ();
    if (!$zo) {
        return $rows;
    }
    try {
        $c      = $zo->query ($q);
        while ($r=$c->fetch_assoc()) {
            array_push ($rows,$r);
        }
        return $rows;
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('draws(): '.$e->getMessage());
        return $rows;
    }
}

function draws_outstanding ( ) {
    $first          = BLOTTO_DRAW_CLOSE_1;
    $zo             = connect (BLOTTO_MAKE_DB);
    $q              = "
      SELECT
        IF(
          MAX(`draw_closed`) IS NULL
         ,'$first'
         ,DATE_ADD(MAX(`draw_closed`),INTERVAL 1 DAY)
        ) AS `start`
       ,CURDATE() AS `future`
      FROM  `blotto_entry`
    ";
    try {
        $range      = $zo->query($q)->fetch_assoc ();
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $dates          = [];
    $date           = $range['start'];
    while (1) {
        $date       = draw_upcoming ($date);
        if ($date>=$range['future']) {
            break;
        }
        array_push ($dates,$date);
        $date       = day_tomorrow($date)->format('Y-m-d');
    }
    return $dates;
}

function draws_super ($from,$to) {
    $rows       = array ();
    $q      = "
      SELECT
        DATE_FORMAT(`draw_closed`,'%Y %b %d')
       ,`supporters_entered`
       ,`tickets_entered`
      FROM  `Draws_Supersummary`
      WHERE `draw_closed`>='$from'
        AND `draw_closed`<='$to'
      ORDER BY `draw_closed`
    ";
    $zo         = connect ();
    if (!$zo) {
        return $rows;
    }
    try {
        $c      = $zo->query ($q);
        while ($r=$c->fetch_assoc()) {
            array_push ($rows,$r);
        }
        return $rows;
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('draws(): '.$e->getMessage());
        return $rows;
    }
}

function enter_super ($org_id,$db,$draw_closed,$entries) { 
    $dbm            = $db['make'];
    $dbf            = $db['frontend'];
    $rbe_make_db    = BLOTTO_MAKE_DB;
    $rbe_db         = BLOTTO_DB;
    $ticket_db      = BLOTTO_TICKET_DB;
    $amount         = BLOTTO_TICKET_PRICE;
    $tickets        = [];
    try {
        $zo = connect (BLOTTO_MAKE_DB);
        $qs = "
          SELECT
            `client_ref`
           ,GROUP_CONCAT(`number` SEPARATOR ',') AS `numbers`
          FROM `$ticket_db`.`blotto_ticket`
          WHERE `org_id`=$org_id
          GROUP BY `client_ref`
          ;
        ";
        $ts = $zo->query ($qs);
        while ($row=$ts->fetch_assoc()) {
            $tickets[$row['client_ref']] = explode (',',$row['numbers']);
        }
        $qi         = "
          INSERT IGNORE INTO `blotto_entry`
          (`draw_closed`,`client_ref`,`ticket_number`)
          VALUES
        ";
        foreach ($entries as $e) {
            if (!array_key_exists($e['client_ref'],$tickets)) {
                fwrite (STDERR,$qs);
                throw new \Exception ("ClientRef '{$e['client_ref']}' has no tickets");
                return false;
            }
            foreach ($tickets[$e['client_ref']] AS $number) {
                $qi .= "('$draw_closed','{$e['client_ref']}','$number'),\n";
            }
        }
        // Put your data in
        $qi = substr ($qi,0,-2);
        $zo->query ($qi);
        // Now put your data out
        $qi = "
          INSERT IGNORE INTO `{{DB}}`.`blotto_super_entry`
            SELECT
              null
             ,'$rbe_db'
             ,`id`
             ,'$draw_closed'
             ,`ticket_number`
             ,$amount
             ,`client_ref`
             ,NOW()
            FROM `blotto_entry` AS `e`
            WHERE `e`.`draw_closed`='$draw_closed'
          ;
        ";
        $zo->query (str_replace('{{DB}}',$dbm,$qi));
        $zo->query (str_replace('{{DB}}',$dbf,$qi));
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
}

function entries ($date,$db=BLOTTO_MAKE_DB) {
    $qs = "
      SELECT
        *
      FROM `blotto_entry`
      WHERE `draw_closed`='$date'
      ORDER BY `id`
    ";
    try {
        $zo = connect ($db);
        $es = $zo->query ($qs);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $entries = [];
    while ($e=$es->fetch_assoc()) {
        $entries[$e['id']] = $e;
    }
    return $entries;
}

function esc ($str,$db=BLOTTO_DB) {
    return connect($db)->escape_string ($str);
}

function escm ($str) {
    return esc ($str,BLOTTO_MAKE_DB);
}

function etrs ($to,$sort='date') {
    $crs        = BLOTTO_CREF_SPLITTER;
    $ncb        = BLOTTO_NO_CLAWBACK;
    $rows       = array ();
    $zo         = connect ();
    if (!$zo) {
        return $rows;
    }
    $q          = "
      SELECT
        `s`.`approved`
       ,`s`.`client_ref`
       ,`s`.`canvas_ref`
       ,IFNULL(`c1`.`latest`,'')
       ,IFNULL(`c1`.`collections`,0)
       ,`p1`.`chances`
       ,`p2`.`chances`-`p1`.`chances`
       ,`p2`.`chances`
       ,IFNULL(`c2`.`collections`,0)
       ,IFNULL(`c2`.`earliest`,'')
       ,`p2`.`client_ref`
      FROM `blotto_supporter` as `s`
      -- The first player for each supporter
      JOIN `blotto_player` AS `p1`
        ON `p1`.`client_ref`=`s`.`client_ref`
      LEFT JOIN (
        SELECT
          `cc`.`ClientRef`
         ,`cm`.`Freq`
         ,COUNT(`cc`.`id`) AS `collections`
         ,MAX(`cc`.`DateDue`) AS `latest`
        FROM `blotto_build_collection` AS `cc`
        JOIN `blotto_build_mandate` AS `cm`
          ON `cm`.`Provider`=`cc`.`Provider`
         AND `cm`.`RefNo`=`cc`.`RefNo`
        WHERE `cc`.`DateDue`<='$to'
        GROUP BY `Provider`,`RefNo`
      ) AS `c1`
        ON `c1`.`ClientRef`=`p1`.`client_ref`
      JOIN (
          SELECT
            `supporter_id`
           ,MIN(`client_ref`) AS `client_ref`
          FROM `blotto_player`
          WHERE `client_ref` LIKE '%$crs%'
          GROUP BY `supporter_id`
      ) AS `pnext`
        ON `pnext`.`supporter_id`=`s`.`id`
      JOIN `blotto_player` AS `p2`
        ON `p2`.`client_ref`=`pnext`.`client_ref`
      LEFT JOIN (
        SELECT
          `ClientRef`
         ,COUNT(`id`) AS `collections`
         ,MIN(`DateDue`) AS `earliest`
        FROM `blotto_build_collection`
        WHERE `DateDue`<='$to'
        GROUP BY `Provider`,`RefNo`
      ) AS `c2`
        ON `c2`.`ClientRef`=`p2`.`client_ref`
      WHERE `c1`.`Freq`='Monthly'
        AND IFNULL(`c1`.`collections`,0)<$ncb
        AND `p1`.`chances`<`p2`.`chances`
      ORDER BY {{ORDER}}
    ";
    if ($sort=='date') {
        $order = "`s`.`approved`,`s`.`client_ref`";
    }
    else {
        $order = "`s`.`client_ref`,`s`.`approved`";
    }
    $q      = str_replace ('{{ORDER}}',$order,$q);
    try {
        $c      = $zo->query ($q);
        while ($r=$c->fetch_assoc()) {
            array_push ($rows,$r);
        }
        return $rows;
    }
    catch (\mysqli_sql_exception $e) {
        return $rows;
    }
}

function fields ( ) {
    $dbc = BLOTTO_CONFIG_DB;
    $oid = BLOTTO_ORG_ID;
    $fields = array ();
    $zo = connect ();
    if (!$zo) {
        return $fields;
    }
    $q = "
      SELECT
        *
      FROM `$dbc`.`blotto_field`
      WHERE `org_id`=$oid
      ORDER BY `p_number`
    ";
    try {
        $fs = $zo->query ($q);
        while ($f=$fs->fetch_assoc()) {
            array_push ($fields,(object) $f);
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($e->getMessage());
    }
    return $fields;
}

function file_write ($file,$contents) {
    if (file_exists($file)) {
        fwrite (STDERR,debug_backtrace()[1]['function']."(\n");
        fwrite (STDERR,print_r(debug_backtrace()[1]['args'],true));
        fwrite (STDERR,")\n");
        fwrite (STDERR,debug_backtrace()[2]['function']."(\n");
        fwrite (STDERR,print_r(debug_backtrace()[2]['args'],true));
        fwrite (STDERR,")\n");
        throw new \Exception ("File $file already exists");
        return false;
    }
    $fp = fopen ($file,'w');
    fwrite ($fp,$contents);
    fclose ($fp);
    return true;
}

function get_argument ($element,&$array=false) {
    global $argv;
    if (!is_array($argv)) {
        return false;
    }
    if (is_array($array) && array_key_exists($element,$array)) {
        return $array[$element];
    }
    for ($i=1;array_key_exists($i,$argv);$i++) {
        if (strpos($argv[$i],'--')===0) {
            $a = explode ('=',substr($argv[$i],2));
            if (!$a[0]) {
                continue;
            }
            if (is_array($array)) {
                $array[$a[0]] = $a[1];
            }
            if ($a[0]==$element) {
                return $a[1];
            }
        }
        if (strpos($argv[$i],'-')===0) {
            if (strpos($argv[$i],$element)) {
                if (is_array($array)) {
                    $array[$element] = true;
                }
                return true;
            }
        }
    }
    return false;
}

function html ($snippet,$title='Untitled',$output=true) {
    if ($output) {
        require __DIR__.'/html.php';
        return;
    }
    ob_start ();
    require __DIR__.'/html.php';
    $html = ob_get_contents ();
    ob_end_clean ();
    return $html;
}

function is_https ( ) {
    if (php_sapi_name()=='cli') {
        return false;
    }
    if (empty($_SERVER['HTTPS'])) {
        return false;
    }
    if (strtolower($_SERVER['REQUEST_SCHEME'])!='https') {
        return false;
    }
    if ($_SERVER['SERVER_PORT']==80) {
        return false;
    }
    return true;
}

function link_query ($target,$table,$date,$interval=null) {
    $datefields = array (
        'ANLs'             => 'tickets_issued',
        'Cancellations'    => 'Cancelled_Date',
        'Changes'          => 'changed_date',
        'Draws'            => 'draw_closed',
        'Insurance'        => 'draw_close_date',
        'Supporters'       => 'created',
        'Updates'          => 'updated',
        'Wins'             => 'draw_closed'
    );
    $datefield = $datefields[$table];
    $table = table_name ($table);
    if (!$table) {
        error_log ('link_query(): table_name() failed');
        return '#error-1';
    }
    try {
        if ($interval) {
            $from = new DateTime ($date);
            $from->add (new DateInterval('P1D'));
            $from->sub (new DateInterval($interval));
        }
        else {
            $from = day_one ();
        }
    }
    catch (Exception $e) {
        error_log ('link_query(): '.$e->getMessage());
        return '#error-2';
    }
    $from = $from->format('Y-m-d');
    if (strtolower($table)=='draws') {
        $dt1    = new DateTime ($from);
        $dt2    = new DateTime ($date);
        if ($dt1->diff($dt2)->format('%r%a')>6) {
            // Too huge so just give summary
            $table = 'Draws_Supersummary';
        }
    }
    elseif (strtolower($table)=='insurance') {
        $dt1    = new DateTime ($from);
        $dt2    = new DateTime ($date);
        if ($dt1->diff($dt2)->format('%r%a')>6) {
            // Too huge so just give summary
            $table = 'Insurance_Summary';
        }
    }
    if ($target=='download') {
        $q = './?download&table='.$table.'&field='.$datefield.'&from='.$from.'&to='.$date;
        return $q;
    }
    if ($target=='adminer') {
        $q = './adminer.php?select='.$table.'&where[0][col]='.$datefield.'&where[0][op]=<=&where[0][val]='.$date.'&where[01][col]='.$datefield.'&where[01][op]=>=&where[01][val]='.$from.'&limit='.BLOTTO_WWW_ROWS_MAX_VIEW;
        return $q;
    }
}

function links_report ($fname,$number,$xhead) {
    $p = func_get_args ();
    array_shift ($p);
    array_shift ($p);
    array_shift ($p);
    $params = '';
    foreach ($p as $i=>$v) {
        $params .= '&amp;p'.$i.'='.htmlspecialchars($v);
    }
    require __DIR__.'/links_report.php';
}

function month_end_last ($format='Y-m-d',$date=null) {
    $date = new DateTime ();
    $date->modify ('last day of previous month');
    return $date->format ($format);
}

function month_last ( ) {
    // Previous month of the months as far back as day_one()
    $end1           = null;
    $end2           = null;
    $end3           = null;
    $months         = array_reverse (months());
    if (!count($months)) {
        // Day one is in this month
        return [null,null];
    }
    foreach ($months as $end=>$m) {
        if (!$end3) {
            $end3   = $end;
            continue;
        }
        if (!$end2) {
            $end2   = $end;
            continue;
        }
        if (!$end1) {
            $end1   = $end;
            break;
        }
    }
    $dt             = new DateTime ($end1);
    $dt->add (new DateInterval('P1D'));
    return [$dt->format ('Y-m-d'),$end2,$dt->format('M Y')];
}

function months ($date1=null,$date2=null,$format='Y-m-d') {
    $f              = 'Y-m-d';
    if ($date1===null) {
        $date1      = day_one()->format ($f);
    }
    if ($date2===null) {
        $date2      = date ($f);
    }
    // Convert and validate
    $ds             = DateTime::createFromFormat($f, $date1);
    if (!$ds || $ds->format($f)!==$date1) {
        throw new \Exception ('First date is not valid');
        return false;
    }
    $de             = DateTime::createFromFormat($f, $date2);
    if (!$de || $de->format($f)!==$date2) {
        throw new \Exception ('Second date is not valid');
        return false;
    }
    // Check order and swap if needed.
    if ($ds>$de) {
        $tmp        = $ds;
        $ds         = $de;
        $de         = $tmp;
    }
    // So now $ds is "date_start" and $de is "date end"
    $output         = [];
    do {
        $output[$ds->format('Y-m-t')] = $ds->format ($format);
        $ds->modify ('first day of next month');
    }
    while ($ds<$de);
    return $output;
}

function notarisation ($file) {
    if (!is_readable($file)) {
        throw new \Exception ("Could not read file '$file'");
        return false;
    }
    $tsq        = $file.'.tsq';
    $tsr        = $file.'.tsr';
    if (file_exists($tsr)) {
        throw new \Exception ("Notarisation tsr file '$tsr' already exists");
        return false;
    }
    $info       = $file.'.info.txt';
    /*
     openssl ts -query -data file.png -no_nonce -sha512 -cert -out file.tsq
     curl -H "Content-Type: application/timestamp-query" --data-binary '@file.tsq' https://freetsa.org/tsr > file.tsr
     openssl ts -verify -in file.tsr -queryfile file.tsq -CAfile cacert.pem -untrusted tsa.crt # verify
     openssl ts -reply -in file.tsr -text # cert details inc timestamp
    */
    $cmd        = 'openssl ts -query -data '.escapeshellarg($file).' ';
    $cmd       .= '-no_nonce -sha512 -cert -out '.escapeshellarg($tsq).' ';
    $cmd       .= '2>&1';
    exec ($cmd,$out,$rtn);
    if ($rtn) {
        error_log (print_r($out,true));
        throw new \Exception ("Could not create TSA query file '$tsq'");
        return false;
    }
    $cmd        = 'curl -H "Content-Type: application/timestamp-query" ';
    $cmd       .= " --silent --show-error "; // suppress progress bar but allow errors.  NB order of flags is important!
    $cmd       .= " --data-binary '@".$tsq."' ";
    $cmd       .= escapeshellarg(BLOTTO_TSA_URL)." > ".$tsr;
    exec ($cmd, $out, $rtn);
    if ($rtn) {
        // error_log (print_r($out,true));
        throw new \Exception ("Could not create TSA response file '$tsr'");
        return false;
    }
    unlink ($tsq);
    $file       = basename ($file);
    $tsq        = basename ($tsq);
    $tsr        = basename ($tsr);
    $crt        = basename (BLOTTO_TSA_CERT);
    $cac        = basename (BLOTTO_TSA_CACERT);
    $help       = "# How to verify draw entry list creation time\n";
    $help      .= "# -------------------------------------------\n";
    $help      .= "\n";
    $help      .= "# Download these files to your current working directory:\n";
    $help      .= "#  * $file\n";
    $help      .= "#  * $tsr\n";
    $help      .= "#  * ".BLOTTO_TSA_CERT."\n";
    $help      .= "#  * ".BLOTTO_TSA_CACERT."\n";
    $help      .= "\n";
    $help      .= "# Create a timestamp query file\n";
    $help      .= "    openssl ts -query -data $file -no_nonce -sha512 -cert -out $tsq\n";
    $help      .= "\n";
    $help      .= "# Verify the timestamp\n";
    $help      .= "    openssl ts -verify -in $tsr -queryfile $tsq -CAfile $cac -untrusted $crt\n";
    $help      .= "\n";
    $help      .= "# Get the certificate details (including the timestamp)\n";
    $help      .= "    openssl ts -reply -in $tsr -text\n";
    $help      .= "\n";
    file_write ($info,$help);
}

function notarise ($draw_closed,$data,$file,$csv=false,$headers=false) {
    $dir_proof  = BLOTTO_PROOF_DIR;
    if (!is_dir($dir_proof)) {
        throw new \Exception ("Directory BLOTTO_PROOF_DIR=$dir_proof does not exist");
        return false;
    }
    $dir        = $dir_proof.'/'.$draw_closed;
    if (!is_dir($dir)) {
        mkdir ($dir,0755,true);
    }
    $file       = $dir.'/'.$file;
    if ($csv) {
        csv_write ($file,$data,$headers);
    }
    else {
        if (is_object($data) || is_array($data)) {
            $data   = json_encode ($data,JSON_PRETTY_PRINT);
        }
        file_write ($file,$data);
    }
    notarisation ($file); 
}

function notarised ($draw_closed,$results=false) {
    if ($results) {
        if (file_exists(BLOTTO_PROOF_DIR.'/'.$draw_closed.'/results_nrmatch.json.tsr')) {
            return true;
        }
        if (file_exists(BLOTTO_PROOF_DIR.'/'.$draw_closed.'/results_raffle.json.tsr')) {
            return true;
        }
        return false;
    }
    return file_exists (BLOTTO_PROOF_DIR.'/'.$draw_closed.'/draw.csv.tsr');
}

function notify ($to,$subject,$message) {
    global $Notes;
    $headers        = null;
    if (defined('BLOTTO_EMAIL_FROM')) {
        $headers    = "From: ".BLOTTO_EMAIL_FROM."\n";
    }
    mail (
        $to,
        BLOTTO_BRAND." - ".$subject." for ".BLOTTO_ORG_NAME." from ".BLOTTO_MC_NAME,
        $message."\n\nNotes: ".print_r($Notes,true),
        $headers
    );
}

function players_new (&$players,&$tickets,$oid=BLOTTO_ORG_ID,$db=null) {
    if (!$db) {
        $db = ['make'=>BLOTTO_MAKE_DB];
    }
    $qs ="
      SELECT
        $oid AS `org_id`
       ,`m`.`Provider`
       ,`m`.`RefNo`
       ,`m`.`ClientRef`
       ,`p`.`chances`
       ,`tk`.`tickets`
       ,IFNULL(`p`.`chances`,0)-IFNULL(`tk`.`tickets`,0) AS `qty`
      FROM `{$db['make']}`.`blotto_player` AS `p`
      JOIN (
        SELECT
          `Provider`
         ,`RefNo`
         ,`ClientRef`
        FROM `{$db['make']}`.`blotto_build_mandate`
        WHERE 1
        GROUP BY `RefNo`
      ) AS `m`
        ON `p`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN (
        SELECT
          `mandate_provider`
         ,`client_ref`
         ,IFNULL(COUNT(`number`),0) AS `tickets`
        FROM `blotto_ticket`
        WHERE `org_id`=$oid
        GROUP BY `client_ref`
      )      AS `tk`
             ON `tk`.`mandate_provider`=`m`.`Provider`
            AND `tk`.`client_ref`=`m`.`ClientRef`
      WHERE `p`.`chances` IS NOT NULL
      HAVING `qty`>0
      ;
    ";
    try {
        $zo             = connect (BLOTTO_TICKET_DB);
        $ps             = $zo->query ($qs);
        while ($p=$ps->fetch_assoc()) {
            $tickets   += $p['qty'];
            $p['db']    = $db;
            array_push ($players,$p);
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
}

function plural ($num) {
    if ($num==1) {
        return '';
    }
    return 's';
}

function prize_calc ($prize,$verbose) {
    if ($verbose) {
        echo "prize_calc():\n";
        print_r ($prize);
    }
    $amount     = $prize['amount_brought_forward'];
    if ($verbose) {
        echo "bf = $amount\n";
    }
    if ($prize['amount_cap'] && $amount>$prize['amount_cap']) {
        if ($verbose) {
            echo "capped at $amount\n";
        }
        return $amount;
    }
    if ($verbose) {
        echo "prize={$prize['amount']}, rollovers={$prize['rollover_count']}, rollover_amount={$prize['rollover_amount']}\n";
    }
    $amount    += $prize['amount'];
    $amount    += $prize['rollover_count'] * $prize['rollover_amount'];
    if ($verbose) {
        echo "calculated = $amount\n";
    }
    if ($prize['amount_cap'] && $amount>$prize['amount_cap']) {
        if ($verbose) {
            echo "capped at = {$prize['amount_cap']}\n";
        }
        return $prize['amount_cap'];
    }
    return $amount;
}

function prize_function (&$p,$draw_closed) {
    if (!function_exists($p['function_name'])) {
        throw new \Exception ("Prize {$p['level']}@{$p['starts']} function_name {$p['results_function']}() does not exist");
        return false;
    }
    // Bespoke functions should add array of ticket numbers to $p['results']
    $p['results'] = $p['function_name'] ($p,$draw_closed);
    $qi = "INSERT INTO `blotto_result` (`draw_closed`,`draw_date`,`prize_level`,`number`) VALUES ";
    foreach ($p['results'] as $ticket) {
        $qi .= "('$draw_closed',drawOnOrAfter('$draw_closed'),{$p['level']},'$ticket'),";
    }
    try {
        $qi         = substr ($qi,0,-1);
        $zo         = connect (BLOTTO_RESULTS_DB);
        $zo->query ($qi);
    }
    catch (\mysqli_sql_exception $e) {
        fwrite(STDERR, $qi.";\n");
        throw new \Exception ($e->getMessage());
        return false;
    }
    return true;
}

function prize_match ($entry,$prizelist,$winner) {
    foreach($prizelist as $matchlen => $p) {
        $ticket = $entry['ticket_number'];
        if ( (substr($ticket, 0, $matchlen) == substr($winner, 0, $matchlen))
          || (substr($ticket, -$matchlen) == substr($winner, -$matchlen)) ){
            return ($p);
        }
    }
    return false;
}

function prize_pot ($draw_closed,$quids_per_thou,$verbose=false) {
    $q ="
      SELECT
        COUNT(`e`.`id`) AS `tickets`
      FROM `blotto_entry` AS `e`
      WHERE `e`.`draw_closed`='$date'
      ;
    ";
    try {
        $zo = connect (BLOTTO_MAKE_DB);
        $ts = $zo->query ($qs);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $t = $ts->fetch_assoc (); // Tickets
    $r = $t * BLOTTO_TICKET_PRICE/100; // Quids collected
    return $quids_per_thou * $r/1000; // Quids pay-out
}

function prizes ($date) {
    $rdb = BLOTTO_RESULTS_DB;
    $qs = "
      SELECT
        `p`.`starts`
       ,`p`.`level`
       ,`p`.`expires`
       ,`p`.`name`
       ,`p`.`insure`
       ,`p`.`function_name`
       ,`p`.`level_method`
       ,`p`.`quantity`
       ,`p`.`quantity_percent`
       ,`p`.`amount`
       ,`p`.`amount_cap`
       ,`p`.`amount_brought_forward`
       ,`p`.`rollover_amount`
       ,`p`.`rollover_cap`
       ,`p`.`rollover_count`
       ,`p`.`results_manual`
       ,`r`.`results`
      FROM `blotto_prize` AS `p`
      -- Latest start date that is not after date arg
      -- Ignore rows that expire before date arg
      JOIN (
        SELECT
          `level`
         ,MAX(`starts`) AS `start_date`
        FROM `blotto_prize`
        WHERE `starts`<='$date'
          AND `expires`>='$date'
        GROUP BY `level`
      ) AS `current`
        ON `current`.`level`=`p`.`level`
       AND `current`.`start_date`=`p`.`starts`
      -- Results on date arg = draw_closed
      LEFT JOIN (
        SELECT
          `prize_level`
         ,GROUP_CONCAT(`number` SEPARATOR ',') AS `results`
        FROM `$rdb`.`blotto_result`
        WHERE `draw_closed`='$date'
        GROUP BY `prize_level`
      )      AS `r`
             ON `r`.`prize_level`=`p`.`level`
      ORDER BY `p`.`level`
      ;
    ";
    try {
        $zo = connect (BLOTTO_MAKE_DB);
        $ps = $zo->query ($qs);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $prizes = [];
    while ($p=$ps->fetch_assoc()) {
        if ($p['results']) {
            $p['results'] = explode (',',$p['results']);
        }
        $p['group']             = null;
        if ($p['level_method']!='RAFF') {
            $p['length']        = substr ($p['level_method'],0,1);
            $p['left']          = stripos($p['level_method'],'L') !== false;
            $p['right']         = stripos($p['level_method'],'R') !== false;
        }
        $prizes[$p['level']]    = $p;
    }
    ksort ($prizes);
    return $prizes;
}

function random_numbers ($min,$max,$num_of_nums,$reuse,&$proof) {
    // $reuse=false means returned numbers must not be repeated
    $min                            = intval ($min);
    $max                            = intval ($max);
    $num_of_nums                    = intval ($num_of_nums);
    if ($min<0 || $max<0 || $max<=$min) {
        throw new \Exception ("Number range $min-$max is not valid");
        return false;
    }
    if (!$reuse && $num_of_nums>(1+$max-$min)) {
        throw new \Exception ("Number range $min-$max is not big enough without reusing numbers");
        return false;
    }
    $request                        = new \stdClass ();
    $request->id                    = uniqid ();
    $request->jsonrpc               = BLOTTO_TRNG_API_VERSION;
    $request->method                = BLOTTO_TRNG_API_METHOD;
    $request->params                = new \stdClass ();
    $request->params->apiKey        = BLOTTO_TRNG_API_KEY;
    $request->params->base          = 10;
    $request->params->userData      = null;
    $request->params->min           = $min;
    $request->params->max           = $max;
    $request->params->n             = $num_of_nums;
    $request->params->replacement   = $reuse;
    $datetime                       = date ('Y-m-d H:i:s');
    $c                              = curl_init (BLOTTO_TRNG_API_URL);
    if (!$c) {
        throw new \Exception ('Failed to curl_init("'.BLOTTO_TRNG_API_URL.'")');
        return false;
    }
    $s                              = curl_setopt_array (
        $c,
        array (
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_VERBOSE         => false,
            CURLOPT_NOPROGRESS      => true,
            CURLOPT_FRESH_CONNECT   => true,
            CURLOPT_CONNECTTIMEOUT  => BLOTTO_TRNG_API_TIMEOUT,
            CURLOPT_POST            => true,
            CURLOPT_HTTPHEADER      => array (BLOTTO_TRNG_API_HEADER),
            CURLOPT_POSTFIELDS      => json_encode ($request)
        )
    );
    if (!$s) {
        throw new \Exception ('Failed to curl_setopt_array()');
        return false;
    }
    $response                       = curl_exec ($c);
    if ($response===false) {
        throw new \Exception ('Error: '.curl_error($c));
        return false;
    }
    $return                         = new \stdClass ();
    $return->request                = $request;
    $return->request->datetime      = $datetime;
    $return->request->params->apiKey                    = '********';
    $response                       = json_decode ($response);
    if (isset($response->error)) {
      unset($request->params->apiKey);
      print_r($request);
      throw new \Exception ('random.org error: '.$response->error->code.' '.$response->error->message);
      return false;
    }
    $return->response               = $response;

    $results                        = implode (',',$return->response->result->random->data);
    $random_object                  = json_encode ($return->response->result->random);
    $log                            = json_encode ($return);
    $q ="
      INSERT INTO `blotto_generation`
      SET
        `provider`='".BLOTTO_TRNG_API."'
       ,`reference`='{$return->request->id}'
       ,`generated`='{$return->response->result->random->completionTime}'
       ,`min`='{$return->request->params->min}'
       ,`max`='{$return->request->params->max}'
       ,`n`='{$return->request->params->n}'
       ,`results_csv`='{$results}'
       ,`random_object`='$random_object'
       ,`signature`='{$return->response->result->signature}'
       ,`log`='{$log}'
    ";
    try {
        $zo = connect (BLOTTO_MAKE_DB);
        $rs = $zo->query ($q);
        $return->generation_id = $zo->insert_id;
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ('SQL error: '.$e->getMessage());
        return false;
    }
    $proof  = "Visit ".BLOTTO_TRNG_API_VERIFY." and fill in the form with the following details.\n\n";
    $proof .= "Format: JSON\n\n";
    $proof .= "Random:\n";
    $proof .= $random_object."\n\n";
    $proof .= "Signature:\n";
    $proof .= $return->response->result->signature."\n\n";
    return $return;
}

function report ( ) {
    if (!array_key_exists('type',$_GET) || !in_array($_GET['type'],['table','csv'])) {
        return 'invalid request - report type "'.$_GET['type'].'" not recognised';
    }
    if (!array_key_exists('fn',$_GET) || !$_GET['fn']) {
        return 'invalid request - no file name given';
    }
    if (!array_key_exists('nr',$_GET) || !$_GET['nr']) {
        return 'invalid request - no report number given';
    }
    if (!array_key_exists('xh',$_GET) || !$_GET['xh']) {
        return 'invalid request - no x-axis heading given';
    }
    $p          = array ();
    foreach ($_GET as $k=>$v) {
        if (preg_match('<^p[0-9]+$>',$k)) {
            $p[substr($k,1)] = $v;
        }
    }
    $output     = chart ($_GET['nr'],$_GET['type'],...$p);
    if (!$output) {
        return 'chart has no output';
    }
    $output     = str_replace ('{{XHEAD}}',htmlspecialchars($_GET['xh']),$output);
    if ($_GET['type']=='table') {
        header ('Content-Type: text/html');
        header ('Content-Disposition: attachment; filename="'.$_GET['fn'].'.html"'); 
    }
    else {
        header ('Content-Type: text/csv');
        header ('Content-Disposition: attachment; filename="'.$_GET['fn'].'.csv"'); 
    }
    echo $output;
    exit;
}

function result_spiel66 ($prize,$draw_closed) {
    // The result from this function is the same regardless of prize properties
    // That is, $prize is not used
    $dt      = new DateTime ($draw_closed);
    $dt->add (new DateInterval('P1D'));
    $date    = $dt->format ('Y/n/j');
    if (!defined('BLOTTO_SPIEL66_FILE')) {
        define ('BLOTTO_SPIEL66_FILE',BLOTTO_DIR_EXPORT.'/spiel66.html.log');
    }
    if (file_exists(BLOTTO_SPIEL66_FILE)) {
        unlink (BLOTTO_SPIEL66_FILE);
        if (file_exists(BLOTTO_SPIEL66_FILE)) {
            throw new \Exception ('Could not remove old file: '.BLOTTO_SPIEL66_FILE);
            return false;
        }
    }
    ob_start ();
    $url = "https://www.lotterypost.com/results/germany/$date";
    exec ("wget -S -O ".escapeshellarg(BLOTTO_SPIEL66_FILE)." $url 2>&1");
    if (!file_exists(BLOTTO_SPIEL66_FILE)) {
        throw new \Exception ("Could not create file ".BLOTTO_SPIEL66_FILE." from $url");
        return false;
    }
    if (!file_get_contents(BLOTTO_SPIEL66_FILE)) {
        throw new \Exception ("Empty file ".BLOTTO_SPIEL66_FILE." from $url");
        return false;
    }
    $output  = ob_get_contents ();
    ob_end_clean ();
    if (stripos($output,'Location:')!==false) {
        throw new \Exception ("Request was redirected, so results were not found for $date");
        return false;
    }
    $dom     = new \DOMDocument ();
    @$dom->loadHTMLFile (BLOTTO_SPIEL66_FILE);
    $block   = $dom->getElementById ('results');
    if (!$block) {
        throw new \Exception ("No tag having id=\"results\" found (for $date) in ".BLOTTO_SPIEL66_FILE);
        return false;
    }
    $results = $block->getElementsByTagName('tr');
    foreach ($results as $result) {
        if ($result->childNodes->item(0)->childNodes->item(0)->getAttribute('title')=='Spiel 77') {
            $draw = $result->childNodes->item(1)->childNodes->item(1);
            $list = $draw->childNodes->item(0)->childNodes->item(0);
            $number = '';
            for ($i=0;$i<6;$i++) {
                $number .= $list->childNodes->item($i)->textContent;
            }
            return [$number];
        }
    }
    throw new \Exception ("Results data was collected but no results were parsed");
    return false;
}

function search ( ) {
    // $expert removes automatic boolean operators which are to make
    // each term mandatory and match to the left
    // i.e. "smit" returns smiths, "mith" does not.
    // dashes are a pain.  searching for burton-on-trent doesn't work as wanted.
    if (array_key_exists('t',$_GET)) {
        return select ();
    }
    $string = '';
    if (array_key_exists('s',$_GET)) {
        $string = $_GET['s'];
    }
    $expert = false;
    if (array_key_exists('e',$_GET) && $_GET['e']>0) {
        $expert = true;
    }
    $limit = 20;
    if (array_key_exists('l',$_GET) && intval($_GET['l'])>0) {
        $limit = intval ($_GET['l']);
    }
    $string         = explode (' ',$string);
    $terms          = [];
    $crefterms      = [];

    $tooshort = true; // at least one term must be three or more characters; "Mr" is a legit term
    foreach ($string as $term) {
        $term_alphanum = preg_replace ('<[^A-z0-9]>','',$term);
        if (strlen($term_alphanum)>=BLOTTO_SEARCH_LEN_MIN) {
            $tooshort = false;
        }
        if (strlen($term_alphanum)) {
            if (strlen($term_alphanum) >= BLOTTO_SEARCH_CREF_MIN) {
              array_push ($crefterms,esc($term_alphanum));
            }
            if (!$expert) { // if not expert allow @ sign (but see below on that one!) and maybe others
                $term_alphanum_extended = preg_replace('<[^A-z0-9\-@]>', '', $term);  // Add \- to allow dashes through
                if (strpos($term_alphanum_extended,'-') !== false) {
                  $term = '+"'.$term_alphanum_extended.'"'; 
                }
                else {
                  $term = '+'.$term_alphanum_extended.'*'; 
                }
            }
        }
        elseif (!$expert) { // if not expert and no alphanumerics, continue
            continue;
        }
        // https://stackoverflow.com/questions/25088183/mysql-fulltext-search-with-symbol-produces-error-syntax-error-unexpected
        $term = str_replace("@", " +", $term); // 
        if (strlen($term)) {
            array_push ($terms,esc($term));
        }
    }
    if ($tooshort) {
        return '{ "short" : true }';
    }

    $fts = implode (', ', $terms);
    try {
        $rs = search_result ('s',$crefterms,$fts,$limit);
    }
    catch (\Exception $e) {
        return '{ "error" : 101 }';
    }
    if (!is_array($rs)) {
        if (!is_object($rs) || property_exists($rs,'error') || $rs->count>0) {
            if (is_object($rs)) {
                return json_encode ($rs);
            }
            return $rs;
        }
    }
    try {
        $rm = search_result ('m',$crefterms,$fts,$limit);
    }
    catch (\Exception $e) {
        return '{ "error" : 102 }';
    }
    if (!is_array($rm)) {
        if (!is_object($rm) || property_exists($rm,'error') || $rm->count>0) {
            if (is_object($rm)) {
                return json_encode ($rm);
            }
            return $rm;
        }
    }
    search_splice ($rs,$rm,$rows);
    if (count($rows)==0) {
        return '{ "count" : 0 }';
    }
    return json_encode ($rows,JSON_PRETTY_PRINT);
}

function search_result ($type,$crefterms,$fulltextsearch,$limit) {
    $zo             = connect ();
    if (!$zo) {
        return (object) ['error' => 121];
    }
    if ($type=='s') {
        $ref        = "original_client_ref";
        $table      = "Supporters";
        $index      = "`name_first`,`name_last`,`email`,`mobile`,`telephone`,`address_1`,`address_2`,`address_3`,`town`,`postcode`,`dob`";
        $fields     = "`$ref`,`signed`,CONCAT_WS(' ',`title`,`name_first`,`name_last`) AS `name`,`email`,`mobile`,`telephone`,CONCAT_WS(' ',`address_1`,`address_2`,`address_3`) AS `address`,`town`,`county`,`postcode`,`dob`";
    }
    elseif ($type=='m') {
        $ref        = "ClientRef";
        $table      = "blotto_build_mandate";
        $index      = "`Name`,`Sortcode`,`Account`,`StartDate`,`LastStartDate`,`Freq`";
        $fields     = "`ClientRef`,dateSilly2Sensible(`StartDate`) AS `FirstStartDate`,`Name`,`Amount`,`Freq`,CONCAT('***',SUBSTR(`Sortcode`,-3),'/*****',SUBSTR(`Account`,-3)) AS `Account`";
    }
    $qc = "
      SELECT
        COUNT(DISTINCT `$ref`) AS `rows`
      FROM `$table`
    ";
    $qw = "
      WHERE MATCH($index) AGAINST ('$fulltextsearch' IN BOOLEAN MODE)
    ";
    foreach ($crefterms as $term) {
        $qw .= "
        OR `$ref` LIKE '%$term%'
        ";
    }
//error_log('search_result(): '.$qc.$qw);
    try {
        $result     = $zo->query ($qc.$qw);
    }
    catch (\mysqli_sql_exception $e) {
//        return $qc.$qw;
        return (object) ['error' => 122];
    }
    if ($result) {
        while ($r=$result->fetch_assoc()) {
            $rows       = $r['rows'];
            break;
        }
    }
    else {
        return (object) ['error' => 123];
    }
    if ($rows>$limit) {
        return (object) ['count' => $rows];
    }
    $qs = "
      SELECT
        $fields
      FROM `$table`
    ";
    $qg = "
      GROUP BY `$ref`
    ";
    $qo = "
      ORDER BY `$ref`
    ";
    $ql = "
      LIMIT 0,$limit
    ";
    try {
        $result         = $zo->query ($qs.$qw.$qg.$qo.$ql);
    }
    catch (\mysqli_sql_exception $e) {
// return $e->getMessage ();
        return (object) ['error' => 124];
    }
    $rows           = [];
    if ($result) {
        while ($r=$result->fetch_assoc()) {
            $rows[$r[$ref]] = $r;
        }
    }
    else {
// return $qs.$qw.$qg.$qo.$ql;   
        return (object) ['error' => 125];
    }
    return $rows;
}

function search_splice ($supporters,$mandates,&$rows) {
    $rows = array ();
    $merged = array ();
    foreach ($supporters as $k=>$s) {
        unset ($s['original_client_ref']);
        $merged[$k] = array ('ClientRef'=>$k,'Supporter'=>implode(', ',array_filter($s)),'Mandate'=>'');
    }
    foreach ($mandates as $k=>$m) {
        unset ($m['ClientRef']);
        if (!array_key_exists($k,$merged)) {
            $merged[$k] = array ('ClientRef'=>$k,'Supporter'=>'','Mandate'=>implode(', ',array_filter($m)));
            continue;
        }
        $merged[$k]['Mandate'] = implode (', ',array_filter($m));
    }
    foreach ($merged as $r) {
        array_push ($rows,$r);
    }
}

function select ( ) {
    $type = '';
    if (array_key_exists('t',$_GET)) {
        $type = $_GET['t'];
    }
    if (!in_array($type,['m','s'])) {
        return '{ "error" : 103 }';
    }
    $cref = false;
    if (array_key_exists('r',$_GET)) {
        $cref = $_GET['r'];
    }
    if (!$cref) {
        return '{ "error" : 104 }';
    }
    $response = new stdClass ();
    $response->data = array ();
    $response->fields = array ();
    $zo = connect ();
    $cref = esc (explode(BLOTTO_CREF_SPLITTER,$cref)[0]);
    $match = '^'.$cref.BLOTTO_CREF_SPLITTER.'[0-9]{4}$';
    if ($type=='m') {
      $q = "
        SELECT
          *
        FROM `blotto_build_mandate`
        WHERE `ClientRef`='$cref'
           OR `ClientRef` LIKE '$match'
        ORDER BY `ClientRef` DESC
      ";
    }
    else {
      $q = "
        SELECT
          `s`.`client_ref` AS `ClientRef`
         ,`c`.*
        FROM `blotto_supporter` AS `s`
        JOIN `blotto_contact` AS `c`
          ON `c`.`supporter_id`=`s`.`id`
        WHERE `s`.`client_ref`='$cref'
        ORDER BY `c`.`created` DESC
      ";
    }
    try {
        $rs = $zo->query ($q);
        while ($r=$rs->fetch_assoc()) {
            array_push ($response->data,(object) $r);
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('select(): '.$e->getMessage());
        return '{ "error" : 105 }';
    }
    if ($type=='s') {
        $response->fields = fields ();
    }
    return json_encode ($response,JSON_PRETTY_PRINT);
}

function sorted_results ($date) {
    $qs = "
      SELECT
        *
      FROM `blotto_result`
      WHERE `draw_closed`='$date'
      ORDER BY `id`
    ";
    try {
      $zo = connect (BLOTTO_RESULTS_DB);
      $rs = $zo->query ($qs);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $results = [];
    while ($r=$rs->fetch_assoc()) {
        if (!array_key_exists($r['prize_level'],$results)) {
            $results[$r['prize_level']] = [];
        }
        array_push ($results[$r['prize_level']],$r);
    }
    ksort ($results);
    return $results;
}

function table ($id,$class,$caption,$headings,$data,$output=true) {
    if ($output) {
        require __DIR__.'/table.php';
        return;
    }
    ob_start ();
    require __DIR__.'/table.php';
    $table = ob_get_contents ();
    ob_end_clean ();
    return $table;
}

function table_name ($generic_name) {
    global $Tablenames;
    if (!$Tablenames) {
        $ts         = connect()->query ('SHOW TABLES');
        $Tablenames = array ();
        while ($t=$ts->fetch_array(MYSQLI_NUM)) {
            array_push ($Tablenames,$t[0]);
        }
    }
    foreach ($Tablenames as $t) {
        if (strpos($t,$generic_name)!==0) {
            continue;
        }
        // This table name starts with the generic name so:
        $suffix     = substr ($t,strlen($generic_name));
        if (preg_match('<^_[A-Z]+$>',$suffix)) {
            // Looks like a custom suffix eg. GenericName_ABC so:
            return $t;
        }
    }
    // No custom name found so:
    return $generic_name;
}

function tee ($str) {
    echo $str;
    fwrite (STDERR,$str);
}

function tidy_addr ($str) {
    $str        = ucwords ($str,"'- ");
    while (strpos($str,'  ')!==false) {
        $str    = str_replace ('  ',' ',$str);
    }
    $str        = trim ($str);
    $str        = trim ($str,"'-,");
    return $str;
}

function update ( ) {
    if (!array_key_exists('t',$_GET) || !in_array($_GET['t'],['m','s'])) {
        return '{ "error" : 106 }';
    }
    $oid                = BLOTTO_ORG_ID;
    $type               = $_GET['t'];
    $usr                = $_SESSION['blotto'];
    $fields             = $_POST;
    $dbc                = BLOTTO_CONFIG_DB;
    unset ($fields['update']);
    $zoc                = connect ($dbc);
    $zom                = connect (BLOTTO_MAKE_DB);
    $zo                 = connect ();
    if (!$zo || !$zom) {
        return (object) ['error' => 126];
    }
    if ($type=='s') {
      // sanity check = are the blotto_contact tables in lockstep with each other?
      // do we have to do this select -> update or insert individually for both databases?
        $q0 = "SELECT id FROM `blotto_contact` WHERE `supporter_id` = ".escm($fields['supporter_id'])." AND DATE(`created`) = CURDATE()";
        try {
            $r          = $zom->query ($q0);
            if ($r->num_rows) {
              $row      = $r->fetch_assoc();
              $curc     = $row['id'];
              $q        = "UPDATE `blotto_contact` SET ";
            }
            else {
              $curc     = 0;
              $q        = "INSERT INTO `blotto_contact` SET ";
            }
            $qf         = "";
            foreach ($fields as $f=>$val) {
                $qf    .= "`$f`='".escm($val)."',";
            }
            $qf        .= "`updater`='".escm($usr)."'";
            $q          = $q.$qf;
            if ($curc) {
                $q     .= " WHERE id = ".$curc;
            }
            $zom->query ($q);
            $zo->query ($q);
        }
        catch (\mysqli_sql_exception $e) {
            error_log ('update(): '.$e->getMessage());
            return '{ "error" : 107 }';
        }
        $fieldnames = fields ();
        $q = "
          UPDATE `Supporters`
          SET
            `title`='".esc($fields['title'])."'
           ,`name_first`='".esc($fields['name_first'])."'
           ,`name_last`='".esc($fields['name_last'])."'
           ,`email`='".esc($fields['email'])."'
           ,`mobile`='".esc($fields['mobile'])."'
           ,`telephone`='".esc($fields['telephone'])."'
           ,`address_1`='".esc($fields['address_1'])."'
           ,`address_2`='".esc($fields['address_2'])."'
           ,`address_3`='".esc($fields['address_3'])."'
           ,`town`='".esc($fields['town'])."'
           ,`county`='".esc($fields['county'])."'
           ,`postcode`='".esc($fields['postcode'])."'
           ,`dob`='".esc($fields['dob'])."'
        ";
        foreach ($fieldnames AS $f) {
            $l = $f->legend;
            $q .= "
             ,`$l`='".esc($fields['p'.$f->p_number])."'
            ";
        }
        $q .= "
          WHERE `supporter_id`=".(1*$fields['supporter_id'])."
        ";
        try {
            $update = $zo->query ($q);
        }
        catch (\mysqli_sql_exception $e) {
            error_log ('update(): '.$e->getMessage());
            return '{ "error" : 108 }';
        }
        return '{ "ok" : true }';
    }
    if ($type=='m') { // TODO: this stuff should not happen if the mandate provider is not DD-based
        $crf = esc ($fields['ClientRef']);
        $ncr = esc (clientref_advance($fields['ClientRef']));
        $qs = "
          SELECT
            *
          FROM `blotto_build_mandate`
          WHERE `ClientRef`='$crf'
          ORDER BY `Created` DESC
          LIMIT 0,1
        ";
        try {
            $ms = $zo->query ($qs);
            $m = null;
            if(!($m=$ms->fetch_assoc())) {
                return '{ "error" : 109 }';
            }
        }
        catch (\mysqli_sql_exception $e) {
            error_log ('update(): '.$e->getMessage());
            return '{ "error" : 110 }';
        }
        $ddr = esc ($m['RefOrig']);
        $ndr = 'SOMETHING UNIQUE!';
        $q = "INSERT INTO `blotto_bacs` SET ";
        $keys = [ 'ClientRef','Name','Sortcode','Account','Freq','Amount','StartDate' ];
        foreach ($keys as $k) {
            if (!array_key_exists($k,$_POST)) {
                return '{ "error" : 111 }';
            }
            $q .= "`$k`='".esc($fields[$k],BLOTTO_CONFIG_DB)."',";
        }
        $ch  = intval (chances($fields['Freq'],$fields['Amount']));
        $onm = $m['Name'];
        $osc = $m['Sortcode'];
        $oac = $m['Account'];
        $q .= "`Chances`=$ch,";
        $q .= "`OldDDRef`='$ddr',";
        $q .= "`OldName`='$onm',";
        $q .= "`OldSortcode`='$osc',";
        $q .= "`OldAccount`='$oac',";
        $q .= "`NewDDRef`='$ndr',";
        $q .= "`NewClientRef`='$ncr',";
        $q .= "`org_id`=$oid,";
        $upd = esc ($usr,BLOTTO_CONFIG_DB);
        $q .= "`updater`='$upd';\n";
        try {
            $update = $zoc->query ($q);
        }
        catch (\mysqli_sql_exception $e) {
            error_log ('update(): '.$e->getMessage());
            return '{ "error" : 112 }';
        }
        if (!defined('BLOTTO_EMAIL_BACS_TO')) {
            return '{ "ok" : true }';
        }
        $headers = null;
        if (defined('BLOTTO_EMAIL_FROM')) {
            $headers = "From: ".BLOTTO_EMAIL_FROM."\n";
        }
        mail (
            BLOTTO_EMAIL_BACS_TO,
            BLOTTO_BRAND." BACS change request for ".BLOTTO_ORG_NAME,
            "Record added to `$dbc`.`blotto_bacs` for old client ref ".$fields['ClientRef']."\n",
            $headers
        );
        return '{ "ok" : true }';
    }
}

function valid_date ($date,$format='Y-m-d') {
    try {
        $d = new DateTime ($date);
        $d = $d->format ($format);
        return $d==$date;
    }
    catch (\Exception $e) {
        return false;
    }
}

function version ($dirw=null) {
    if ($dirw===null) {
        $dirw = dirname (__DIR__);
    }
    else {
        $dirw = dirname ($dirw);
    }
    $dirf = dirname (__DIR__);
    if ($dirw!=$dirf) {
        throw new \Exception ("Configuration error (functions.php in $dirf but org.php in $dirw)");
        error_log ("functions.php in $dirf but org.php in $dirw");
        return false;
    }
    preg_match ('<[0-9]+$>',basename($dirf),$matches);
    if (count($matches)) {
        echo 'v'.$matches[0];
    }
}

function weeks ($dow,$date1,$date2=null,$format='Y-m-d') {
    // $date1 and $date2 can be in any order; if $date2 is null then it is today.
    // $dow allows both 0=Sunday and 7=Sunday
    // TODO: maybe - write validate_and_order_dates() - see also months()
    $f              = 'Y-m-d';
    $dow            = $dow % 7;
    if ($date2===null) {
        $date2      = date ($f);
    }
    // Convert and validate
    $ds             = DateTime::createFromFormat ($f,$date1);
    if (!$ds || $ds->format($f)!==$date1) {
        throw new \Exception ('First date is not valid');
        return false;
    }
    $de             = DateTime::createFromFormat ($f,$date2);
    if (!$de || $de->format($f)!==$date2) {
        throw new \Exception ('Second date is not valid');
        return false;
    }
    // Check order and swap if needed.
    if ($ds>$de) {
        $tmp        = $ds;
        $ds         = $de;
        $de         = $tmp;
    }
    $output         = [];
    $start_dow      = $ds->format ('w');
    $diff           = $dow - $start_dow;
    if ($diff) {
        if ($diff<0) {
            $diff  += 7;
        }
        $ds->modify ('+'.$diff.' day');
    }
    while ($ds<$de) {
        $output[$ds->format($f)] = $ds->format ($format);
        $ds->modify ('+7 day');
    }
    return $output;
}

function win_last ( ) {
    $yesterday = day_yesterday()->format('Y-m-d');
    $zo = connect ();
    if (!$zo) {
        return $yesterday;
    }
    $qs = "
      SELECT
        MAX(`draw_closed`) AS `last`
      FROM `Wins`
    ";
    try {
        $w = $zo->query ($qs);
        $w = $w->fetch_assoc ();
        if (!$w) {
            return false;
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($qs."\n".$e->getMessage());
        return $yesterday;
    }
    if ($w['last'] && $w['last']<$yesterday) {
        return $w['last'];
    }
    return $yesterday;
}

function winnings_add ($amounts,$draw_closed,$as) {
    if (!is_array($amounts)) {
        throw new \Exception ('First argument must be an array');
        return false;
    }
    if (!is_array($as)) {
        throw new \Exception ('Third argument must be an array');
        return false;
    }
    foreach ($as as $level=>$amount) {
        if (!$amount) {
            continue;
        }
        if (!array_key_exists($draw_closed,$amounts)) {
            $amounts[$draw_closed] = [];
        }
        if (!array_key_exists($level,$amounts[$draw_closed])) {
            $amounts[$draw_closed][$level] = 0;
        }
        $amounts[$draw_closed][$level] += $amount;
    }
    return $amounts;
}

function winnings_notify ($amounts) {
    if (!defined('BLOTTO_EMAIL_WINS_ON') || !BLOTTO_EMAIL_WINS_ON) {
        return;
    }
    if (!is_array($amounts)) {
        throw new \Exception ('First argument must be an array');
        return false;
    }
    if (!count($amounts)) {
        return;
    }
    notify (BLOTTO_EMAIL_TO,'Winnings report','Winnings: '.print_r($amounts,true));
}

function winnings_nrmatch ($nrmatchprizes,$entries,$matchtickets,$rbe,$verbose=false) {
    if (!is_array($nrmatchprizes)) {
        throw new \Exception ('Number-match prizes were not given');
        return false;
    }
    if (!count($nrmatchprizes)) {
        echo "No number-match prizes\n";
        return [];
    }
    try {
        $zo = connect (BLOTTO_MAKE_DB);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    // Sort prizes by a) ticketgroup b) match length in reverse
    $prizes             = [];
    $firstentry         = reset ($entries);
    $draw_closed        = $firstentry['draw_closed'];
    // Insert results query and collate prizes
    $rdb                = BLOTTO_RESULTS_DB;
    $qr = "
      INSERT IGNORE INTO `$rdb`.`blotto_result`
      (`draw_closed`,`draw_date`,`prize_level`,`number`)
      VALUES
    ";
    $rcount             = 0;
    foreach ($nrmatchprizes as $mp) {
        $matchlen       = substr ($mp['level_method'],0,1);
        $group          = substr ($mp['level_method'],-1);
        $prizes[$group][$matchlen] = $mp;
        $prize_level    = $mp['level'];
        $prize_name     = $mp['name'];
        $number         = $matchtickets[$group];
        if ($verbose) {
            echo "PERFECT = $number\n";
        }
        if (!$mp['results']) {
            // Not a manual result
            $rcount++;
            $qr        .= "('$draw_closed',drawOnOrAfter('$draw_closed'),$prize_level,'$number'),";
        }
    }
    foreach ($prizes as $k => $pa) {
        krsort ($pa); // returns true / false.  Because PHP.
        $prizes[$k]     = $pa;
    }
    // Insert winners query
    $wins                   = [];
    $amounts                = [];
    $qw                     = null;
    if (count($entries)) {
        $qw = "
          INSERT IGNORE INTO `blotto_winner`
          (`entry_id`,`number`,`prize_level`,`prize_starts`,`amount`)
          VALUES
        ";
        foreach ($entries as $e) {
            foreach ($prizes as $group => $prizelist) {
                $winner         = $matchtickets[$group];
                $prizewon       = prize_match ($e,$prizelist,$winner);
                if ($prizewon) {
                    // Bespoke modification of prize amount in BLOTTO_BESPOKE_FUNC
                    if (function_exists('prize_amount')) {
                        prize_amount ($prizewon,$verbose);
                    }
                    // Calculate amount after rollover/cap
                    $amount     = prize_calc ($prizewon,$verbose);
                    if (!array_key_exists($prizewon['level'],$amounts)) {
                        $amounts[$prizewon['level']] = 0;
                    }
                    $amounts[$prizewon['level']] += $amount;
                    if ($verbose) {
                        echo "TICKET = {$e['ticket_number']}\n";
                    }
                    $qw   .= "({$e['id']},'{$e['ticket_number']}',{$prizewon['level']},'{$prizewon['starts']}',$amount),";
                    if ($rbe) {
                        array_push (
                            $wins,
                            array (
                                'entry_id'      => $eid,
                                'number'        => $entry['ticket_number'],
                                'prize_level'   => $p['level'],
                                'prize_starts'  => $p['starts'],
                                'prize_name'    => $p['name'],
                                'amount'        => $amount
                            )
                        );
                    }
                }
            }
        }
    }
    // Run results insert
    if ($rcount>0) {
        try {
            $qr         = substr ($qr,0,-1);
            $zo->query ($qr);
        }
        catch (\mysqli_sql_exception $e) {
            fwrite(STDERR, substr($qr,0,64)." ...\n");
            throw new \Exception ($e->getMessage());
            return false;
        }
    }
    // Run winners insert
    if (count($amounts)) {
        try {
            $qw = substr ($qw,0,-1);
            $zo->query ($qw);
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($e->getMessage());
            return false;
        }
        if ($rbe) {
            winnings_super ($wins,'number-match');
        }
    }
    // Return the total amount won for each level that matched
    return $amounts;
}

function winnings_raffle ($prizes,$entries,$rafflewinners,$rbe=false,$adhoc=false,$verbose=false) {
    if (!is_array($prizes)) {
        throw new \Exception ('Raffle prizes were not given');
        return false;
    }
    if (!count($prizes)) {
        echo "No raffle prizes\n";
        return [];
    }
    try {
        $zo = connect (BLOTTO_MAKE_DB);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    $rdb = BLOTTO_RESULTS_DB;
    $qr = "
      INSERT IGNORE INTO `$rdb`.`blotto_result`
      (`draw_closed`,`draw_date`,`prize_level`,`number`)
      VALUES
    ";
    $qw = "
      INSERT IGNORE INTO `blotto_winner`
      (`entry_id`,`number`,`prize_level`,`prize_starts`,`amount`)
      VALUES
    ";
    $wins               = [];
    $amounts            = [];
    foreach ($prizes as $p) {
        // blotto_entry.id
        $eid            = array_pop ($rafflewinners);
        $entry          = $entries[$eid];
        // Bespoke modification of prize amount in BLOTTO_BESPOKE_FUNC
        if (function_exists('prize_amount')) {
            prize_amount ($p,$verbose);
        }
        // Calculate amount after rollover/cap
        $amount         = prize_calc ($p,$verbose);
        if (!array_key_exists($p['level'],$amounts)) {
            $amounts[$p['level']] = 0;
        }
        $amounts[$p['level']] += $amount;
        $qr            .= "('{$entry['draw_closed']}',drawOnOrAfter('{$entry['draw_closed']}'),{$p['level']},'{$entry['ticket_number']}'),";
        $qw            .= "($eid,'{$entry['ticket_number']}',{$p['level']},'{$p['starts']}',$amount),";
        if ($rbe) {
            array_push (
                $wins,
                array (
                    'entry_id'      => $eid,
                    'number'        => $entry['ticket_number'],
                    'prize_level'   => $p['level'],
                    'prize_starts'  => $p['starts'],
                    'prize_name'    => $p['name'],
                    'amount'        => $amount
                )
            );
        }
    }
    $qr                 = substr ($qr,0,-1);
    $qw                 = substr ($qw,0,-1);
    if (!$adhoc) {
        // Result is not recorded already 
        // This is a normal raffle and not a capped rollover
        try {
            $zo->query ($qr);
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($e->getMessage());
            return false;
        }
    }
    try {
        $zo->query ($qw);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    if ($rbe) {
        // Call bespoke function
        winnings_super ($wins,'raffle');
    }
    return $amounts;
}

function winnings_super ($wins,$type) {
    $super_db = BLOTTO_DB;
    try {
        $zo = connect (BLOTTO_MAKE_DB);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    foreach (dbs() as $org_id=>$db) {
        $super_entry_ids    = [];
        $seids_found        = [];
        $winners            = [];
        foreach ($wins as $w) {
            $seid           = $w['entry_id'];
            $winners[$seid] = $w;
            array_push ($super_entry_ids,$seid);
        }
        // Only the superdraw winners for this database should be inserted
        $qs = "
          SELECT
            `id`
           ,`superdraw_entry_id`
           ,`client_ref`
          FROM `{$db['frontend']}`.`blotto_super_entry`
          WHERE `superdraw_db`='$super_db'
            AND `superdraw_entry_id` IN (".implode (',',$super_entry_ids).")
          ;
        ";
        try {
            $seids          = $zo->query ($qs);
            while ($row=$seids->fetch_assoc()) {
                $seids_found[$row['superdraw_entry_id']] = $row;
            }
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($e->getMessage());
            return false;
        }
        if (!count($seids_found)) {
            fwrite (STDERR,"No $type superdraw winners for {$db['frontend']}\n");
            continue;
        }
        $qi1 = "
          INSERT IGNORE INTO `{$db['make']}`.`blotto_super_winner`
          (`entry_id`,`number`,`client_ref`,`prize_level`,`prize_starts`,`prize_name`,`amount`)
          VALUES
        ";
        $qi2 = "
          INSERT IGNORE INTO `{$db['frontend']}`.`blotto_super_winner`
          (`entry_id`,`number`,`client_ref`,`prize_level`,`prize_starts`,`prize_name`,`amount`)
          VALUES
        ";
        foreach ($seids_found as $super_entry_id=>$entry) {
            $w = $winners[$super_entry_id];
            $n1 = esc ($w['prize_name'],$db['make']);
            $n2 = esc ($w['prize_name'],$db['frontend']);
            $qi1 .= "({$entry['id']},'{$w['number']}','{$entry['client_ref']}',{$w['prize_level']},'{$w['prize_starts']}','$n1',{$w['amount']}),\n";
            $qi2 .= "({$entry['id']},'{$w['number']}','{$entry['client_ref']}',{$w['prize_level']},'{$w['prize_starts']}','$n2',{$w['amount']}),\n";
        }
        try {
            $qi1             = substr ($qi1,0,-2);
            $qi2             = substr ($qi2,0,-2);
            $zo->query ($qi1);
            $zo->query ($qi2);
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($e->getMessage());
            return false;
        }
    }
    return true;
}

function www_auth ($db,&$time,&$err,&$msg) {
    $time               = time ();
    if (!isset($_SESSION)) {
        www_session_start ();
    }
    $zo = connect (BLOTTO_DB,$_POST['un'],$_POST['pw'],true,true);
    if (!$zo) {
        $err            = 'Authentication failed - please try again';
        return false;
    }
    $_SESSION['blotto'] = $_POST['un'];
    $_SESSION['ends']   = $time;
    setcookie ('blotto_end',$_SESSION['ends'],0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_dbn',BLOTTO_DB,0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_key',pwd2cookie($_POST['pw']),0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_usr',$_POST['un'],0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    array_push ($msg,'Welcome, '.$_POST['un'].', to '.BLOTTO_ORG_NAME.' lottery system');
    return true;
}

function www_logout ( ) {
    if (!isset($_SESSION)) {
        www_session_start ();
    }
    if (array_key_exists('blotto',$_SESSION)) {
        unset ($_SESSION['blotto']);
    }
    if (array_key_exists('ends',$_SESSION)) {
        unset ($_SESSION['ends']);
    }
    setcookie ('blotto_usr','',0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_key','',0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_dbn','',0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_end',0,0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    header ('Location: ./');
    exit;
}

function www_session (&$time) {
    if (!isset($_SESSION)) {
        www_session_start ();
    }
    if (!array_key_exists('blotto',$_SESSION)) {
        return false;
    }
    if (!array_key_exists('ends',$_SESSION)) {
        return false;
    }
    $time                = time ();
    if ($_SESSION['ends']<$time) {
        return false;
    }
    $_SESSION['ends']    = $time + 60*BLOTTO_WWW_SESSION_MINUTES;
    setcookie ('blotto_end',$_SESSION['ends'],0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    return true;
}

function www_session_start () {
    // This application is HTTPS only
    if (!$_SERVER['HTTPS']) {
        return false;
    }
    if (strcasecmp($_SERVER['HTTPS'],'on')!=0) {
        return false;
    }
    session_set_cookie_params (0,BLOTTO_WWW_COOKIE_PATH,$_SERVER['HTTP_HOST'],true);
    session_start();
}

function www_winners ($format='Y-m-d') {
    // Provide latest winners for external API requests
    $rdb = BLOTTO_RESULTS_DB;
    $winners = ['date'=>'','dateYMD'=>'','results'=>[],'wins'=>[]];
    $results = [];
    $zo = connect ();
    if (!$zo) {
        return $winners;
    }
    $q = "
      SELECT
        `w`.*
      FROM `Wins` AS `w`
      JOIN (
        SELECT
          MAX(`draw_closed`) AS `dc`
        FROM `Wins`
      ) AS `last`
        ON `last`.`dc`=`w`.`draw_closed`
      ORDER BY `winnings` DESC, `ticket_number`
    ";
    try {
        $ws = connect()->query ($q);
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('www_winners(): '.$e->getMessage());
        return $winners;
    }
    while ($w=$ws->fetch_assoc()) {
        $draw_closed = $w['draw_closed'];
        array_push ($winners['wins'],[$w['ticket_number'],$w['winnings']]);
    }
    if (!count($winners['wins'])) {
        return $winners;
    }
    $dt                 = new DateTime ($draw_closed);
    $dt->add (new DateInterval('P1D'));
    $winners['date']    = $dt->format ($format);
    $prizes             = prizes ($draw_closed);
    $q = "
      SELECT
        `prize_level`
       ,`number`
      FROM `$rdb`.`blotto_result`
      WHERE `draw_closed`='$draw_closed'
      ORDER BY `prize_level`,`number`
    ";
    try {
        $rs = connect()->query ($q);
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('www_winners(): '.$e->getMessage());
        return $winners;
    }
    while ($r=$rs->fetch_assoc()) {
        foreach ($prizes as $p) {
            if ($p['level_method']=='RAFF') {
                continue;
            }
            if ($p['level']!=$r['prize_level']) {
                continue;
            }
            if (array_key_exists($r['number'],$results)) {
                continue;
            }
            $results[$r['number']] = $p;
        }
    }
    foreach ($results as $n=>$r) {
        array_push ($winners['results'],[$r['name'],$n]);
    }
    return $winners;
}


