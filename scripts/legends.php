<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$orgid = BLOTTO_ORG_ID;


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

$columns = array ();
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

for ($i=0;$i<10;$i++) {

    if (array_key_exists($i,$columns)) {
        $legend = $columns[$i]['legend'];
        echo "
          ALTER TABLE `Supporters`
          CHANGE COLUMN `p$i` `$legend` VARCHAR(255) CHARACTER SET utf8
          ;
        ";
        echo "
          ALTER TABLE `Updates`
          CHANGE COLUMN `p$i` `$legend` VARCHAR(255) CHARACTER SET utf8
          ;
        ";
        continue;
    }
    echo "
        ALTER TABLE `Supporters`
        DROP COLUMN `p$i`
        ;
    ";
    echo "
        ALTER TABLE `Updates`
        DROP COLUMN `p$i`
        ;
    ";
}


