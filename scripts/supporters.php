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

// TEMPORARY CODE TO FILL HOLES IN canvas_agent_ref
$qu = "
  UPDATE `blotto_supporter` as `s`
  JOIN `tmp_supporter` AS `tmp`
    ON `tmp`.`ClientRef`=`s`.`client_ref`
  SET `s`.`canvas_agent_ref`=`tmp`.`AgentRef`
  WHERE `s`.`canvas_agent_ref` IS NULL
";
try {
    $zo->query ($qu);
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR, $qu."\n".$zo->error."\n");
    exit (127);
}
// END OF TEMPORARY CODE


$qs = "
  SELECT
    *
  FROM `tmp_supporter`
  WHERE REPLACE(`ClientRef`,' ','')=''
     OR REPLACE(`FirstName`,' ','')=''
     OR REPLACE(`LastName`,' ','')=''
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
            fwrite (STDERR,"`tmp_supporter`.`ClientRef` is compulsory - $ccc\n");
            exit (103);
        }
/*

TODO: Some attempt to sanity check formats and values
by something based roughly on the theme below.

        if (str_replace(' ','',$c['FirstName'])=='') {
            fwrite(STDERR, "`tmp_supporter`.`FirstName` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['LastName'])=='') {
            fwrite(STDERR, "`tmp_supporter`.`LastName` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['AddressLine1'])=='') {
            fwrite(STDERR, "`tmp_supporter`.`AddressLine1` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['Town'])=='') {
            fwrite(STDERR, "`tmp_supporter`.`Town` is compulsory - $ccc - {$c['ClientRef']}\n");
            exit (103);
        }
        if (str_replace(' ','',$c['Postcode'])=='') {
            fwrite(STDERR, "`tmp_supporter`.`Postcode` is compulsory - $ccc - {$c['ClientRef']}\n");
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
    if ($check->fetch_assoc()) {
        fwrite (STDERR,"Client reference ".$c['ClientRef']." contains illegal characters\n");
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
    if ($check->fetch_assoc()) {
        fwrite (STDERR,"Client reference ".$c['ClientRef']." contains the reserved character sequence '$splitter' \n");
        exit (109);
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (110);
}

$phonere='^\\\\+?[0-9]+$';
$qs = "
  SELECT
    `Mobile`
  FROM `tmp_supporter`
  WHERE REPLACE(`Mobile`,' ','') NOT REGEXP '$phonere'
    AND REPLACE(`Mobile`,' ','')!=''
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->fetch_assoc()) {
        fwrite (STDERR,"Mobile number ".$c['Mobile']." is illegal\n");
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
    `Telephone`
  FROM `tmp_supporter`
  WHERE REPLACE(REPLACE(`Telephone`,'-',''),' ','') NOT REGEXP '$phonere'
    AND REPLACE(REPLACE(`Telephone`,'-',''),' ','')!=''
  LIMIT 0,1
  ;
";
try {
    $check = $zo->query ($qs);
    if ($check->fetch_assoc()) {
        fwrite(STDERR, "Telephone number ".$c['Telephone']." is illegal\n");
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
    $new = $zo->query ($qs);
    $count      = 0;
    while ($s=$new->fetch_assoc()) {
        $cd     = esc ($s['Nominated']);
        $sg     = esc ($s['Signed']);
        $ap     = esc ($s['Approved']);
        $cv     = esc ($s['CanvasRef']);
        $cr     = esc ($s['ClientRef']);
        $tt     = esc ($s['Title']);
        $nf     = esc ($s['FirstName']);
        $nl     = esc ($s['LastName']);
        $em     = esc ($s['Email']);
        $mb     = esc ($s['Mobile']);
        $tl     = esc (str_replace('-',' ',$s['Telephone']));
        $a1     = esc ($s['AddressLine1']);
        $a2     = esc ($s['AddressLine2']);
        $a3     = esc ($s['AddressLine3']);
        $tn     = esc ($s['Town']);
        $cn     = esc ($s['County']);
        $pc     = esc ($s['Postcode']);
        $cy     = esc ($s['Country']);
        for ($i=0;$i<10;$i++) {
            ${'p'.$i} = esc ($s['P'.$i]);
        }
        echo "INSERT INTO `blotto_supporter` (`created`,`signed`,`approved`,`canvas_code`,`canvas_ref`,`client_ref`) VALUES\n";
        echo "  ('$cd','$sg','$ap','$ccc','$cv','$cr');\n";
        echo "SET @sid = LAST_INSERT_ID();\n";
        echo "INSERT INTO `blotto_player` (`supporter_id`,`client_ref`) VALUES\n";
        echo "  (@sid,'$cr');\n\n";
        // First contact should not be `created` = timestamp of when this script runs
        // Rather it should be `created` = when supporter is notionally created
        echo "INSERT INTO `blotto_contact` (`created`,`supporter_id`,`title`,`name_first`,`name_last`,`email`,`mobile`,`telephone`,`address_1`,`address_2`,`address_3`,`town`,`county`,`postcode`,`country`,`p0`,`p1`,`p2`,`p3`,`p4`,`p5`,`p6`,`p7`,`p8`,`p9`) VALUES\n";
        echo "  ('$cd 03:00:00',@sid,'$tt','$nf','$nl','$em','$mb','$tl','$a1','$a2','$a3','$tn','$cn','$pc','$cy','$p0','$p1','$p2','$p3','$p4','$p5','$p6','$p7','$p8','$p9');\n\n";
        $count++;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (117);
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
        fwrite (STDERR,"$rows of data found for $ccc\n");
        if ($rows<5) {
            fwrite (STDERR,"WARNING: very little data was found for $ccc\n");
        }
    }
    catch (\mysqli_sql_exception $e) {
        fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
        exit (118);
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


