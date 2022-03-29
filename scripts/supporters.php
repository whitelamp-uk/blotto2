<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


if (!array_key_exists(4,$argv)) {
    fwrite (STDERR,"Canvas company directory name not passed as argument\n");
    exit (101);
}

$ccc = strtoupper ($argv[4]);


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}

/*
TODO: delete this

// TEMPORARY CODE TO FILL HOLES IN canvas_agent_ref
$qu = "
  UPDATE `blotto_supporter`
  SET `canvas_agent_ref`=SUBSTR(`client_ref`,3,4)
  WHERE `canvas_agent_ref` IS NULL
     OR `canvas_agent_ref`=''
";
try {
    $zo->query ($qu);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR, $qu."\n".$zo->error."\n");
    exit (127);
}
// END OF TEMPORARY CODE
*/


$qs = "
  SELECT
    *
  FROM `tmp_supporter`
  WHERE REPLACE(`ClientRef`,' ','')=''
     OR REPLACE(`NamesGiven`,' ','')=''
     OR REPLACE(`NamesFamily`,' ','')=''
     OR REPLACE(`AddressLine1`,' ','')=''
     OR REPLACE(`Town`,' ','')=''
     OR REPLACE(`Postcode`,' ','')=''
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    while ($c=$check->fetch_assoc()) {
        if (str_replace(' ','',$c['ClientRef'])=='') {
            fwrite (STDERR,"Supporter import: `tmp_supporter`.`ClientRef` is compulsory - $ccc\n");
            exit (103);
        }
/*

TODO: Some attempt to sanity check formats and values
by something based roughly on the theme below.

        if (str_replace(' ','',$c['FirstName'])=='') {
            fwrite(STDERR, "Supporter import: `tmp_supporter`.`NamesGiven` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['LastName'])=='') {
            fwrite(STDERR, "Supporter import: `tmp_supporter`.`NamesFamily` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['AddressLine1'])=='') {
            fwrite(STDERR, "Supporter import: `tmp_supporter`.`AddressLine1` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['Town'])=='') {
            fwrite(STDERR, "Supporter import: `tmp_supporter`.`Town` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['Postcode'])=='') {
            fwrite(STDERR, "Supporter import: `tmp_supporter`.`Postcode` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
*/

    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}


$qs = "
  SELECT
    *
  FROM (
    SELECT
      COUNT(*) AS `rows`
     ,COUNT(DISTINCT `ClientRef`) AS `refs`
    FROM `tmp_supporter` AS `t`
  ) as `cf`
  WHERE `cf`.`refs`!=`cf`.`rows`
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->fetch_assoc()) {
        fwrite (STDERR,"Client reference column is not unique\n");
        exit (105);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (106);
}

$crm = BLOTTO_CREF_MATCH;
$qs = "
  SELECT
    `ClientRef`
  FROM `tmp_supporter`
  WHERE `ClientRef` NOT REGEXP '$crm'
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($c=$check->fetch_assoc()) {
        fwrite (STDERR,"Client reference '".$c['ClientRef']."' contains illegal characters\n");
        exit (107);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (108);
}

$splitter = BLOTTO_CREF_SPLITTER;
$qs = "
  SELECT
    `ClientRef`
  FROM `tmp_supporter`
  WHERE `ClientRef` LIKE '%$splitter%'
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($c=$check->fetch_assoc()) {
        fwrite (STDERR,"Client reference ".$c['ClientRef']." contains the reserved character sequence '$splitter' \n");
        exit (109);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (110);
}

$qs = "
  SELECT
    `ClientRef`
   ,`Chances`
  FROM `tmp_supporter`
  WHERE `Chances` IS NULL
     OR `Chances`<1
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($c=$check->fetch_assoc()) {
        fwrite (STDERR,"Chances='".$c['Chances']."' is illegal for ".$c['ClientRef']."\n");
        exit (111);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (112);
}



$phonere='^\\\\+?[0-9]+$';
$qs = "
  SELECT
    `ClientRef`
   ,`Mobile`
  FROM `tmp_supporter`
  WHERE REPLACE(`Mobile`,' ','') NOT REGEXP '$phonere'
    AND REPLACE(`Mobile`,' ','')!=''
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($c=$check->fetch_assoc()) {
        fwrite (STDERR,"Mobile number '".$c['Mobile']."' is illegal for ".$c['ClientRef']."\n");
        exit (111);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (112);
}

// MySQL regexp needs double escaping for reasons not yet fathomed...
$qs = "
  SELECT
    `ClientRef`
   ,`Telephone`
  FROM `tmp_supporter`
  WHERE REPLACE(REPLACE(`Telephone`,'-',''),' ','') NOT REGEXP '$phonere'
    AND REPLACE(`Telephone`,' ','')!=''
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($c=$check->fetch_assoc()) {
        fwrite(STDERR, "Telephone number '".$c['Telephone']."' is illegal for ".$c['ClientRef']."\n");
        exit (113);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (114);
}

/*
$qs = "
  SELECT
    `Postcode`
  FROM `tmp_supporter`
  WHERE REPLACE(`Postcode`,' ','') NOT REGEXP '^[A-Z][A-Z]?[0-9][0-9]?[0-9][A-Z][A-Z]$'
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->fetch_assoc()) {
        fwrite(STDERR, "Poscode ".$c['Postcode']." is not valid\n");
        exit (115);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (116);
}
*/


echo "\nUSE `".BLOTTO_MAKE_DB."`\n\n";


echo "
ALTER TABLE `blotto_contact`
DROP INDEX IF EXISTS `search_idx`
;
";

$qs = "
  SELECT
    `t`.*
  FROM `tmp_supporter` AS `t`
  LEFT JOIN `blotto_supporter` AS `s`
         ON `s`.`client_ref`=`t`.`ClientRef`
  WHERE `s`.`id` IS NULL
  GROUP BY `ClientRef`
  ;
";
try {
    $new            = $zo->query ($qs);
    $count          = 0;
    while ($s=$new->fetch_assoc()) {
        if (!territory_permitted($s['Postcode'],$areas)) {
            fwrite(STDERR, "Postcode '{$s['Postcode']}'' is outside territory '".BLOTTO_TERRITORIES_CSV."' - $ccc - for '{$s['ClientRef']}'\n");
            exit (115);
        }
        $cd         = esc ($s['Approved']);
        $sg         = esc ($s['Signed']);
        $ap         = esc ($s['Approved']);
        $ch         = intval ($s['Chances']);
        $ca         = '';
        $cv         = '';
        $bl         = 0.00;
        if (trim($s['SysEx'])) {
            // blotto currently has three sys-ex messages beyond the FLC standard
            // A JSON object is expected
            $sx     = null;
            try {
                $sx = json_decode ($s['SysEx']);
            }
            catch (\Exception $e) {
                fwrite (STDERR,$e->getMessage()."\n");
                exit (116);
            }
            if (!$sx) {
                fwrite (STDERR,"Could not interpret system exclusive column = '{$s['SysEx']}'\n");
                exit (117);
            }
            if (property_exists($sx,'cc_agent_ref')) {
                $ca = esc ($sx->cc_agent_ref);
            }
            if (property_exists($sx,'cc_ref')) {
                $cv = esc ($sx->cc_ref);
            }
            if (property_exists($sx,'balance')) {
                if (!preg_match('<^[0-9]+$>',$sx->balance) && !preg_match('<^[0-9]*\.[0-9]+$>',$sx->balance)) {
                    fwrite (STDERR,"Could not interpret sysex->balance = '{$sx->balance}'\n");
                    exit (117);
                }
                $bl = round ($sx->balance,2);
            }
        }
        $cr         = esc ($s['ClientRef']);
        $tt         = esc ($s['Title']);
        if (in_array($s['EasternOrder'],['','0','N','n'])) {
            $nf     = esc ($s['NamesGiven']);
            $nl     = esc ($s['NamesFamily']);
        }
        else {
            $nf     = esc ($s['NamesFamily']);
            $nl     = esc ($s['NamesGiven']);
        }
        $em         = esc ($s['Email']);
        $mb         = esc (str_replace('-',' ',$s['Mobile']));
        $tl         = esc (str_replace('-',' ',$s['Telephone']));
        $a1         = esc (trim($s['AddressLine1']));
        $a2         = esc (trim($s['AddressLine2']));
        $a3         = esc (trim($s['AddressLine3']));
        $tn         = esc (trim($s['Town']));
        $cn         = esc (trim($s['County']));
        $pc         = esc (trim($s['Postcode']));
        $cy         = esc (trim($s['Country']));
        $ps         = explode (BLOTTO_PREFERENCES_SEP,trim($s['Preferences']));
        for ($i=0;$i<10;$i++) {
            if (array_key_exists($i,$ps)) {
                ${'p'.$i} = esc (trim($ps[$i]));
            }
            else {
                ${'p'.$i} = '';
            }
        }
        echo "INSERT INTO `blotto_supporter` (`created`,`signed`,`approved`,`projected_chances`,`canvas_code`,`canvas_agent_ref`,`canvas_ref`,`client_ref`) VALUES\n";
        echo "  ('$cd','$sg','$ap','$ch','$ccc','$ca','$cv','$cr');\n";
        echo "SET @sid = LAST_INSERT_ID();\n";
        echo "INSERT INTO `blotto_player` (`supporter_id`,`client_ref`,`opening_balance`) VALUES\n";
        echo "  (@sid,'$cr',$bl);\n\n";
        // First contact should not be `created` = timestamp of when this script runs
        // Rather it should be `created` = when supporter is notionally created
        echo "INSERT INTO `blotto_contact` (`created`,`supporter_id`,`title`,`name_first`,`name_last`,`email`,`mobile`,`telephone`,`address_1`,`address_2`,`address_3`,`town`,`county`,`postcode`,`country`,`p0`,`p1`,`p2`,`p3`,`p4`,`p5`,`p6`,`p7`,`p8`,`p9`) VALUES\n";
        echo "  ('$cd 03:00:00',@sid,'$tt','$nf','$nl','$em','$mb','$tl','$a1','$a2','$a3','$tn','$cn','$pc','$cy','$p0','$p1','$p2','$p3','$p4','$p5','$p6','$p7','$p8','$p9');\n\n";
        $count++;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (118);
}

echo "-- COUNT: $count supporters from $ccc --\n\n";

if (!$count) {
    $qs = "
      SELECT
        COUNT(*) AS `rows`
      FROM `tmp_supporter`
      ;
    ";
    try {
        $rows = $zo->query ($qs);
        $rows = $rows->fetch_assoc ()['rows'];
        fwrite (STDERR,"$rows rows of data found for $ccc\n");
        if ($rows<5) {
            fwrite (STDERR,"WARNING: very little data was found for $ccc\n");
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (119);
    }
}

echo "
CREATE FULLTEXT INDEX `search_idx`
  ON `blotto_contact` (
    `name_first`,
    `name_last`,
    `email`,
    `mobile`,
    `address_1`,
    `address_2`,
    `address_3`,
    `town`,
    `postcode`
  )
;
";


