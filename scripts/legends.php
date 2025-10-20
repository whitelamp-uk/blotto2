<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$orgid = BLOTTO_ORG_ID;
$split = BLOTTO_CREF_SPLITTER;
$price = BLOTTO_TICKET_PRICE;
$pfz = get_argument('z',$Sw) !== false;
if ($pfz) {
    echo "\n-- PAY FREEZE - leave structure of Updates alone (only Supporters table altered here)\n";
}

echo "\n\nUSE `".BLOTTO_MAKE_DB."`;\n\n\n";


echo "\n-- Alter statements for `Supporters` --\n\n\n";


$zo = connect (BLOTTO_CONFIG_DB);
if (!$zo) {
    exit (101);
}

$qs = "
  SELECT
    *
  FROM `blotto_field`
  WHERE `org_id`=$orgid
  ORDER BY `p_number`
  ;
";

$columns = [];
try {
    $cols = $zo->query ($qs);
    while ($c=$cols->fetch_assoc()) {
        $columns[$c['p_number']] = $c;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (102);
}


$prefs = [];
for ($i=0;$i<10;$i++) {

    if (array_key_exists($i,$columns)) {
        $legend = $columns[$i]['legend'];
        echo "
          ALTER TABLE `Supporters`
          CHANGE COLUMN `p$i` `$legend` VARCHAR(255) CHARACTER SET utf8
          ;
        ";
        if (!$pfz) {
            echo "
              ALTER TABLE `Updates`
              CHANGE COLUMN `p$i` `$legend` VARCHAR(255) CHARACTER SET utf8
              ;
            ";
            echo "
              ALTER TABLE `UpdatesLatest`
              CHANGE COLUMN `p$i` `$legend` VARCHAR(255) CHARACTER SET utf8
              ;
            ";
           $prefs[] = $legend;
        }
        continue;
    }
    echo "
        ALTER TABLE `Supporters`
        DROP COLUMN `p$i`
        ;
    ";
    if (!$pfz) {
        echo "
            ALTER TABLE `Updates`
            DROP COLUMN `p$i`
            ;
        ";
        echo "
            ALTER TABLE `UpdatesLatest`
            DROP COLUMN `p$i`
            ;
        ";
    }
}


echo "\n-- Create `SupportersView` --\n";
echo "
    CREATE OR REPLACE VIEW `SupportersView` 
    AS
    SELECT * FROM `Supporters`
    GROUP BY `supporter_id`
    ;
";


