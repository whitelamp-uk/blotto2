<?php


require __DIR__.'/functions.php';
cfg ();
require $argv[1];


if (!defined('BLOTTO_DEMO') || !BLOTTO_DEMO) {
    echo "    Not a demo\n";
    exit (0);
}


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

echo "    Mangling demo make database ".BLOTTO_MAKE_DB."\n"; 

$db   = BLOTTO_ANONYMISER_DB;
$male_titles    = explode (',', BLOTTO_MALE_TITLES);
$female_titles  = explode (',', BLOTTO_FEMALE_TITLES);
$ngs_titles     = explode (',', BLOTTO_TITLES);
$ngs_titles     = array_diff ($ngs_titles,$female_titles,$male_titles);


$qs = "
  SELECT
    `s`.`id` AS `s_id`
   ,`c`.`id` AS `c_id`
   ,`c`.`title`
   ,`c`.`name_first`
   ,`c`.`name_last`
   ,`c`.`email`
   ,`c`.`mobile`
   ,`c`.`telephone`
   ,`c`.`address_1`
   ,`c`.`address_2`
   ,`c`.`address_3`
   ,`c`.`town`
   ,`c`.`county`
   ,`c`.`postcode`
   ,`c`.`country`
   ,`c`.`dob`
   ,`p`.`id` AS `p_id`
   ,`m`.`Provider` AS `m_pr`
   ,`m`.`RefNo` AS `m_rn`
   ,`m`.`Name`
   ,`m`.`Sortcode`
   ,`m`.`Account`
  FROM `blotto_supporter` AS `s`
  LEFT JOIN `blotto_contact` AS `c`
         ON `c`.`supporter_id`=`s`.`id`
  LEFT JOIN `blotto_player` AS `p`
         ON `p`.`client_ref`=`s`.`client_ref`
  LEFT JOIN `blotto_build_mandate` as `m`
         ON `m`.`ClientRef`=`s`.`client_ref` 
  ORDER BY `s_id`
  ;
";

try {
    $people = $zo->query ($qs);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}

$q = "
  SELECT
    COUNT(*) AS `num`
  FROM `$db`.`road_names`
";
try {
    $res = $zo->query ($q);
    $roads_num = $res->fetch_object()->num;
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$q."\n".$e->getMessage()."\n");
    exit (103);
}

$q = "
  SELECT
    COUNT(*) AS `num`
  FROM `$db`.`female_names`
";
try {
    $res = $zo->query ($q);
    $femnames_num = $res->fetch_object()->num;
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$q."\n".$e->getMessage()."\n");
    exit (104);
}

$q = "
  SELECT
    COUNT(*) AS `num`
  FROM `$db`.`male_names`
";
try {
    $res = $zo->query($q);
    $mnames_num = $res->fetch_object()->num;
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$q."\n".$e->getMessage()."\n");
    exit (105);
}



$n = 0;
$prev = null;

while ($cur=$people->fetch_assoc()) {

/* output fieldnames
    if (!$prev) {
        $prev = $cur;
        foreach ($prev as $k => $v) {
            echo $k."\t";
        }
        echo "\n";
    }
*/

    if (!isset($new)) {  // create structure
        $new = $cur;
    }
    foreach ($new as $k => $v) { // blank out every time
        $new[$k] = '';
    }
    // if prev same s_id as cur, reuse name etc (check if mandate account changed or not etc.)
    // NB that most changes are changes to bank sortcode / account, and if it's not that, it's the mandate Name;
    // but our will never be "wrong"; so we *always* have a fresh sortcode and accountnumber
    // so repeats are identical except for sortcode and account.
    // and then we do not need prevnew
    if ($prev && $prev['s_id'] == $cur['s_id']) {
    /*
        if ($n < 10) {
            foreach ($p as $k => $v) {
                echo $v."\t";
            }
            echo "\n";
        }
        $n++;
    */
        $repeat = true;
    }
    else {
        $repeat = false;
    }

/*
Warning: this old code does not allow for $prev['m_pr']
    if ($repeat && ($cur['c_id'] != $prev['c_id'])) {
              // || $cur['m_rn'] != $prev['m_rn'])) { 
        foreach ($prev as $k => $v) {
            echo $v."\t";
        }
        echo "\n";
        foreach ($cur as $k => $v) {
            echo $v."\t";
        }
        echo "\n";
*/

    if (!$repeat) {
        // Create name, dob, phones

        if (in_array($cur['title'], $female_titles)) {
            $nmpref = 'fe';
        }
        elseif (in_array($cur['title'], $male_titles)) {
            $nmpref = '';
        }
        elseif (in_array($cur['title'], $ngs_titles)) {
            $nmpref = (rand(1,100) > 45 ? 'fe': '');
        }
        else {
            $nmpref = (rand(1,100) > 36 ? 'fe': '');
        }

        $rndid = rand(1, ${$nmpref.'mnames_num'});
        $nq = "SELECT `name` FROM `$db`.`".$nmpref."male_names` WHERE id =".$rndid;
        try {
            $res = $zo->query ($nq);
            $name = $res->fetch_object()->name; 
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$nq."\n".$e->getMessage()."\n");
            exit (106);
        }

        $namearr = explode(' ', $name, 2);
        $new['name_first'] = $namearr[0];
        $new['name_last']  = $namearr[1];
        $new['Name'] = $cur['title'].' '.$namearr[0].' '.$namearr[1];
        $new['email'] = strtolower($namearr[0]).'.'.strtolower($namearr[1]).'@fakeisp.com';

        if ($cur['mobile']) {
            $new['mobile'] = substr($cur['mobile'], 0, 5).str_pad(rand(10000,999999), 6, '0', STR_PAD_LEFT);
        }
        if ($cur['telephone']) {
            $new['telephone'] = substr($cur['telephone'], 0, 5).rand(210000,899999);
        }

        $yob = substr($cur['dob'],0,4);
        if ($yob >= 1900 && $yob <= date('Y') - 16) { 
            $yearstart = strtotime($yob.'-01-01');  // poss sanity check?
            $yearend = strtotime($yob.'-12-31 23:59:59');
            $new['dob'] = date('Y-m-d', rand($yearstart, $yearend));
        }

    }
    else {
        // Copy name, phones, dob from prev

        $new['name_first'] =  $prev['name_first'];
        $new['name_last']  = $prev['name_last'];
        $new['Name'] =  $prev['Name'];
        $new['email'] =  $prev['email'];
        $new['mobile'] =  $prev['mobile'];
        $new['telephone'] =  $prev['telephone'];
        $new['dob'] =  $prev['dob'];

    }

    if ((!$repeat || $cur['c_id'] != $prev['c_id']) && $cur['c_id']) {
        // Create new address and update contact. in practice if repeat, always same contact
        // If none found (e.g. Belfast) then repeat without where clause

        $pcstub = trim (substr($cur['postcode'],0,-3));
        $addr = null;
        $first = true;
        while (!$addr) {
            if ($first) {
                $aq = "SELECT `roadname`, `town` FROM `$db`.`road_names` WHERE `district`='".escm($pcstub)."' ORDER BY RAND() LIMIT 0,1";
            }
            else {
                $rndid = rand (1,$roads_num);
                $aq = "SELECT `roadname`, `town` FROM `$db`.`road_names` WHERE id = ".$rndid;
            }
            try {
                $res = $zo->query($aq); 
                if ($res->num_rows) {
                    $addr = $res->fetch_assoc ();
                    break;
                }
                else {
                    $first = false;
                }
            }
            catch (\mysqli_sql_exception $e) {
                fwrite (STDERR,$aq."\n".$e->getMessage()."\n");
                exit (107);
            }
        }

        $new['address_1'] = rand(1,121).' '.$addr['roadname'];
        $new['town'] = $addr['town'];
        $new['county'] = $cur['county'];
        $new['country'] = $cur['country'];
        $new['postcode'] = $pcstub.' '.rand(1,9).chr(rand(65,90)).chr(rand(65,90));

        $uq = "UPDATE blotto_contact SET  name_first = '".escm($new['name_first'])."', name_last = '".escm($new['name_last'])."', email = '".escm($new['email'])."', ";
        $uq .= "mobile = '".escm($new['mobile'])."', telephone = '".escm($new['telephone'])."', address_1 = '".escm($new['address_1'])."', address_2 = '".escm($new['address_2'])."', ";
        $uq .= "address_3 = '".escm($new['address_3'])."', town = '".escm($new['town'])."', county = '".escm($new['county'])."', postcode = '".escm($new['postcode'])."', ";
        $uq .= "country = '".escm($new['country'])."' ";
        if (strlen($new['dob']) == 10) {
            $uq .= ", dob = '".escm($new['dob'])."' ";
        }
        $uq .= "WHERE id = ".$cur['c_id'];
        try {
            $zo->query ($uq);
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$uq."\n".$e->getMessage()."\n");
            exit (108);
        }

    }

    if ((!$repeat || $cur['m_pr'] != $prev['m_pr'] || $cur['m_rn'] != $prev['m_rn']) && $cur['m_rn']) {
        // Create new sort / account and update mandate

        $new['Sortcode'] = str_pad(rand(10000,999999), 6, '0', STR_PAD_LEFT);
        $new['Account']  = str_pad(rand(1000000,99999999), 8, '0', STR_PAD_LEFT);
        $bq = "UPDATE blotto_build_mandate SET NAME = '".escm($new['Name'])."', Sortcode = '".escm($new['Sortcode'])."', Account = '".escm($new['Account'])."' ";
        $bq .= "WHERE Provider = '{$cur['m_pr']}' AND RefNo = {$cur['m_rn']}";
        try {
            $zo->query ($bq);
        }
        catch (\mysqli_sql_exception $e) {
            fwrite (STDERR,$bq."\n".$e->getMessage()."\n");
            exit (109);
        }

    }

// This old stuff does not know about m_pr

    //if ($n < 10) {         print_r($prev); print_r($new);     } else exit;

    // Use c_id, p_id, m_rn, to decide whether to add new bogus details or not

    // Use BLOTTO_MALE_TITLES and other to detect gender from title; if title blank then assign 64 female 36 male else (Doctors etc) assign 55/45
    // Am I totally over-thinking this or what

    // create gendered first name; create last_name; if email not blank then email address from fisrstname.lastname@pretendisp.com

    // names do not change on repeats, our staff never get it wrong :-}

    // create fake address (postcode keeps first half)

    // dob keeps year, set random month, random day (1-28 to keep simple?)

    // mobile and telephone updated (keep area code)

    // random sortcde and account number.

    $prev = $cur;
    $prevnew = $new;
    $n++;

}


