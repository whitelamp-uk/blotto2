<?php

function bank_decrypt ($key,$data,&$sortcode,&$accountnr) {
    $method = 'AES-256-CBC';
    $data = base64_decode($data);
    $data = json_decode($data);
    // $data also contains $data->mac which is constructed thus:
    // $mac = $this->hash($iv = base64_encode($iv), $value);
    $out = openssl_decrypt ( $data->value, $method, $key, 0, base64_decode($data->iv)); // , OPENSSL_RAW_DATA 
    $out = unserialize ($out);
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

function campaign_monitor ($key,$campaign_id,$to,$data) {
    if (!class_exists('\CS_REST_Transactional_SmartEmail')) {
        throw new \Exception ('Class \CS_REST_Transactional_SmartEmail not found');
        return false;
    }
    $cm         = new \CS_REST_Transactional_SmartEmail (
        $campaign_id,
        ['api_key'=>$key]
    );
    return $cm->send (
        [
            "To"    => $to,
            "Data"  => $data
        ],
        'unchanged'
    );
}

function cfg ( ) {
    global $argv;
    if (!array_key_exists(1,$argv)) {
        fwrite(STDERR, "Usage: ".$argv[0]." path_to_config_file\n");
        exit (100);
    }
    if (!is_readable($argv[1])) {
        fwrite(STDERR, "Config file ".$argv[1]." is not readable\n");
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
        return number_format ($chances*BLOTTO_TICKET_PRICE*434/10000,2,'.','');
    }
    if ($freq=='Quarterly') {
        return number_format ($chances*BLOTTO_TICKET_PRICE*1302/10000,2,'.','');
    }
    if ($freq=='Six Monthly') {
        return number_format ($chances*BLOTTO_TICKET_PRICE*2600/10000,2,'.','');
    }
    if ($freq=='Annually') {
        return number_format ($chances*BLOTTO_TICKET_PRICE*5200/10000,2,'.','');
    }
    return false;
}

function chart ($number,$type) {
    if (!in_array($type,['graph','table','csv'])) {
        return '';
    }
    $error          = [
        'graph'         => "null; // See error log [n]",
        'table'         => "<dialog open>See PHP error log [n]</dialog>",
        'csv'           => "See error log [n]"
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
        return str_replace('[n]',"[chart $number, error 1]",$error);
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
            return str_replace('[n]',"[chart $number, error 2]",$error);
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
        return str_replace('[n]',"[chart $number, error 3]",$error);
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

function collection_startdate ($today,$payday) {
    // Calculating from today, complying with DD rules
    // 1 day to collect created mandate from RSM
    // 1 day for printing and dispatch
    // 2 days in case its a weekend
    // 2 days for 2nd class delivery
    // 10 days cooling-off after delivery
    $dt = new \DateTime ($today);
    $dt->add (new \DateInterval('P16D'));
    if (intval($dt->format('j'))>28) {
        // End of month so add more days
        for ($i=0;$i<3;$i++) {
            $dt->add (new \DateInterval('P1D'));
            if (intval($dt->format('j'))==1) {
                break;
            }
        }
    }
    if ($pd=intval($payday)) {
        // Day of month specified so add more days
        for ($i=0;$i<31;$i++) {
            if (intval($dt->format('j'))==$pd) {
                break;
            }
            $dt->add (new \DateInterval('P1D'));
        }
    }
    return $dt->format ('Y-m-d');
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
        $fpin               = fopen ($file,'r');
        $fpout              = fopen ('php://output','w');
        while ($array=fgetcsv($fpin)) {
            foreach ($array as $k=>$v) {
                if ($complement && preg_match("<^'[0-9]+$>",$v)) {
                    $array[$k] = substr ($v,1);
                }
                if (preg_match('<^[0-9]+$>',$v)) {
                    $array[$k] = "'".$v;
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

function defn ($name,$echo=true) {
    if (!defined($name)) {
        return false;
    }
    if ($echo) {
        echo htmlspecialchars (constant($name));
    }
    return constant($name);
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
    $elz        = array_key_exists('elz',$_GET) && $_GET['elz']>0;
    $file       = $_GET['table'];
    $file      .= '_'.$_GET['from'].'_'.$_GET['to'];
    if ($gp) {
        $file  .= '_by_member';
    }
    elseif (!in_array(strtolower($t),['anls','wins'])) {
        $file  .= '_by_ticket';
    }
    if ($elz) {
        $file  .= '_XL';
    }
    $file      .= '.csv';
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
        return "SQL failure [1]";
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
        if ($t=='Supporters') {
            $gpby   = "GROUP BY `original_client_ref`\n";
        }
        else {
            $gpby   = "GROUP BY `client_ref`\n";
        }
    }
    else {
        $gpby       = "";
    }
    $q              = str_replace ('{{HEADINGS}}',implode(",\n",$headings),$q);
    $q              = str_replace ('{{DATA}}',implode(",\n",$data),$q);
    $q              = str_replace ('{{CONDITION}}',$cond,$q);
    $q              = str_replace ('{{GROUP}}',$gpby,$q);
    $fp             = fopen ($of,'w');
    if (!$fp) {
        return "file open failure";
    }
    try {
        $rows       = $zo->query ($q);
        while ($r=$rows->fetch_assoc()) {
            fputcsv (
                $fp,
                $r,
                BLOTTO_CSV_DELIMITER,
                BLOTTO_CSV_ENCLOSER,
                BLOTTO_CSV_ESCAPER
            );
        }
        fclose ($fp);
    }
    catch (\mysqli_sql_exception $e) {
        fclose ($fp);
        error_log ('download_csv(): '.$e->getMessage());
        return "SQL failure [2]";
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
        throw new \Exception ("Draw could not be found; is '{$draw->closed}' a valid date?\n");
        return false;
    }
    $draw->time             = $d['draw_time'];
    $draw->prizes           = prizes ($draw_closed);
    $draw->insure           = [];
    $draw->manual           = false;
    $draw->results          = [];
    $draw->groups           = [];
    $draw->manual_groups    = [];
    foreach ($draw->prizes as $level=>$p) {
        // Raffles
        if ($p['level_method']=='RAFF') {
            if ($p['results']) {
                 $draw->results['RAFF'] = true;
            }
            continue;
        }
        // Number-matches
        $group              = substr ($p['level_method'],-1);
        $draw->prizes[$level]['group'] = $group;
        if ($p['insure']) {
            array_push ($draw->insure,$level);
        }
        if ($p['results_manual']) {
            // If any prize level is manual
            // and without a bespoke function,
            // both group and draw are manual
            $draw->manual   = $group;
            array_push ($draw->manual_groups,$group);
        }
        if (count($p['results'])) {
             $draw->results[$group] = true;
        }
        if (!array_key_exists($group,$draw->groups)) {
            $draw->groups[$group] = [];
        }
        array_push ($draw->groups[$group],$level);
    }
    // Every prize level in a manual group should be manual
    foreach ($draw->prizes as $level=>$p) {
        if ($p['level_method']=='RAFF') {
            continue;
        }
        if (in_array($p['group'],$draw->manual_groups)) {
            $draw->prizes[$level]['results_manual'] = 1;
        }
    }
    return $draw;
}

function draw_first_asap ($first_collection_date) {
    if (!function_exists('draw_upcoming')) {
        throw new \Exception ("Function draw_upcoming() was not found");
        return false;
    }
    // Money received may be used in a draw closing on
    // the same day unless a delay is required for insurance
    // Use the bespoke function
    $draw_closes    = draw_upcoming ($first_collection_date);
    if (!defined('BLOTTO_INSURE') || !BLOTTO_INSURE || BLOTTO_INSURE_DAYS<1) {
        return $draw_closes;
    }
    // How many days from first collection to insurance deadline?
    $d1             = new \DateTime ($first_collection_date);
    $d2             = new \DateTime ($draw_closes);
    $days           = $d1->diff($d2)->format ('%r%a');
    if ($days>=BLOTTO_INSURE_DAYS) {
        // There is enough time
        return $draw_closes;
    }
    // The first draw for this collection date
    // must be the following one so add a day...
    $d2->add (new \DateInterval('P1D'));
    // ... in order to return the next draw
    // Use the bespoke function
    return draw_upcoming ($d2->format('Y-m-d'));
}

function draw_first_zaffo_model ($first_collection_date,$dow=5) {
    /*
        OLD: Do it on a Friday
            1. Take first_collection_date
            2. Add pay delay
            3. Move on to next Friday (even if first_collection_date is a Friday)
            4. Move on 21 more days
        NEW: Do it on day=4 for BWH
            1. Take first_collection_date
            2. Add pay delay
            3. Move on to next $dow (even if first_collection_date is a $dow)
            4. Move on 21 more days
    */
    $fcd        = new DateTime ($first_collection_date);
    $fcd->add (new DateInterval(BLOTTO_PAY_DELAY));
    // Move on to next Fri
    $days       = (($dow+7)-$fcd->format('w')) % 7;
    if ($days==0) {
        // Even if collection date is a $dow
        $days  += 7;
    }
    // Move on 21 more days
    $days      += 21;
    $fcd->add (new DateInterval('P'.$days.'D'));
    return $fcd->format ('Y-m-d');
}

function draw_report ($draw,$output=true) {
    if ($output) {
        require __DIR__.'/draw.php';
        return;
    }
    ob_start ();
    require __DIR__.'/draw.php';
    $draw_report = ob_get_contents ();
    ob_end_clean ();
    return $draw_report;
}

function draw_report_render ($draw_closed,$output=true) {
    $code                   = strtoupper (BLOTTO_ORG_USER);
    $org                    = org ();
    $qs                     = "SELECT DATE(drawOnOrAfter('$draw_closed')) AS `dt`";
    try {
        $zo                 = connect (BLOTTO_MAKE_DB);
        $date_draw          = $zo->query ($qs);
        $date_draw          = $date_draw->fetch_assoc ();
        $date_draw          = new \DateTime ($date_draw['dt']);
        $date_draw          = $date_draw->format ('Y M d');
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
    $draw                   = new \stdClass ();
    $draw->date             = $date_draw;
    $draw->html_title       = "Draw DRW{$code}-{$draw_closed}";
    $draw->reference        = "DRW{$code}-{$draw_closed}";
    $draw->description      = "Report for draw closing {$draw_closed}";
    $draw->results          = [];
    $draw->wins             = [];
    $draw->winners          = 0;
    $draw->players          = 0;
    $draw->tickets          = 0;
    $draw->total            = 0;
    $draw->entries          = [];
    foreach (prizes($draw_closed) as $p) {
        if ($p['level_method']=='RAFF' || !is_array($p['results'])) {
            continue;
        }
        foreach ($p['results'] as $r) {
            if (!in_array($r,$draw->results)) {
                $draw->results[] = $r;
            }
        }
    }
    try {
        $qs = "
          SELECT
            `winnings`
           ,CONCAT(`title`,' ',`name_first`,' ',`name_last`)
           ,`ticket_number`
          FROM `Wins`
          WHERE `draw_closed`='$draw_closed'
          ;
        ";
        $wins = $zo->query ($qs);
        while($win=$wins->fetch_array(MYSQLI_NUM)) {
            $draw->winners++;
            $draw->total   += $win[0];
            $draw->wins[]   = $win;
        }
        $qs = "
          SELECT
            `draw_closed`
           ,COUNT(`ticket_number`) AS `tickets`
           ,COUNT(DISTINCT `client_ref`) AS `players`
          FROM `blotto_entry`
          WHERE `draw_closed`<='$draw_closed'
          GROUP BY `draw_closed`
          ORDER BY `draw_closed` DESC
          LIMIT 0,2
          ;
        ";
        $entries            = $zo->query ($qs);
        $e                  = $entries->fetch_assoc ();
        $draw->players      = number_format ($e['players']);
        $draw->tickets      = number_format ($e['tickets']);
        $e                  = $entries->fetch_assoc ();
        if ($e) {
            $draw->players_prv  = number_format ($e['players']);
            $draw->tickets_prv  = number_format ($e['tickets']);
        }
        else {
            $draw->players_prv  = null;
            $draw->tickets_prv  = null;
        }
        $qs = "
          SELECT
            `ccc`
           ,`supporters_entered`
           ,`tickets_entered`
          FROM `Draws_Summary`
          WHERE `draw_closed`='$draw_closed'
          ORDER BY `tickets_entered` DESC
          ;
        ";
        $cccs = $zo->query ($qs);
        while($ccc=$cccs->fetch_array(MYSQLI_NUM)) {
            $draw->entries[] = $ccc;
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
    $snippet = draw_report ($draw,false);
    return html ($snippet,$draw->html_title,$output);

}

function draw_report_serve ($file) {
    header ('Cache-Control: no-cache');
    header ('Content-Type: text/html');
    header ('Content-Disposition: attachment; filename="'.$file.'"');
    if (!is_readable(BLOTTO_DIR_DRAW.'/'.$file)) {
        echo "<html><body>Sorry - could not find draw report $file</body></html>";
        return;
    }
    echo file_get_contents (BLOTTO_DIR_DRAW.'/'.$file);
}

function draw_upcoming_dow_last_in_months ($dow,$months,$today=null) {
    // Allow dow to be loose
    $dow            = intval($dow) %7;
    if (!is_array($months)) {
        // Illegal input type
        throw new \Exception ('Second argument, months, must be an array');
        return false;
    }
    // Allow months to be loose
    foreach ($months as $i=>$m) {
        $months[$i] = (intval($m-1)%12) +1;
    }
    // Empty array implies every month
    if (!count($months)) {
        $months     = [1,2,3,4,5,6,7,8,9,10,11,12];
    }
    $days           = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat',];
    $dt             = new DateTime ($today);
    $today          = $dt->format ('Y-m-d');
    $dt->modify ('first day of this month');
    $count          = 0;
    while ($count<=12) {
        if (in_array($dt->format('n'),$months)) {
            $last = new DateTime ($dt->format ('Y-m-d'));
            $last->modify ('last '.$days[$dow].' of this month');
            $last = $last->format ('Y-m-d');
            if ($last>=$today) {
                return $last;
            }
        }
        $dt->add (new DateInterval('P1M'));
        $count++;
    }
    // Sanity
    throw new \Exception ('No date found in 12 months');
    return false;
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
    if (!function_exists('draw_upcoming')) {
        throw new \Exception ("Function draw_upcoming() was not found");
        return false;
    }
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
        // Use the bespoke function
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
        try {
            fwrite (STDERR,debug_backtrace()[2]['function']."(\n");
            fwrite (STDERR,print_r(debug_backtrace()[2]['args'],true));
            fwrite (STDERR,")\n");
        }
        catch (\Exception $e) {
        }
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

function insurance_draw_close ($today=null) {
    // Which draw are we insuring today?
    // This is called by bespoke draw_insuring()
    // Therefore customisation is supported if not envisaged
    if (!function_exists('draw_upcoming')) {
        throw new \Exception ("Function draw_upcoming() was not found");
        return false;
    }
    $insure_days = 0;
    if (defined('BLOTTO_INSURE_DAYS')) {
        $insure_days = intval (BLOTTO_INSURE_DAYS);
    }
    $dt = new \DateTime ($today);
    $dt->add (new \DateInterval('P'.$insure_days.'D'));
    // Use the bespoke function
    return draw_upcoming ($dt->format('Y-m-d'));
}

function invoice ($invoice,$output=true) {
    if ($output) {
        require __DIR__.'/invoice.php';
        return;
    }
    ob_start ();
    require __DIR__.'/invoice.php';
    $invoice = ob_get_contents ();
    ob_end_clean ();
    return $invoice;
}

function invoice_custom ($inv,$output=true) {
    $invoice                = new \stdClass ();
    foreach ($inv as $k=>$v) {
        $invoice->$k        = $v;
    }
    $invoice->html_title    = "Invoice {$invoice->type} {$invoice->raised}";
    $invoice->html_table_id = "invoice-custom";
    $invoice->date          = $invoice->raised;
    $invoice->reference     = strtoupper (
        "CST{$invoice->org_code}-{$invoice->type}-{$invoice->raised}"
    );
    $invoice->items         = [
        [
            $invoice->item_text,
            $invoice->item_quantity,
            $invoice->item_unit_price,
            '',
            $invoice->item_tax_percent / 100
        ]
    ];
    return invoice_render ($invoice,$output);
}

function invoice_game ($draw_closed_date,$output=true) {
    $code                   = strtoupper (BLOTTO_ORG_USER);
    $org                    = org ();
    $qs                     = "SELECT DATE(drawOnOrAfter('$draw_closed_date')) AS `dt`";
    try {
        $zo                 = connect (BLOTTO_MAKE_DB);
        $date_draw          = $zo->query ($qs);
        $date_draw          = $date_draw->fetch_assoc ();
        $date_draw          = $date_draw['dt'];
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
    $invoice                = new \stdClass ();
    $invoice->html_title    = "Invoice LOT{$code}-{$date_draw}";
    $invoice->html_table_id = "invoice-game";
    $invoice->date          = $date_draw;
    $invoice->reference     = "LOT{$code}-{$draw_closed_date}";
    $invoice->address       = $org['invoice_address'];
    $invoice->description   = "Payout for draw closing {$draw_closed_date}";
    $invoice->items         = [];
    $invoice->terms         = $org['invoice_terms_game'];
    if ($invoice->terms) {
        try {
            $qs = "
              SELECT
                COUNT(`id`) AS `tickets`
              FROM `blotto_entry`
              WHERE `draw_closed`='$draw_closed_date'
            ";
            $tickets        = $zo->query ($qs);
            $tickets        = $tickets->fetch_assoc ();
            $tickets        = intval ($tickets['tickets']);
            $qs = "
              SELECT
                DISTINCT `draw_closed` AS `previous`
              FROM `blotto_entry`
              WHERE `draw_closed`<'$draw_closed_date'
              ORDER BY `draw_closed` DESC
              LIMIT 0,1
            ";
            $previous       = $zo->query ($qs);
            $previous       = $previous->fetch_assoc ();
            $previous       = $previous['previous'];
            $qs = "
              SELECT
                COUNT(`ClientRef`) AS `loaded`
               ,SUM(LENGTH(IFNULL(`letter_batch_ref`,''))>0) AS `letters_anl`
              FROM `ANLs`
              WHERE `tickets_issued`<='$draw_closed_date'
                AND `tickets_issued`>'$previous'
            ";
            $loaded         = $zo->query ($qs);
            $loaded         = $loaded->fetch_assoc ();
            $loaded         = $loaded['loaded'];
            $letters_anl    = $loaded['letters_anl'];
            $qs = "
              SELECT
                COUNT(`ticket_number`) AS `letters_win`
              FROM `Wins`
              WHERE `draw_closed`='$draw_closed_date'
                AND LENGTH(IFNULL(`letter_batch_ref`,''))>0
            ";
            $letters_win    = $zo->query ($qs);
            $letters_win    = $letters_win->fetch_assoc ();
            $letters_win    = $letters_win['letters_win'];
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($qs."\n".$e->getMessage());
            return false;
        }
        $invoice->items[] = [
            "Loading fees",
            $loaded,
            loading_fee ($loaded)
        ];
        $invoice->items[] = [
            "Advanced notification letters",
            $letters_anl,
            number_format (BLOTTO_FEE_ANL/100,2,'.','')
        ];
        $invoice->items[] = [
            "Winner letters",
            $letters_win,
            number_format (BLOTTO_FEE_WL/100,2,'.','')
        ];
        $invoice->items[] = [
            "Email services",
            1,
            number_format (BLOTTO_FEE_CM/100,2,'.','')
        ];
        $invoice->items[] = [
            "Administration charge",
            1,
            number_format (BLOTTO_FEE_ADMIN/100,2,'.','')
        ];
        $invoice->items[] = [
            "Ticket management fee",
            $tickets,
            number_format (BLOTTO_FEE_MANAGE/100,2,'.','')
        ];
        if (defined('BLOTTO_INSURE') && BLOTTO_INSURE) {
            $invoice->items[] = [
                "Ticket insurance fee",
                $tickets,
                number_format (BLOTTO_FEE_INSURE/100,2,'.',''),
                "",
                0
            ];
        }
    }
    return invoice_render ($invoice,$output);
}

function invoice_payout ($draw_closed_date,$output=true) {
    $code                   = strtoupper (BLOTTO_ORG_USER);
    $org                    = org ();
    $qs                     = "SELECT DATE(drawOnOrAfter('$draw_closed_date')) AS `dt`";
    try {
        $zo                 = connect (BLOTTO_MAKE_DB);
        $date_draw          = $zo->query ($qs);
        $date_draw          = $date_draw->fetch_assoc ();
        $date_draw          = $date_draw['dt'];
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
    $invoice = new \stdClass ();
    $invoice->html_title    = "Invoice WIN{$code}-{$date_draw}";
    $invoice->html_table_id = "invoice-payout";
    $invoice->date          = $date_draw;
    $invoice->reference     = "WIN{$code}-{$draw_closed_date}";
    $invoice->address       = $org['invoice_address'];
    $invoice->description   = "Payout for draw closing {$draw_closed_date}";
    $invoice->items         = [];
    $invoice->terms         = $org['invoice_terms_payout'];
    if ($invoice->terms) {
        $qs = "
          SELECT
            `Wins`.`prize`
           ,COUNT(`Wins`.`ticket_number`) AS `qty`
           ,`Wins`.`winnings` AS `prize_value`
           ,'' AS `blank`
           ,'0.00' AS `sales_tax`
          FROM `Wins`
          JOIN `blotto_entry` AS `e`
            ON `e`.`draw_closed`=`Wins`.`draw_closed`
           AND `e`.`ticket_number`=`Wins`.`ticket_number`
          JOIN `blotto_winner` AS `w`
            ON `w`.`entry_id`=`e`.`id`
          JOIN `blotto_prize` AS `p`
            ON `p`.`level`=`w`.`prize_level`
           AND `p`.`starts`=`w`.`prize_starts`
          WHERE `Wins`.`draw_closed`='$draw_closed_date'
            AND `Wins`.`superdraw`='N'
            AND `p`.`insure`=0
          GROUP BY `Wins`.`prize`
          ORDER BY `Wins`.`winnings`
          ;
        ";
        try {
            $items = $zo->query ($qs);
            while($item=$items->fetch_array(MYSQLI_NUM)) {
                $invoice->items[] = $item;
            }
        }
        catch (\mysqli_sql_exception $e) {
            throw new \Exception ($qs."\n".$e->getMessage());
            return false;
        }
    }
    return invoice_render ($invoice,$output);
}

function invoice_render ($invoice,$output=true) {
/*
    // Test object
    $invoice = '{
        "html_title" : "Invoice LOTDBH-2021-08-1",
        "html_table_id" : "invoice-game",
        "date" : "2021-08-14",
        "reference" : "LOTDBH-2021-08-14",
        "address" : "Charity XYZ\n1 The Street\nTownsville\nAA1 1AA",
        "description" : "Game costs draw closing 2021-08-13",
        "items" : [
          [ "Loading Fees", 0, 2.50 ],
          [ "ANL Letters", 0, 0.80 ],
          [ "Winners Letters", 6, 0.80 ],
          [ "Email Client", 1, 11.31 ],
          [ "Admin Charges", 1, 45.00 ],
          [ "Management Charge", 4003, 0.07 ],
          [ "Insurance", 4003, 0.07, '', 0 ]
        ],
        "terms" : "Within 5 days"
    }';
    $invoice = json_decode ($invoice);
*/
    if (!count($invoice->items)) {
        // Tells the calling routine that no invoice should be raised
        return false;
    }
    // Calculate rows of data
    $invoice->totals = [ "Totals", "", "", 0, 0, 0 ];
    foreach ($invoice->items as $idx=>$item) {
        $tax_rate = BLOTTO_TAX;
        if (array_key_exists(4,$invoice->items[$idx])) {
            $tax_rate = $invoice->items[$idx][4];
        }
        $qty = intval ($item[1]);
        $unit = number_format ($item[2],2,'.','');
        $subtotal = number_format ($qty*$unit,2,'.','');
        $tax = number_format ($tax_rate*$subtotal,2,'.','');
        $total = number_format ($subtotal+$tax,2,'.','');
        $invoice->totals[3] += $subtotal;
        $invoice->totals[4] += $tax;
        $invoice->totals[5] += $total;
        $invoice->items[$idx] = [
            $invoice->items[$idx][0],
            $qty,
            $unit,
            $subtotal,
            $tax,
            $total
        ];
    }
    $invoice->totals[3] = number_format ($invoice->totals[3],2,'.','');
    $invoice->totals[4] = number_format ($invoice->totals[4],2,'.','');
    $invoice->totals[5] = number_format ($invoice->totals[5],2,'.','');
    $invoice->grand_total = [
        "Total to be paid",
        "",
        "",
        "",
        "",
        BLOTTO_CURRENCY.number_format($invoice->totals[5],2,'.','')
    ];
    // Generate an HTML invoice snippet
    // NB invoice() calls table()
    $snippet = invoice ($invoice,false);
    return html ($snippet,$invoice->html_title,$output);
}

function invoice_serve ($file) {
    header ('Cache-Control: no-cache');
    header ('Content-Type: text/html');
    header ('Content-Disposition: attachment; filename="'.$file.'"');
    if (!is_readable(BLOTTO_DIR_INVOICE.'/'.$file)) {
        echo "<html><body>Sorry - could not find invoice $file</body></html>";
        return;
    }
    echo file_get_contents (BLOTTO_DIR_INVOICE.'/'.$file);
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

function loading_fee ($qty) {
    $defn           = explode (',',BLOTTO_FEE_LOADING);
    $bulk_fee       = number_format (array_pop($defn)/100,2);
    foreach ($defn as $range) {
        $range      = explode (':',$range);
        if ($range[0]>=$qty) {
            return number_format ($range[1]/100,2);
        }
    }
    return $bulk_fee;
}

function mail_attachments ($to,$subject,$message,$files) {
    if (!is_array($files) || !count($files)) {
        throw new \Exception ('No file attachments given');
        return false;
    }
    $attach             = [];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            throw new \Exception ("File '$file' was not found");
            return false;
        }
        if (!is_readable($file)) {
            throw new \Exception ("File '$file' was found but is not readable");
            return false;
        }
        $file_size      = filesize ($file);
        $fp             = fopen ($file,'r');
        $content        = fread ($fp,$file_size);
        fclose ($fp);
        $attach[$file]  = chunk_split (base64_encode($content));
    }
    $uid                = md5 (uniqid(time()));
     // Additional headers
    $hdr                = "From: ".BLOTTO_EMAIL_FROM.PHP_EOL;
    $hdr               .= "MIME-Version: 1.0".PHP_EOL;
    $hdr               .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"".PHP_EOL;
    $hdr               .= "This is a multi-part message in MIME format.".PHP_EOL;
    // Plain text
    $str                = "--".$uid.PHP_EOL;
    $str               .= "Content-type:text/plain; charset=iso-8859-1".PHP_EOL;
    $str               .= "Content-Transfer-Encoding: 7bit".PHP_EOL;
    $str               .= $message.PHP_EOL;
    foreach ($attach as $file=>$content) {
        // Attachment
        $str           .= "--".$uid.PHP_EOL;
        $str           .= "Content-Type: text/csv; name=\"".basename($file)."\"".PHP_EOL;
        $str           .= "Content-Transfer-Encoding: base64".PHP_EOL;
        $str           .= "Content-Disposition: attachment; filename=\"".basename($file)."\"".PHP_EOL.PHP_EOL;
        $str           .= $content.PHP_EOL;
    }
    $str               .= "--".$uid."--";
    mail ($to,$subject,$str,$hdr);
}

function month_end_last ($format='Y-m-d',$date=null) {
    $date = new DateTime ($date);
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

function nonce ($name) {
    nonce_init ();
    if (!array_key_exists($name,$_SESSION['nonce'])) {
        return '';
    }
    if (!array_key_exists($name,$_SESSION['nonce_expires'])) {
        return '';
    }
    if ($_SESSION['nonce_expires'][$name]<time()) {
        unset ($_SESSION['nonce_expires'][$name]);
        unset ($_SESSION['nonce'][$name]);
        return '';
    }
    return $_SESSION['nonce'][$name];
}

function nonce_challenge ($name,$candidate) {
    if ($candidate!=nonce($name)) {
        return false;
    }
    // Change nonce for next contact
    nonce_set ($name);
    // Return the new nonce
    return nonce ($name);
}

function nonce_init ( ) {
    if (!array_key_exists('nonce',$_SESSION)) {
        $_SESSION['nonce'] = [];
    }
    if (!array_key_exists('nonce_expires',$_SESSION)) {
        $_SESSION['nonce_expires'] = [];
    }
}

function nonce_new ($name) {
    if (!nonce($name)) {
        nonce_set ($name);
    }
}

function nonce_set ($name) {
    nonce_init ();
    $_SESSION['nonce_expires'][$name] = time() + BLOTTO_NONCE_MINUTES*60;
    $_SESSION['nonce'][$name] = bin2hex (random_bytes(16));
    return $_SESSION['nonce'][$name];
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
    echo "Notarisation using ".BLOTTO_TSA_URL."\n";
    $cmd        = 'curl -kH "Content-Type: application/timestamp-query" ';
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
    $help       = "# How to verify the file creation time\n";
    $help      .= "# ------------------------------------\n";
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

function org ( ) {
    $c = connect (BLOTTO_CONFIG_DB);
    $org_code = strtoupper (BLOTTO_ORG_USER);
    $q = "
      SELECT
        *
      FROM `blotto_org`
      WHERE `org_code`='$org_code'
    ";
    try {
        $org = $c->query ($q);
        $org = $org->fetch_assoc ();
        // Ticket options
        $org['signup_ticket_cap'] = 0;
        $tickets = [];
        $options = explode (',',$org['signup_ticket_options']);
        foreach ($options as $o) {
            $o = intval ($o);
            if ($o>0) {
                if ($o>$org['signup_ticket_cap']) {
                    $org['signup_ticket_cap'] = $o;
                }
                $tickets[] = $o;
            }
        }
        $org['signup_ticket_options'] = $tickets;
        // Draw options
        $draws = [];
        $options = explode ("\n",$org['signup_draw_options']);
        foreach ($options as $o) {
            $o = trim ($o);
            if (!$o) {
                continue;
            }
            $o = explode (' ',$o);
            if (!count($o)) {
                continue;
            }
            $i = intval (array_shift($o));
            if ($i<=0) {
                continue;
            }
            $o = trim (implode(' ',$o));
            if (!$o) {
                $o = "$i weekly draws";
            }
            $draws[$i] = $o;
        }
        $org['signup_draw_options'] = $draws;
        return $org;
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
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

function prize_calc ($prize) {
    $amount     = $prize['amount_brought_forward'];
    if ($prize['amount_cap'] && $amount>$prize['amount_cap']) {
        return $amount;
    }
    $amount    += $prize['amount'];
    $amount    += $prize['rollover_count'] * $prize['rollover_amount'];
    if ($prize['amount_cap'] && $amount>$prize['amount_cap']) {
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

function prize_payout_max ($prizes) {
    $ppm = 0;
    foreach ($prizes as $p) {
        $amt = prize_calc ($p);
        if ($amt>$ppm) {
            $ppm = $amt;
        }
    }
    return $ppm;
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
        else {
            $p['results'] = [];
        }
        $p['group']             = null;
        if ($p['level_method']!='RAFF') {
            $p['length']        = substr ($p['level_method'],0,1);
            $p['left']          = stripos($p['level_method'],'L') !== false;
            $p['right']         = stripos($p['level_method'],'R') !== false;
            $p['group']         = substr ($p['level_method'],-1);
        }
        // Bespoke modification of prize amount
        if (function_exists('prize_amount')) {
            // Use bespoke function
            prize_amount ($p);
        }
        $prizes[$p['level']]    = $p;
    }
    ksort ($prizes);
    return $prizes;
}

function random_numbers ($min,$max,$num_of_nums,$reuse,$payout_max,&$proof) {
    // $reuse=false means returned numbers must not be repeated
    $min                            = intval ($min);
    $max                            = intval ($max);
    $num_of_nums                    = intval ($num_of_nums);
    $payout_max                     = intval ($payout_max);
    if ($min<0 || $max<0 || $max<=$min) {
        throw new \Exception ("Number range $min-$max is not valid");
        return false;
    }
    if (!$reuse && $num_of_nums>(1+$max-$min)) {
        throw new \Exception ("Number range $min-$max is not big enough without reusing numbers");
        return false;
    }
    if ($payout_max<=0) {
        throw new \Exception ("Maximum payout $payout_max is not valid");
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
/*
// This is the new API which will not be used in the foreseeable future
    $request->params->licenseData   = new \stdClass ();
    $request->params->licenseData->maxPayoutValue = new \stdClass ();
    $request->params->licenseData->maxPayoutValue->currency = 'GBP';
    $request->params->licenseData->maxPayoutValue->amount = $payout_max;
*/
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
    $type               = 's'; // s for supporter or m for mandate
    if (array_key_exists('t',$_GET)) {
        $type           = $_GET['t'];
    }
    if (!in_array($type,['m','s'])) {
        return '{ "error" : 101 }';
    }
    if (array_key_exists('r',$_GET)) {
        // Just get the data for a particular known ClientRef
        return select ($type);
    }
    // Smart search string with one or more search terms
    $string             = '';
    if (array_key_exists('s',$_GET)) {
        $string         = $_GET['s'];
    }
    // Expert means https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode
    $expert             = false;
    if (array_key_exists('e',$_GET) && $_GET['e']>0) {
        $expert         = true;
    }
    // Maximum results to display
    $limit              = BLOTTO_SEARCH_LIMIT;
    if (array_key_exists('l',$_GET) && intval($_GET['l'])>0) {
        $limit          = intval ($_GET['l']);
    }
    // Each term is mandatory
    // Each term is left-matched ("smit" returns smiths, "mith" does not)
    // NB: dashes are a pain - searching for burton-on-trent doesn't work as wanted
    // At least one term must be BLOTTO_SEARCH_LEN_MIN or more characters
    $string             = explode (' ',$string);
    $terms              = [];
    $crefterms          = [];
    $rows               = [];
    $tooshort           = true;
    foreach ($string as $term) {
        $term_alphanum  = preg_replace ('<[^A-z0-9\-]>','',$term);
        if (strlen($term_alphanum)>=BLOTTO_SEARCH_LEN_MIN) {
            $tooshort   = false;
        }
        if (strlen($term_alphanum)) {
            if (strlen($term_alphanum) >= BLOTTO_SEARCH_CREF_MIN) {
                array_push ($crefterms,esc($term_alphanum));
            }
            // If not expert, allow @ sign (but see below on that one!) and maybe others
            if (!$expert) {
                // Add \- to allow dashes through
                $term_alphanum_extended = preg_replace('<[^A-z0-9\-@]>', '', $term);
                if (strpos($term_alphanum_extended,'-') !== false) {
                    $term = '+"'.$term_alphanum_extended.'"'; 
                }
                else {
                    $term = '+'.$term_alphanum_extended.'*'; 
                }
            }
        }
        elseif (!$expert) {
            // So not expert and no alphanumerics
            continue;
        }
        // https://stackoverflow.com/questions/25088183/mysql-fulltext-search-with-symbol-produces-error-syntax-error-unexpected
        $term = str_replace ('@',' +',$term); // 
        if (strlen($term)) {
            array_push ($terms,esc($term));
        }
    }
    if ($tooshort) {
        return '{ "short" : true }';
    }
    try {
        return search_result ($type,$crefterms,implode(', ',$terms),$limit);
    }
    catch (\Exception $e) {
        return $e->getMessage();
    }
}

function search_result ($type,$crefterms,$fulltextsearch,$limit) {
    if (!in_array($type,['m','s'])) {
        throw new \Exception ('{ error : 121 }');
        return false;
    }
    $zo = connect ();
    if (!$zo) {
        throw new \Exception ('{ error : 122 }');
        return false;
    }
    $qc = "
      SELECT
        COUNT(*) AS `rows`
    ";
    $qs = "
      SELECT
        IFNULL(`s`.`current_client_ref`,`m`.`ClientRef`) AS `ClientRef`
       ,CONCAT_WS(
          ' '
         ,`s`.`signed`
         ,CONCAT_WS(' ',`s`.`title`,`s`.`name_first`,`s`.`name_last`)
         ,`s`.`email`
         ,`s`.`mobile`
         ,`s`.`telephone`
         ,CONCAT_WS(' ',`s`.`address_1`,`s`.`address_2`,`s`.`address_3`)
         ,`s`.`town`
         ,`s`.`county`
         ,`s`.`postcode`
         ,`s`.`dob`
       ) AS `Supporter`
       ,CONCAT_WS(
          ' '
         ,`m`.`Status`
         ,`m`.`ClientRef`
         ,`m`.`Updated`
         ,`m`.`Name`
         ,`m`.`Amount`
         ,`m`.`Freq`
         ,CONCAT('***',SUBSTR(`m`.`Sortcode`,-3),'/*****',SUBSTR(`m`.`Account`,-3))
       ) AS `Mandate`
    ";
    if ($type=='s') {
        $qt = "
          FROM `Supporters` AS `s`
          LEFT JOIN `blotto_player` AS `p`
                 ON `p`.`supporter_id`=`s`.`supporter_id`
          LEFT JOIN `blotto_build_mandate` AS `m`
                 ON `m`.`ClientRef`=`p`.`client_ref`
        ";
    }
    else {
        $qt = "
          FROM `blotto_build_mandate` AS `m`
          LEFT JOIN `blotto_player` AS `p`
                 ON `p`.`client_ref`=`m`.`ClientRef`
          LEFT JOIN `Supporters` AS `s`
                 ON `s`.`supporter_id`=`p`.`supporter_id`
        ";
    }
    $qw = "
      WHERE (
            `s`.`supporter_id` IS NOT NULL
        AND MATCH(
              `name_first`
             ,`name_last`
             ,`email`
             ,`mobile`
             ,`telephone`
             ,`address_1`
             ,`address_2`
             ,`address_3`
             ,`town`
             ,`postcode`
             ,`dob`
            ) AGAINST ('$fulltextsearch' IN BOOLEAN MODE)
      )
         OR (
            `m`.`RefNo` IS NOT NULL
        AND MATCH(
              `Name`
             ,`Sortcode`
             ,`Account`
             ,`StartDate`
             ,`LastStartDate`
             ,`Freq`
            ) AGAINST ('$fulltextsearch' IN BOOLEAN MODE)
      )
    ";
    $indexm = "";
    if ($type=='s') {
        foreach ($crefterms as $term) {
          $qw .= "
            OR ( `p`.`supporter_id` IS NOT NULL AND `p`.`client_ref` LIKE '%$term%' )
          ";
        }
        $qg = "
          GROUP BY `s`.`supporter_id`
        ";
    }
    else {
        foreach ($crefterms as $term) {
          $qw .= "
            OR ( `m`.`RefNo` IS NOT NULL AND `m`.`ClientRef` LIKE '%$term%' )
          ";
        }
        $qg = "
          GROUP BY `m`.`ClientRef`
        ";
    }
    $qo = "
      ORDER BY IFNULL(`p`.`client_ref`,`m`.`ClientRef`) DESC
    ";
    $ql = "
      LIMIT 0,$limit
    ";
    if (defined('BLOTTO_LOG_SEARCH_SQL') && BLOTTO_LOG_SEARCH_SQL) {
        error_log ("Search SQL [1] (search_result(), type=$type): $qc $qt $qw $qg $ql");
    }
    try {
        $result = $zo->query ($qc.$qt.$qw.$qg.$ql);
        $rows =$result->fetch_assoc()['rows'];
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('search_result(): $qc $qt $qw $qg $ql');
        error_log ('search_result(): '.$e->getMessage());
        throw new \Exception ('{ error : => 123 }');
        return false;
    }
    if ($rows>$limit) {
        throw new \Exception ("{ count : => $rows }");
        return false;
    }
    if (defined('BLOTTO_LOG_SEARCH_SQL') && BLOTTO_LOG_SEARCH_SQL) {
        error_log ("Search SQL [2] (search_result(), type=$type): $qs $qt $qw $qg $qo $ql");
    }
    $rows = [];
    try {
        $result = $zo->query ($qs.$qt.$qw.$qg.$qo.$ql);
        while ($r=$result->fetch_assoc()) {
            array_push ($rows,$r);
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('search_result(): $qs $qt $qw $qg $qo $ql');
        error_log ('search_result(): '.$e->getMessage());
        throw new \Exception ('{ error : => 124 }');
        return false;
    }
    return json_encode ($rows,JSON_PRETTY_PRINT);
}

function select ($type) {
    $cref = $_GET['r'];
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
        JOIN (
          SELECT
            `supporter_id`
           ,MAX(`created`) AS `created`
          FROM `blotto_contact`
          GROUP BY `supporter_id`
        ) AS `clast`
          ON `clast`.`supporter_id`=`s`.`id`
        JOIN `blotto_contact` AS `c`
          ON `c`.`supporter_id`=`clast`.`supporter_id`
         AND `c`.`created`=`clast`.`created`
        JOIN (
          SELECT
            `supporter_id`
          FROM `blotto_player`
          WHERE `client_ref`='$cref'
             OR `client_ref` LIKE '$match'
          ORDER BY `client_ref` DESC
          LIMIT 0,1
        ) AS `player`
          ON `player`.`supporter_id`=`s`.`id`
      ";
    }
    if (defined('BLOTTO_LOG_SEARCH_SQL') && BLOTTO_LOG_SEARCH_SQL) {
        error_log ("Search SQL [3] (select(), type=$type): $q");
    }
    try {
        $rs = $zo->query ($q);
        while ($r=$rs->fetch_assoc()) {
            array_push ($response->data,(object) $r);
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ('select(): '.$q);
        error_log ('select(): '.$e->getMessage());
        return '{ "error" : 105 }';
    }
    if ($type=='s') {
        $response->fields = fields ();
    }
    return json_encode ($response,JSON_PRETTY_PRINT);
}

function set_once (&$var,$value) {
    if ($var===null) {
        $var = $value;
    }
}

function signup ($org,$s,$ccc,$cref,$first_draw_close) {
    // For use by internal payment APIs which do not
    // provide FLC-standard CSV files for importing
    try {
        $c = connect (BLOTTO_MAKE_DB);
        foreach ($s as $k => $v) {
            $s[$k] = $c->escape_string($v);
        }
        $c->query (
          "
            INSERT INTO `blotto_supporter` SET
              `created`=DATE('{$s['created']}')
             ,`signed`=DATE('{$s['created']}')
             ,`approved`=DATE('{$s['created']}')
             ,`projected_first_draw_close`='$first_draw_close'
             ,`projected_chances`={$s['quantity']}
             ,`canvas_code`='$ccc'
             ,`canvas_agent_ref`='$ccc'
             ,`canvas_ref`='{$s['id']}'
             ,`client_ref`='$cref'
          "
        );
        $sid = $c->insert_id;
        $c->query (
          "
            INSERT INTO `blotto_player` SET
              `started`=DATE('{$s['created']}')
             ,`supporter_id`=$sid
             ,`client_ref`='$cref'
             ,`first_draw_close`='$first_draw_close'
             ,`chances`={$s['quantity']}
          "
        );
        $c->query (
          "
            INSERT INTO `blotto_contact` SET
              `supporter_id`=$sid
             ,`title`='{$s['title']}'
             ,`name_first`='{$s['name_first']}'
             ,`name_last`='{$s['name_last']}'
             ,`email`='{$s['email']}'
             ,`mobile`='{$s['mobile']}'
             ,`telephone`='{$s['telephone']}'
             ,`address_1`='{$s['address_1']}'
             ,`address_2`='{$s['address_2']}'
             ,`address_3`='{$s['address_3']}'
             ,`town`='{$s['town']}'
             ,`county`='{$s['county']}'
             ,`postcode`='{$s['postcode']}'
             ,`dob`='{$s['dob']}'
             ,`p{$org['pref_nr_email']}`='{$s['pref_email']}'
             ,`p{$org['pref_nr_sms']}`='{$s['pref_sms']}'
             ,`p{$org['pref_nr_post']}`='{$s['pref_post']}'
             ,`p{$org['pref_nr_phone']}`='{$s['pref_phone']}'
          "
        );
        return true;
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
}

function sms ($org,$to,$message,&$diagnostic) {
    if (!is_array($org) || !$to || !$message) {
        throw new \Exception ("Invalid parameters ('".gettype($org)."','$to','$message')");
        return false;
    }
    $sms        = new \SMS ();
    return $sms->send ($to,$message,$org['signup_sms_from'],$diagnostic);
}

function stannp_fields_merge (&$array2d,$ref_key,&$refs=[]) {
    foreach ($array2d as $i=>$row) {
        // Player refs `ClientRef`/`client_ref` interchangeable
        if (array_key_exists('ClientRef',$row)) {
            $array2d[$i]['client_ref']  = $row['ClientRef'];
            $array2d[$i]['ref_id']      = $array2d[$i]['client_ref'];
        }
        elseif (array_key_exists('client_ref',$row)) {
            $array2d[$i]['ClientRef']  = $row['client_ref'];
            $array2d[$i]['ref_id']      = $array2d[$i]['ClientRef'];
        }
        else {
            throw new \Exception ("Either `ClientRef` or `client_ref` is compulsory");
            return false;
        }
        $refs[]                         = $row[$ref_key];
        // Stannp address window (ie compulsory fields)
        //     https://dash.stannp.com/designer/mailpiece/A4
        //     Select "A blank canvas"
        $array2d[$i]['full_name']       = $row['title'].' '.$row['name_first'].' '.$row['name_last'];
        $array2d[$i]['job_title']       = '';
        $array2d[$i]['company']         = '';
        $array2d[$i]['address1']        = $row['address_1'];
        $array2d[$i]['address2']        = $row['address_2'];
        $array2d[$i]['address3']        = $row['address_3'];
        $array2d[$i]['city']            = $row['town'];
        // Country is not given in the blank mailpiece draft.
        // Which is odd - so here it is anyway just in case
        $array2d[$i]['country']         = BLOTTO_STANNP_COUNTRY;
        // `postcode` has the same name in blottoland so needs no transformation
        // Remove undesirable fields (consider GDPR for example)
        $undesirable = explode (',',BLOTTO_STANNP_RM_FIELDS);
        foreach ($undesirable as $u) {
            if (array_key_exists($u,$row)) {
                unset ($array2d[$i][$u]);
            }
        }
    }
    return true;
}

function stannp_mail ($recipients,$name,$template_id,$ref_key,&$refs) {
    if (!defined('BLOTTO_STANNP') || !BLOTTO_STANNP) {
        // API is not active
        return ['recipients'=>0];
    }
    $prefix = BLOTTO_STANNP_PREFIX.'-'.gethostname().'-'.$name.'-';
    $name = $prefix.date('Y-m-d--H:i:s');
    // Transform arrays by reference (arguments above)
    stannp_fields_merge ($recipients,$ref_key,$refs);
    // Do it
    $stannp = new \Whitelamp\Stannp ();
    // Return value is just a summary
    return $stannp->campaign_create ($name,$template_id,$recipients);
}

function stannp_mail_anls ( ) {
    $earliest = BLOTTO_STANNP_FROM_ANL;
    $q = "
      SELECT
        `a`.*
      FROM `ANLs` AS `a`
      JOIN `blotto_player` AS `p`
        ON (`p`.`letter_batch_ref` IS NULL OR `p`.`letter_batch_ref`='')
       AND `p`.`client_ref`=`a`.`ClientRef`
      WHERE `a`.`tickets_issued`>='$earliest'
      ORDER BY `a`.`tickets_issued`,`a`.`ClientRef`
    ";
    echo $q;
    $recipients = [];
    $c = connect (BLOTTO_MAKE_DB);
    try {
        $rows = $c->query ($q);
        while ($r=$rows->fetch_assoc()) {
            $recipients[] = $r;
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    if (!count($recipients)) {
        return ['recipients'=>0];
    }
    $batch = stannp_mail ($recipients, 'ANLs', BLOTTO_STANNP_TPL_ANL,'ClientRef',$refs);
    if (!$batch['recipients']) {
        return $batch;
    }
    $name = $c->escape_string ($batch['name']);
    $q = "
      UPDATE `blotto_player` AS `p_in`
      JOIN `ANLs` AS `p_out`
        ON `p_out`.`ClientRef`=`p_in`.`client_ref`
      SET
        `p_in`.`letter_batch_ref`='$name'
       ,`p_out`.`letter_batch_ref`='$name'
      WHERE `p_in`.`client_ref` IN (
        '".implode("','",$refs)."'
      );
    ";
    echo $q;
    try {
        $c->query ($q);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception (
            'Stannp batch was sent successfully but an SQL exception followed it: '.$e->getMessage()."\n".print_r($batch,true).$q
        );
        return false;
    }
    return $batch;
}

function stannp_mail_wins ( ) {
    $earliest = BLOTTO_STANNP_FROM_WIN;
    $q = "
      SELECT
        `w`.*
      FROM `Wins` AS `w`
      JOIN `blotto_entry` AS `e`
        ON `e`.`draw_closed`=`w`.`draw_closed`
       AND `e`.`ticket_number`=`w`.`ticket_number`
      JOIN `blotto_winner` AS `bw`
        ON (`bw`.`letter_batch_ref` IS NULL OR `bw`.`letter_batch_ref`='')
       AND `bw`.`entry_id`=`e`.`id`
      WHERE `w`.`draw_closed`>='$earliest'
      ORDER BY `w`.`draw_closed`,`w`.`winnings`,`ticket_number`
    ";
    echo $q;
    $recipients = [];
    $c = connect (BLOTTO_MAKE_DB);
    try {
        $rows = $c->query ($q);
        while ($r=$rows->fetch_assoc()) {
            $recipients[] = $r;
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    if (!count($recipients)) {
        return ['recipients'=>0];
    }
    $batch = stannp_mail ($recipients, 'Wins', BLOTTO_STANNP_TPL_WIN,'entry_id',$refs);
    if (!$batch['recipients']) {
        return $batch;
    }
    $name = $c->escape_string ($batch['name']);
    $q = "
      UPDATE `blotto_winner` AS `w_in`
      JOIN `WinsAdmin` AS `w_out_1`
        ON `w_out_1`.`entry_id`=`w_in`.`entry_id`
      JOIN `Wins` AS `w_out_2`
        ON `w_out_2`.`entry_id`=`w_in`.`entry_id`
      SET
        `w_in`.`letter_batch_ref`='$name'
       ,`w_out_1`.`letter_batch_ref`='$name'
       ,`w_out_2`.`letter_batch_ref`='$name'
      WHERE `w_in`.`entry_id` IN (
        ".implode(",",$refs)."
      );
    ";
    echo $q;
    try {
        $c->query ($q);
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception (
            'Stannp batch was sent successfully but an SQL exception followed it: '.$e->getMessage()."\n".print_r($batch,true).$q
        );
        return false;
    }
    return $batch;
}

function stannp_status ($batch_names) {
    $refs = [];
    if (!defined('BLOTTO_STANNP') || !BLOTTO_STANNP) {
        // API is not active
        return $refs;
    }
    $stannp = new \Whitelamp\Stannp ();
    foreach ($batch_names as $campaign_name) {
        $refs[$campaign_name] = [];
        $recipients = $stannp->campaign ($campaign_name) ['recipient_list'];
        if (is_array($recipients)) {
            foreach ($recipients as $r) {
                if (!array_key_exists($r['mailpiece_status'],$refs[$campaign_name])) {
                    $refs[$campaign_name][$r['mailpiece_status']] = [];
                }
                $refs[$campaign_name][$r['mailpiece_status']][] = $r['ref_id'];
            }
        }
        else {
            fwrite (STDERR,"WARNING: campaign $campaign_name was not found\n");
        }
    }
    return $refs;
}

function stannp_status_anls ($live=false) {
    $earliest = BLOTTO_STANNP_FROM_ANL;
    $batches = [];
    $c = connect (BLOTTO_MAKE_DB);
    if ($live) {
        $c_live = connect (BLOTTO_DB);
    }
    $q = "
      SELECT
        DISTINCT `p`.`letter_batch_ref` AS `batch`
      FROM `blotto_player` AS `p`
      JOIN `ANLs` AS `a`
        ON `p`.`client_ref`=`a`.`ClientRef`
       AND `a`.`tickets_issued`>='$earliest'
      WHERE `p`.`letter_batch_ref` IS NOT NULL
        AND `p`.`letter_batch_ref`!=''
        AND (`p`.`letter_status`!='delivered' OR `p`.`letter_status` IS NULL OR `p`.`letter_status`='')
      ORDER BY `batch`
    ";
//    echo $q;
    try {
        $rows = $c->query ($q);
        while ($row=$rows->fetch_assoc()) {
            $batches[] = $row['batch'];
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    if (!count($batches)) {
        return;
    }
    $statuses = stannp_status ($batches);
    if (count($statuses) && $live){
        $c_live = connect (BLOTTO_DB);
    }
    foreach ($statuses as $batch=>$refs) {
        foreach ($refs as $status=>$crefs) {
            $status = $c->escape_string ($status);
            foreach ($crefs as $i=>$ref) {
                $crefs[$i] = $c->escape_string ($ref);
            }
            $q = "
              UPDATE `blotto_player` AS `p_in`
              JOIN `ANLs` AS `p_out`
                ON `p_out`.`ClientRef`=`p_in`.`client_ref`
              SET
                `p_in`.`letter_status`='$status'
               ,`p_out`.`letter_status`='$status'
              WHERE `p_in`.`client_ref` IN (
                '".implode("','",$crefs)."'
              );
            ";
            echo $q;
            try {
                $c->query ($q);
                if ($live) {
                    // Make the data web accessible right now
                    $c_live->query ($q);
                }
            }
            catch (\mysqli_sql_exception $e) {
                throw new \Exception ($e->getMessage()."\n");
                return false;
            }
        }
    }
    return true;
}

function stannp_status_wins ($live=false) {
    $earliest = BLOTTO_STANNP_FROM_WIN;
    $batches = [];
    $c = connect (BLOTTO_MAKE_DB);
    // Get winners
    $q = "
      SELECT
        DISTINCT `w`.`letter_batch_ref` AS `batch`
      FROM `blotto_winner` AS `w`
      JOIN `blotto_entry` AS `e`
        ON `e`.`id`=`w`.`entry_id`
       AND `e`.`draw_closed`>='$earliest'
      WHERE `w`.`letter_batch_ref` IS NOT NULL
        AND `w`.`letter_batch_ref`!=''
        AND (`w`.`letter_status`!='delivered' OR `w`.`letter_status` IS NULL OR `w`.`letter_status`='')
      ORDER BY `batch`
      ;
    ";
    echo $q;
    try {
        $rows = $c->query ($q);
        while ($row=$rows->fetch_assoc()) {
            $batches[] = $row['batch'];
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    if (!count($batches)) {
        return;
    }
    // Get statuses
    $statuses = stannp_status ($batches);
    if (count($statuses) && $live){
        $c_live = connect (BLOTTO_DB);
    }
    foreach ($statuses as $batch=>$refs) {
        foreach ($refs as $status=>$crefs) {
            $status = $c->escape_string ($status);
            foreach ($crefs as $i=>$ref) {
                $crefs[$i] = $c->escape_string ($ref);
            }
            $q = "
              UPDATE `blotto_winner` AS `w_in`
              JOIN `WinsAdmin` AS `w_out_1`
                ON `w_out_1`.`entry_id`=`w_in`.`entry_id`
              JOIN `Wins` AS `w_out_2`
                ON `w_out_2`.`entry_id`=`w_in`.`entry_id`
              SET
                `w_in`.`letter_status`='$status'
               ,`w_out_1`.`letter_status`='$status'
               ,`w_out_2`.`letter_status`='$status'
              WHERE `w_out_1`.`letter_batch_ref`='$batch'
                AND `w_out_1`.`client_ref` IN (
                '".implode("','",$crefs)."'
              )
              ;
            ";
            echo $q;
            try {
                $c->query ($q);
                if ($live) {
                    // Make the data web accessible right now
                    $c_live->query ($q);
                }
            }
            catch (\mysqli_sql_exception $e) {
                throw new \Exception ($e->getMessage()."\n");
                return false;
            }
        }
    }
    return true;
}

function statement ($stmt,$output=true) {
    if ($output) {
        require __DIR__.'/statement.php';
        return;
    }
    ob_start ();
    require __DIR__.'/statement.php';
    $statement = ob_get_contents ();
    ob_end_clean ();
    return $statement;
}

function statement_render ($day_first,$day_last,$description,$output=true) {
    $c                  = BLOTTO_CURRENCY;
    $n                  = '×';
    $pennies            = BLOTTO_TICKET_PRICE;
    $code               = strtoupper (BLOTTO_ORG_USER);
    $org                = org ();
    $qs = "
      SELECT
        `game_was`.`plays` AS `plays_before`
       ,`game_is`.`plays` AS `plays_during`
       ,`game_is`.`draw_closed_first`
       ,`game_is`.`draw_closed_last`
       ,`game_is`.`draws`
       ,`game_is`.`paid_out`
       ,`game_is`.`winners`
       ,SUM(IFNULL(`game_is`.`winners_posted`,0)) AS `winners_posted`
       ,`supporter`.`starting_balances`
       ,IFNULL(`pre`.`collected`,0) AS `collections_before`
       ,IFNULL(`post`.`collected`,0) AS `collections_during`
       ,`anl`.`quantity` AS `anls`
       ,SUM(IFNULL(`anl`.`posted`,0)) AS `anls_posted`
      FROM (
        SELECT
          COUNT(`id`) AS `plays`
        FROM `blotto_entry`
        WHERE `draw_closed`<'$day_first'
      ) AS `game_was`
      JOIN (
        SELECT
          MIN(`e`.`draw_closed`) AS `draw_closed_first`
         ,MAX(`e`.`draw_closed`) AS `draw_closed_last`
         ,COUNT(DISTINCT `e`.`draw_closed`) AS `draws`
         ,COUNT(`e`.`id`) AS `plays`
         ,SUM(`w`.`winnings`) AS `paid_out`
         ,SUM(`w`.`winners`) AS `winners`
         ,SUM(`w`.`posted`) AS `winners_posted`
        FROM `blotto_entry` AS `e`
        LEFT JOIN (
          SELECT
            `entry_id`
           ,SUM(`amount`) AS `winnings`
           ,COUNT(`id`) AS `winners`
           ,SUM(LENGTH(IFNULL(`letter_batch_ref`,''))>0) AS `posted`
          FROM `blotto_winner`
          GROUP BY `entry_id`
        ) AS `w`
          ON `w`.`entry_id`=`e`.`id`
        WHERE `e`.`draw_closed`>='$day_first'
          AND `e`.`draw_closed`<='$day_last'
      ) AS `game_is`
        ON 1
      LEFT JOIN (
        SELECT
          SUM(`p`.`opening_balance`) AS `starting_balances`
        FROM `blotto_player` AS `p`
        JOIN `blotto_supporter` AS `s`
          ON `s`.`id`=`p`.`supporter_id`
         AND `s`.`client_ref`=`p`.`client_ref`
        WHERE `p`.`created`<='$day_last'
      ) AS `supporter`
        ON 1
      LEFT JOIN (
        SELECT
          SUM(`PaidAmount`) AS `collected`
        FROM `blotto_build_collection`
        WHERE `DateDue`<'$day_first'
      ) AS `pre`
        ON 1
      LEFT JOIN (
        SELECT
          SUM(`PaidAmount`) AS `collected`
        FROM `blotto_build_collection`
        WHERE `DateDue`>='$day_first'
          AND `DateDue`<='$day_last'
      ) AS `post`
        ON 1
      LEFT JOIN (
        SELECT
          COUNT(*) AS `quantity`
         ,SUM(LENGTH(IFNULL(`letter_batch_ref`,''))>0) AS `posted`
        FROM `ANLs`
        WHERE `tickets_issued`>='$day_first'
          AND `tickets_issued`<='$day_last'
      ) AS `anl`
        ON 1
      ;
    ";
    try {
        $zo             = connect (BLOTTO_MAKE_DB);
        $stats          = $zo->query ($qs);
        $stats          = $stats->fetch_assoc ();
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($qs."\n".$e->getMessage());
        return false;
    }
    $reconcile          = 0;
    $return             = 0;
    $played             = $stats['plays_during'] * $pennies/100;
    $return            += $played;
    $return            -= $stats['paid_out'];
    $expend             = 0;
    $expend_loading     = loading_fee($stats['anls']) * $stats['anls'];
    $expend            += $expend_loading;
    $expend_anls        = BLOTTO_FEE_ANL/100 * $stats['anls_posted'];
    $expend            += $expend_anls;
    $expend_winlets     = BLOTTO_FEE_WL/100 * $stats['winners_posted'];
    $expend            += $expend_winlets;
    $expend_email       = BLOTTO_FEE_CM/100 * $stats['draws'];
    $expend            += $expend_email;
    $expend_admin       = BLOTTO_FEE_ADMIN/100 * $stats['draws'];
    $expend            += $expend_admin;
    $expend_tickets     = BLOTTO_FEE_MANAGE/100 * $stats['plays_during'];
    $expend            += $expend_tickets;
    $expend_insure      = 0;
    if (defined('BLOTTO_INSURE') && BLOTTO_INSURE) {
        $expend_insure  = BLOTTO_FEE_INSURE/100 * $stats['plays_during'];
    }
    $expend            += $expend_insure;
    $return            -= $expend;
    $opening            = $stats['starting_balances'] + $stats['collections_before'];
    $opening           -= $stats['plays_before'] * $pennies/100;
    $closing            = $opening + $stats['collections_during'] - $stats['plays_during']*$pennies/100;
    $reconcile         += $opening;
    $reconcile         += $stats['collections_during'];
    $reconcile         -= $played;
    $reconcile         -= $closing;
    $from               = new \DateTime ($day_first);
    $to                 = new \DateTime ($day_last);
    $stmt               = new \stdClass ();
    $stmt->from         = $from->format ('Y M d');
    $stmt->to           = $to->format ('Y M d');
    $stmt->html_title   = "Statement {$stmt->from} through {$stmt->to}";
    $stmt->description  = str_replace ('{{d}}',$stmt->to,$description);
    $stmt->description  = str_replace ('{{o}}',BLOTTO_ORG_USER,$stmt->description);
    $stmt->description  = str_replace ('{{on}}',BLOTTO_ORG_NAME,$stmt->description);
    $stmt->rows         = [];
    $stmt->rows[]       = [ "", "", "", "Summary" ];
    $stmt->rows[]       = [ "", $stats['draw_closed_first'], "", "First draw closed in period" ];
    $stmt->rows[]       = [ "", $stats['draw_closed_last'], "", "Last draw closed in period" ];
    $stmt->rows[]       = [ $n, $stats['draws'], "", "Draws closed in this period" ];
    $stmt->rows[]       = [ $c, number_format($pennies/100,2,'.',''), "", "Charge per play" ];
    $stmt->rows[]       = [ $n, $stats['plays_during'], "", "Plays in this period" ];
    $stmt->rows[]       = [ "", "", "", "Reconciliation" ];
    $stmt->rows[]       = [ $c, number_format($opening,2,'.',''), "", "+ player opening balances" ];
    $stmt->rows[]       = [ $c, number_format($stats['collections_during'],2,'.',''), "", "+ collected this period" ];
    $stmt->rows[]       = [ $c, number_format(0-$played,2,'.',''), "", "- played this period" ];
    $stmt->rows[]       = [ $c, number_format(0-$closing,2,'.',''), "", "- player closing balances" ];
    $stmt->rows[]       = [ $c, number_format($reconcile,2,'.',''), "", "≡ to be reconciled" ];
    $stmt->rows[]       = [ "", "", "", "Revenue & expenditure" ];
    $stmt->rows[]       = [ $c, number_format($played,2,'.',''), "", "+ revenue from plays" ];
    $stmt->rows[]       = [ $c, number_format(0-$stats['paid_out'],2,'.',''), "", "- winnings paid out" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_loading,2,'.',''), "", "- loading fees" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_anls,2,'.',''), "", "- advanced notification letters" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_winlets,2,'.',''), "", "- winner letters" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_email,2,'.',''), "", "- email services" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_admin,2,'.',''), "", "- administration charges" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_tickets,2,'.',''), "", "- ticket management fees" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend_insure,2,'.',''), "", "- draw insurance costs" ];
    $stmt->rows[]       = [ $c, number_format(0-$expend,2,'.',''), "", "- total expenditure" ];
    $stmt->rows[]       = [ $c, number_format($return,2,'.',''), "", "≡ return generated" ];
    $snippet            = statement ($stmt,false);
    return html ($snippet,$stmt->html_title,$output);
}

function statement_serve ($file) {
    header ('Cache-Control: no-cache');
    header ('Content-Type: text/html');
    header ('Content-Disposition: attachment; filename="'.$file.'"');
    if (!is_readable(BLOTTO_DIR_STATEMENT.'/'.$file)) {
        echo "<html><body>Sorry - could not find statement $file</body></html>";
        return;
    }
    echo file_get_contents (BLOTTO_DIR_STATEMENT.'/'.$file);
}

function table ($id,$class,$caption,$headings,$data,$output=true,$footings=false,$classes=false) {
    // TODO: these inputs are now a mess and should become an object, $table
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

function territory_permitted ($postcode,&$areas=null) {
    // BLOTTO_TERRITORIES_CSV can be:
    // UK [everywhere is default],
    // GB, BT, JE, GY, IM
    $areas = BLOTTO_TERRITORIES_CSV;
    if (!$areas) {
        return true;
    }
    $areas = explode (',',$areas);
    if (in_array('UK',$areas)) {
        return true;
    }
    $gb = true;
    foreach (['BT','GY','IM','JE'] as $area) {
        if (strpos($postcode,$area)===0) {
            if (in_array($area,$areas)) {
                return true;
            }
            // Specific postcode so not GB
            $gb = false;
        }
    }
    if (in_array('GB',$areas) && $gb) {
        return true;
    }
    return false;
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

function tickets ($provider_code,$refno,$cref,$qty) {
    $org_id = BLOTTO_ORG_ID;
    $refno = $org_id.$refno; // quirk originally created by RSM with non-numeric DDRefNo values
    $tickets = [];
    try {
        $zo = connect (BLOTTO_TICKET_DB);
        for ($i=0;$i<$qty;$i++) {
            while (1) {
                $new = mt_rand (intval(BLOTTO_TICKET_MIN),intval(BLOTTO_TICKET_MAX));
                $pad_length = strlen(BLOTTO_TICKET_MAX);
                $new = str_pad ($new,$pad_length,'0',STR_PAD_LEFT);
                $qi = "
                  INSERT INTO `blotto_ticket` SET
                    `number`='$new'
                   ,`issue_date`=CURDATE()
                   ,`org_id`=$org_id
                   ,`mandate_provider`='$provider_code'
                   ,`dd_ref_no`=$refno
                   ,`client_ref`='$cref'
                  ON DUPLICATE KEY UPDATE `number`=`number`
                  ;
                ";
                $zo->query ($qi);
                if ($zo->affected_rows > 0) {  // if new number inserted
                    array_push ($tickets,$new);
                    break;
                }
            }
        }
    }
    catch (\mysqli_sql_exception $e) {
        throw new \Exception ($e->getMessage());
        return false;
    }
    return $tickets;
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
        // Use the bespoke function chances()
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
    ksort ($amounts);
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
        www_auth_log (false);
        $err            = 'Authentication failed - please try again';
        return false;
    }
    $_SESSION['blotto'] = $_POST['un'];
    $_SESSION['ends']   = $time;
    www_auth_log (true);
    setcookie ('blotto_end',$_SESSION['ends'],0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_dbn',BLOTTO_DB,0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_key',pwd2cookie($_POST['pw']),0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    setcookie ('blotto_usr',$_POST['un'],0,BLOTTO_WWW_COOKIE_PATH,'',is_https()*1);
    array_push ($msg,'Welcome, '.$_POST['un'].', to '.BLOTTO_ORG_NAME.' lottery system');
    return true;
}

function www_auth_log ($ok) {
    try {
        $zo = connect (BLOTTO_CONFIG_DB);
        $r = $zo->escape_string($_SERVER['REMOTE_ADDR']);
        $h = $zo->escape_string(gethostname());
        $hh = $zo->escape_string($_SERVER['HTTP_HOST']);
        $u = $zo->escape_string($_POST['un']);
        if ($ok) {
            $s = "OK";
        }
        else {
            $s = "FAIL";
        }
        $qi = "
          INSERT INTO `blotto_log`
          SET
            `remote_addr`='$r'
           ,`hostname`='$h'
           ,`http_host`='$hh'
           ,`user`='$u'
           ,`type`='AUTH'
           ,`status`='$s'
        ";
        $zo->query ($qi);
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($e->getMessage());
        return;
    }
}

function www_get_address ( ) {
    if (ctype_digit($_POST['address_1'][0])) {
        $firstline          = explode(' ', $_POST['address_1'], 2);
        $house_number       = $firstline[0];
        $address_1          = $firstline[1];
        $address_2          = $_POST['address_2'];
        if ($_POST['address_3']) {
            $address_2     .= ', '.$_POST['address_3'];
        }
        $house_name         = '';
    }
    else {
        $house_name         = $_POST['address_1'];
        $address_1          = $_POST['address_2'];
        $address_2          = $_POST['address_3'];
        $house_number       = '';
    }
    $address_obj                = new \stdClass ();
    $address_obj->city          = $_POST['town'];
    $address_obj->county        = $_POST['county'];
    $address_obj->country       = 'GB';
    $address_obj->postcode      = $_POST['postcode'];
    $address_obj->address_1     = $address_1;
    $address_obj->address_2     = $address_2;
    $address_obj->house_name    = $house_name;
    $address_obj->house_number  = $house_number;
    $address = json_encode ($address_obj);
    return $address;
}

function www_is_url ($str) {
    return preg_match ('<^https?://>',$str);
}

function www_letter_status_refresh ( ) {
    $log = "";
    ob_start ();
    try {
        stannp_status_anls (true);
        stannp_status_wins (true);
    }
    catch (\Exception $e) {
        $log = $e->getMessage()."\n";
    }
    $log = ob_get_contents().$log;
    ob_end_clean ();
    // error_log ($log);
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

function www_pay_apis ( ) {
    $constants = get_defined_constants (true);
    $apis = [];
    foreach ($constants['user'] as $name => $file) {
        if (!preg_match('<^BLOTTO_PAY_API_([A-Z]+)$>',$name,$matches)) {
            // Not an API class file
            continue;
        }
        if (!defined($name.'_BUY') || !constant($name.'_BUY')) {
            // Not to be integrated
            continue;
        }
        if (!defined($name.'_CLASS') || !($class=constant($name.'_CLASS'))) {
            // Class name not found
            continue;
        }
        if (!defined($matches[1].'_CODE') || !($code=constant($matches[1].'_CODE'))) {
            // Code not found
            continue;
        }
        $apis[$code] = new \stdClass ();
        $apis[$code]->name = ucwords (strtolower($matches[1]));
        $apis[$code]->file = $file;
        $apis[$code]->class = $class;
    }
    return $apis;
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

function www_signup_dates ($org,&$e) {
    $e = false;
    $now = new \DateTime ();
    $dates = [];
    if (array_key_exists('d',$_GET)) {
        $c = connect ();
        foreach (explode(',',$_GET['d']) AS $d) {
            try {
                $d = new \DateTime (trim($d));
            }
            catch (\Exception $e) {
                $e = "'$d' is not a valid date to pass";
                return false;
            }
            $draw_closed = $d->format ('Y-m-d');
            if ($draw_closed>=BLOTTO_DRAW_CLOSE_1) {
                try {
/*
                    $rs = $c->query ("SELECT DATE(drawOnOrAfter('$draw_closed')) AS `draw_date`;");
                    $date = $rs->fetch_assoc()['draw_date'];
                    $end = new \DateTime ("$date 00:00:00");
                    $end->sub (new \DateInterval('PT'.$org['signup_close_advance_hours'].'H'));
                    if ($end>$now) {
                        $dates[$draw_closed] = new \DateTime ($date);
                    }
*/
// I think that was all wrong... instead something like:
                    // A draw is closed exactly one day after the day begins
                    $closed = new \DateTime ($draw_closed.' 00:00:00');
                    $closed->add (new \DateInterval('P1D'));
                    if (defined('BLOTTO_INSURE') && BLOTTO_INSURE && BLOTTO_INSURE_DAYS>0) {
                        // The www promotion for a draw date needs to close BLOTTO_INSURE_DAYS earlier than DD
                        $closed->sub (new \DateInterval('P'.BLOTTO_INSURE_DAYS.'D'));
                    }
                    // Promoters sometimes prefer closing at 5pm rather than midnight (7 hours)
                    // Preferred approach is to set this to zero hours so that terms and conditions
                    // for DD and online players are harmonised
                    $closed->sub (new \DateInterval('PT'.$org['signup_close_advance_hours'].'H'));
                    // Only if the time now is before the calculated cut-off can the date be available
                    if ($now<$closed) {
                        $rs = $c->query ("SELECT DATE(drawOnOrAfter('$draw_closed')) AS `draw_date`;");
                        $dates[$draw_closed] = new \DateTime ($rs->fetch_assoc()['draw_date']);
                    }
                }
                catch (\mysqli_sql_exception $e) {
                    throw new \Exception ($e->getMessage());
                    return false;
                }
            }
        }
        if (!count($dates)) {
            // At least one date passed but no dates are in scope
            throw new \Exception ("No dates are in scope");
            return false;
        }
    }
    return $dates;
}

function www_signup_vars ( ) {
    $dev_mode = defined('BLOTTO_DEV_MODE') && BLOTTO_DEV_MODE;
    $vars = array (
        'title'              => !$dev_mode ? '' : 'Mr',
        'name_first'         => !$dev_mode ? '' : 'Mickey',
        'name_last'          => !$dev_mode ? '' : 'Mouse',
        'dob'                => !$dev_mode ? '' : '1928-05-15',
        'postcode'           => !$dev_mode ? '' : 'W1A 1AA',
        'address_1'          => !$dev_mode ? '' : 'Broadcasting House',
        'address_2'          => !$dev_mode ? '' : '',
        'address_3'          => !$dev_mode ? '' : '',
        'town'               => !$dev_mode ? '' : 'London',
        'county'             => !$dev_mode ? '' : '',
        'collection_date'    => !$dev_mode ? '' : '2024-12-25',
        'quantity'           => !$dev_mode ? '' : '1',
        'draws'              => !$dev_mode ? '' : '1',
        'pref_email'         => !$dev_mode ? '' : '',
        'pref_sms'           => !$dev_mode ? '' : 'on',
        'pref_post'          => !$dev_mode ? '' : '',
        'pref_phone'         => !$dev_mode ? '' : '',
        'email'              => !$dev_mode ? '' : 'mm@latter.org',
        'email_verify'       => '',
        'mobile'             => !$dev_mode ? '' : '07890309286',
        'mobile_verify'      => '',
        'telephone'          => !$dev_mode ? '' : '01234567890',
        'gdpr'               => !$dev_mode ? '' : 'on',
        'terms'              => !$dev_mode ? '' : 'on',
        'age'                => !$dev_mode ? '' : 'on',
        'signed'             => !$dev_mode ? '' : '',
    );
    foreach ($_POST as $k=>$v) {
        $vars[$k] = $v;
    }
    return $vars;
}

function www_signup_verify_check ($type,$value,$code) {
    $interval = BLOTTO_VERIFY_INTERVAL;
    $c = connect ();
    $c->query (
        "
          DELETE FROM `blotto_verification`
          WHERE `created`<DATE_SUB(NOW(),INTERVAL $interval)
        "
    );
    $r = $c->query (
        "
          SELECT `verify_value`
          FROM `blotto_verification`
          WHERE `type`='$type'
            AND `verify_value`='$value'
            AND `code`='$code'
          LIMIT 0,1
        "
    );
    return $r->fetch_assoc() && true;
}

function www_signup_verify_store ($type,$value,$code) {
    $interval = BLOTTO_VERIFY_INTERVAL;
    if (!($c=connect())) {
        return false;
    }
    try {
        $c->query (
            "
              DELETE FROM `blotto_verification`
              WHERE `created`<DATE_SUB(NOW(),INTERVAL $interval)
                 OR ( `type`='$type' AND `verify_value`='$value' )
            "
        );
        $c->query (
            "
              INSERT INTO `blotto_verification`
              SET
                `type`='$type'
               ,`verify_value`='$value'
               ,`code`='$code'
            "
        );
        return true;
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($e->getMessage());
        return false;
    }
}

function www_validate_email ($email,&$e) {
    $params = [
        "username"  => DATA8_USERNAME,
        "password"  => DATA8_PASSWORD,
        "email"     => $email,
        "level"     => DATA8_EMAIL_LEVEL,
    ];
    try {
        $client = new \SoapClient ("https://webservices.data-8.co.uk/EmailValidation.asmx?WSDL");
        $result = $client->IsValid ($params);
        if ($result->IsValidResult->Status->Success==false) {
            $e[] = "Error trying to validate email: ".$result->Status->ErrorMessage;
            return false;
        }
        if ($result->IsValidResult->Result=='Invalid') {
            $e[] = "$email is an invalid address";
            return false;
        }
    }
    catch (\Throwable $error) {
        notify (
            BLOTTO_EMAIL_WARN_TO,
            "Email validation problem",
            "Error message: ".$error->getMessage()
        );
        error_log ($error->getMessage());
        $e[] = "Error trying to use email validation service";
        return false;
    }
    return true;
}

function www_validate_phone ($number,$type,&$e) {
    $params = array(
        "username"          => DATA8_USERNAME,
        "password"          => DATA8_PASSWORD,
        "telephoneNumber"   => $number,
        "defaultCountry"    => DATA8_COUNTRY,
    );
    $params['options']['Option'][] = [ "Name" => "UseMobileValidation", "Value" => false ];
    $params['options']['Option'][] = [ "Name" => "UseLineValidation",   "Value" => false ];
    $client = new \SoapClient ("https://webservices.data-8.co.uk/InternationalTelephoneValidation.asmx?WSDL");
    $result = $client->IsValid($params);
    if ($result->IsValidResult->Status->Success == false) {
        $e[] = "Error trying to validate phone number: ".$result->Status->ErrorMessage;
        return false;
    }
    if ($result->IsValidResult->Result->ValidationResult=='Invalid') {
        $e[] = "$number is not a valid phone number";
        return false;
    }
    elseif ($type == 'M' && $result->IsValidResult->Result->NumberType!='Mobile') {
        $e[] = "$number is not a valid mobile phone number";
        return false;
    }
    return true;
}

function www_validate_signup ($org,&$e=[],&$go=null) {
    foreach ($_POST as $key => $value) {
        $_POST[$key] = trim($value);
        if (www_is_url($_POST[$key])) {
            // Foil phishing attempts
            $_POST[$key] = '';
        }
    }
    $required = [
        'title'             => [ 'about',        'Title is required' ],
        'name_first'        => [ 'about',        'First name is required' ],
        'name_last'         => [ 'about',        'Last name is required' ],
        'dob'               => [ 'about',        'Date of birth is required' ],
        'postcode'          => [ 'address',      'postcode is required' ],
        'address_1'         => [ 'address',      'Address is required' ],
        'town'              => [ 'address',      'Town/city is required' ],
        'collection_date'   => [ 'requirements', 'First draw date is required' ],
        'quantity'          => [ 'requirements', 'Ticket requirements are needed' ],
        'draws'             => [ 'requirements', 'Ticket requirements are needed' ],
        'gdpr'              => [ 'smallprint',   'You must confirm that you have read the GDPR statement' ],
        'terms'             => [ 'smallprint',   'You must agree to terms & conditions and the privacy policy' ],
        'age'               => [ 'smallprint',   'You must be aged 18 or over to signup' ],
        'email'             => [ 'contact',      'Email is required' ],
        'mobile'            => [ 'contact',      'Mobile number is required'  ]
    ];
    foreach ($required as $field=>$details) {
        if (!array_key_exists($field,$_POST) || !strlen($_POST[$field])) {
            set_once ($go,$details[0]);
            $e[]        = $details[1];
        }
    }
    $org = org ();
    if ($_POST['postcode'] && !territory_permitted($postcode)) {
        set_once ($go,'about');
        $e[]        = 'Sorry - we are not allowed to sell lottery tickets to your address';
    }
    if ($_POST['dob']) {
        $dt             = new \DateTime ($_POST['dob']);
        if (!$dt) {
            set_once ($go,'about');
            $e[]        = 'Date of birth is not valid';
        }
        else {
            $now        = new \DateTime ();
            $years      = $dt->diff($now)->format ('%r%y');
            if ($years<18) {
                set_once ($go,'about');
                $e[]    = 'You must be 18 or over to sign up';
            }
        }
    }
    if (intval($_POST['quantity'])<1 || intval($_POST['draws'])<1) {
        set_once ($go,'requirements');
        $e[] = 'Ticket requirements are not valid';
    }
    else {
        if (intval($_POST['quantity'])>$org['signup_ticket_cap']) {
            set_once ($go,'requirements');
            $e[] = 'Tickets are limited to a maximum of '.$org['signup_ticket_cap'];
        }
        if ($_POST['draws']*$_POST['quantity']*BLOTTO_TICKET_PRICE/100>$org['signup_amount_cap']) {
            set_once ($go,'requirements');
            $e[] = 'Purchases are limited to a maximum of £'.$org['signup_amount_cap'];
        }
    }
    if ($_POST['email']) {
        if ($org['signup_verify_email']>0) {
            if (!www_signup_verify_check('email',$_POST['email'],$_POST['email_verify'])) {
                set_once ($go,'contact');
                $e[] = 'Email address is not verified';
            }
        }
        elseif (!www_validate_email($_POST['email'],$e)) {
            set_once ($go,'contact');
        }
    }
    if ($_POST['mobile']) {
        if ($org['signup_verify_sms']>0) {
            if (!www_signup_verify_check('mobile',$_POST['mobile'],$_POST['mobile_verify'])) {
                set_once ($go,'contact');
                $e[] = 'Telephone number (mobile) is not verified';
            }
        }
        elseif (!www_validate_phone($_POST['mobile'],'M',$e)) {
            set_once ($go,'contact');
        }
    }
    if ($_POST['telephone'] && !www_validate_phone ($_POST['telephone'],'L',$e)) {
        set_once ($go,'contact');
    }
    if (count($e)) {
        return false;
    }
    return true;
}

function www_winners ($format='') {
    // Provide latest winners for external API requests
    $zo = connect ();
    $results = new \stdClass ();
    $results->number_matches = [];
    $results->winners = [];
    $results->classes = new \stdClass ();
    $results->classes->number_matches = [];
    $results->classes->winners = [];
    $level = [];
    if (!$format) {
        $format = 'Y-m-d';
    }
    if (!$zo) {
        return $results;
    }
    $rdb = BLOTTO_RESULTS_DB;
    try {
        $q = "
          SELECT
            MAX(`draw_closed`) AS `draw_closed`
           ,DATE(drawOnOrAfter(MAX(`draw_closed`))) AS `drawn`
          FROM `$rdb`.`blotto_result`
          WHERE drawPublishAfter(drawOnOrAfter(`draw_closed`))<=NOW()
        ";
        $rs = connect()->query ($q);
        $r = $rs->fetch_assoc ();
        if ($r) {
            $draw = draw ($r['draw_closed']);
            // Get the lowest level for each group
            foreach ($draw->prizes as $p) {
                if ($p['level_method']!='RAFF') {
                    if (!array_key_exists($p['group'],$level)) {
                        $level[$p['group']] = 1000000;
                    }
                    if ($p['level']<$level[$p['group']]) {
                        $level[$p['group']] = $p['level'];
                    }
                }
            }
            // Use lowest level prize in group for result name
            foreach ($draw->prizes as $p) {
                if ($p['level_method']!='RAFF') {
                    if ($p['level']==$level[$p['group']]) {
                        $results->number_matches[] = [
                            $p['name'],
                            $p['results'][0]
                        ];
                        $results->classes->number_matches[] = preg_replace (
                            '<[^A-z0-9\-]>',
                            '',
                            str_replace (
                                ' ',
                                '-',
                                strtolower ($p['name'])
                            )
                        );
                    }
                }
            }
            $dt                 = new DateTime ($r['drawn']);
            $results->date      = $dt->format ($format);
            $q = "
              SELECT
                *
              FROM `Wins`
              WHERE `draw_closed`='{$r['draw_closed']}'
              ORDER BY `winnings` DESC, `ticket_number`
            ";
            $ws = connect()->query ($q);
            while ($w=$ws->fetch_assoc()) {
                $results->winners[] = [
                    $w['ticket_number'],
                    $w['prize']
                ];
                $results->classes->winners[] = preg_replace (
                    '<[^A-z0-9\-]>',
                    '',
                    str_replace (
                        ' ',
                        '-',
                        strtolower ($w['prize'])
                    )
                );
            }
        }
        else {
            return $results;
        }
    }
    catch (\mysqli_sql_exception $e) {
        error_log ($e->getMessage());
    }
    return $results;
}

function yes_or_no ($loose_value,$y=true,$n=false) {
    if ($loose_value && !preg_match('<^0+(\.0+)?$>',$loose_value)) {
        return $y;
    }
    return $n;
}

