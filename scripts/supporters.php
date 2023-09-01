<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


if (!array_key_exists(4,$argv)) {
    fwrite (STDERR,"Canvas company directory name not passed as argument\n");
    exit (101);
}

$ccc = strtoupper ($argv[4]);
$errors = '';
$supporters = [];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (102);
}


// Compulsory fields
$required = [
    'ClientRef',
    'NamesGiven',
    'NamesFamily',
    'AddressLine1',
    'Town',
    'Postcode',
];
foreach ($required as $field) {
    $qs = "
      SELECT
        *
      FROM `tmp_supporter`
      WHERE REPLACE(`$field`,' ','')=''
         OR `$field` IS NULL
      ;
    ";
    try {
        $check = $zo->query ($qs);
        if ($check->num_rows) {
            $errors .= "`$field` is compulsory\n";
            while ($c=$check->fetch_assoc()) {
                if (str_replace(' ','',$c[$field])=='') {
                    $errors .= "    {$c['ClientRef']} {$c['NamesFamily']}";
                }
            }
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (103);
    }
}


// ClientRef duplication
$qs = "
  SELECT
    `s2`.`ClientRef`
   ,COUNT(`s1`.`ClientRef`) AS `instances`
  FROM `tmp_supporter` AS `s1`
  JOIN (
    SELECT
      DISTINCT `ClientRef`
    FROM `tmp_supporter`
  ) AS `s2`
    ON `s2`.`ClientRef`=`s1`.`ClientRef`
  GROUP BY `ClientRef`
  HAVING `instances`>1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`ClientRef` must be unique\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']} x {$c['instances']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (104);
}



// ClientRef validation [regexp]
$crm = BLOTTO_CREF_MATCH;
$qs = "
  SELECT
    `ClientRef`
  FROM `tmp_supporter`
  WHERE `ClientRef` NOT REGEXP '$crm'
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`ClientRef` does not match <$crm>\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (105);
}

// ClientRef validation [splitter character(s) for player incrementing]
$splitter = BLOTTO_CREF_SPLITTER;
$qs = "
  SELECT
    `ClientRef`
  FROM `tmp_supporter`
  -- First players only
  WHERE `ClientRef` LIKE '%$splitter%'
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`ClientRef` (original) contains the reserved character sequence '$splitter'\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (106);
}

// Chances validation
$qs = "
  SELECT
    `ClientRef`
   ,`Chances`
  FROM `tmp_supporter`
  WHERE `Chances` NOT REGEXP '^[0-9]+$'
  -- sanity
     OR `Chances`>10
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`Chances` are out of range\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']} x {$c['Chances']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (107);
}

// Phone validation (optional field)
// MySQL regexp needs double escaping for reasons not yet fathomed...
$phonere='^\\\\+?[0-9]+$';
$qs = "
  SELECT
    `ClientRef`
   ,`Mobile`
  FROM `tmp_supporter`
  WHERE (
           REPLACE(`Mobile`,' ','')!=''
       AND `Mobile` IS NOT NULL
       AND (
            REPLACE(`Mobile`,' ','') NOT REGEXP '$phonere'
         OR LENGTH(REPLACE(`Mobile`,' ',''))<10
         OR LENGTH(REPLACE(`Mobile`,' ',''))>16
       )
  )
     OR (
           REPLACE(`Telephone`,' ','')!=''
       AND `Telephone` IS NOT NULL
       AND (
            REPLACE(`Telephone`,' ','') NOT REGEXP '$phonere'
         OR LENGTH(REPLACE(`Telephone`,' ',''))<10
         OR LENGTH(REPLACE(`Telephone`,' ',''))>16
       )
     )
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`Mobile` and/or `Telephone` invalid\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']} {$c['Mobile']} {$c['Telephone']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (108);
}

// Postcode validation
$regexp = BLOTTO_POSTCODE_PREG;
$qs = "
  SELECT
    `ClientRef`
  FROM `tmp_supporter`
  WHERE `Postcode`!=''
    AND `Postcode` IS NOT NULL
    AND REPLACE(UPPER(`Postcode`),' ','') NOT REGEXP '$regexp'
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->num_rows) {
        $errors .= "`Postcode` invalid\n";
        while ($c=$check->fetch_assoc()) {
            $errors .= "    {$c['ClientRef']} {$c['Postcode']}\n";
        }
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (109);
}


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
    while ($s=$new->fetch_assoc()) {
        if (!territory_permitted($s['Postcode'])) {
            $errors .= "{$s['ClientRef']} - postcode {$s['Postcode']} is outside territory ".BLOTTO_TERRITORIES_CSV." - $ccc\n";
            continue;
        }
        $s['ca'] = '';
        $s['cv'] = '';
        $s['bl'] = 0.00;
        if (trim($s['SysEx'])) {
            // blotto currently has three sys-ex messages beyond the FLC standard
            // A JSON object is expected
            $sx     = null;
            try {
                $sx = json_decode ($s['SysEx']);
            }
            catch (\Exception $e) {
                $errors .= "{$s['ClientRef']} - could not interpret sysex\n";
                continue;
            }
            if (property_exists($sx,'balance')) {
                if (!preg_match('<^[0-9]+$>',$sx->balance) && !preg_match('<^[0-9]*\.[0-9]+$>',$sx->balance)) {
                    $errors .= "{$s['ClientRef']} - could not interpret sysex->balance = '{$sx->balance}'\n";
                    continue;
                }
                $s['bl'] = round ($sx->balance,2);
            }
            if (property_exists($sx,'cc_agent_ref')) {
                $s['ca'] = esc ($sx->cc_agent_ref);
            }
            if (property_exists($sx,'cc_ref')) {
                $s['cv'] = esc ($sx->cc_ref);
            }
            if (property_exists($sx,'first_draw_close')) {
                try {
                    $dc = new \DateTime ($sx->first_draw_close);
                    $dc = $dc->format ('Y-m-d');
                }
                catch (\Exception $e) {
                    $errors .= "{$s['ClientRef']} - could not interpret sysex->first_draw_close = '{$sx->first_draw_close}'\n";
                    continue;
                }
            }
        }
        $supporters[] = $s;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (110);
}


if ($errors) {
    notify (BLOTTO_EMAIL_WARN_TO,"Rejected import from $ccc",$errors);
    fwrite (STDERR,"Rejected import from $ccc\n");
    // Skip import without aborting
    exit (0);
}


echo "\nUSE `".BLOTTO_MAKE_DB."`;\n\n";
echo "ALTER TABLE `blotto_contact` DROP INDEX IF EXISTS `search_idx`;\n\n";
foreach ($supporters as $s) {
    $cd         = esc ($s['Approved']);
    $sg         = esc ($s['Signed']);
    $ap         = esc ($s['Approved']);
    $ch         = intval ($s['Chances']);
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
    $db         = "null";
    $yb         = "null";
    if (preg_match('<^[0-9]{4}-[0-9]{2}-[0-9]{2}$>',$s['DOB'])) {
        $db     = "'{$s['DOB']}'";
        $yb     = intval (substr($s['DOB'],0,4));
    }
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
    echo "  ('$cd','$sg','$ap','$ch','$ccc','{$s['ca']}','{$s['cv']}','$cr');\n";
    echo "SET @sid = LAST_INSERT_ID();\n";
    echo "INSERT INTO `blotto_player` (`supporter_id`,`client_ref`,`opening_balance`) VALUES\n";
    echo "  (@sid,'$cr',{$s['bl']});\n\n";
    // First contact should not be `created` = timestamp of when this script runs
    // Rather it should be `created` = when supporter is notionally created
    echo "INSERT INTO `blotto_contact` (`created`,`supporter_id`,`title`,`name_first`,`name_last`,`email`,`mobile`,`telephone`,`address_1`,`address_2`,`address_3`,`town`,`county`,`postcode`,`country`,`dob`,`yob`,`p0`,`p1`,`p2`,`p3`,`p4`,`p5`,`p6`,`p7`,`p8`,`p9`) VALUES\n";
    echo "  ('$cd 03:00:00',@sid,'$tt','$nf','$nl','$em','$mb','$tl','$a1','$a2','$a3','$tn','$cn','$pc','$cy',$db,$yb,'$p0','$p1','$p2','$p3','$p4','$p5','$p6','$p7','$p8','$p9');\n\n";
    $count++;
}


echo "-- COUNT: ".count($supporters)." supporters from $ccc --\n\n";

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


