<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$orgid = BLOTTO_ORG_ID;
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


echo "\n-- Create `UpdatesLatest` --\n";
$pref_str = "";
foreach ($prefs as $i=>$legend) {
    $pref_str .= "   ,GROUP_CONCAT(`u`.`$legend` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `$legend`\n";
}
echo "
  CREATE OR REPLACE VIEW `UpdatesLatest` AS
  SELECT
    MAX(`u`.`updated`) AS `updated`
   ,`u`.`supporter_id` AS `sort2_supporter_id`
   ,'' AS `unused_1`
   ,'' AS `unused_2`
   ,'' AS `unused_3`
    -- group_concat() trick to get the data from the latest row
    -- usually one milestone per updated value but when more, use milestone_date as a tie-breaker
   ,GROUP_CONCAT(`u`.`signed` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `sort1_signed`
   ,GROUP_CONCAT(`u`.`created` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `created`
   ,GROUP_CONCAT(`u`.`cancelled` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `cancelled`
   ,GROUP_CONCAT(`u`.`ccc` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `ccc`
   ,GROUP_CONCAT(`u`.`canvas_ref` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `canvas_ref`
   ,GROUP_CONCAT(`u`.`client_ref_orig` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `client_ref_orig`
   ,GROUP_CONCAT(`u`.`client_ref` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `client_ref`
   ,GROUP_CONCAT(`u`.`tickets` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `tickets`
   ,GROUP_CONCAT(`u`.`ticket_numbers` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `ticket_numbers`
   ,GROUP_CONCAT(`u`.`title` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `title`
   ,GROUP_CONCAT(`u`.`name_first` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `name_first`
   ,GROUP_CONCAT(`u`.`name_last` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `name_last`
   ,GROUP_CONCAT(`u`.`email` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `email`
   ,GROUP_CONCAT(`u`.`mobile` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `mobile`
   ,GROUP_CONCAT(`u`.`telephone` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `telephone`
   ,GROUP_CONCAT(`u`.`address_1` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `address_1`
   ,GROUP_CONCAT(`u`.`address_2` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `address_2`
   ,GROUP_CONCAT(`u`.`address_3` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `address_3`
   ,GROUP_CONCAT(`u`.`town` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `town`
   ,GROUP_CONCAT(`u`.`county` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `county`
   ,GROUP_CONCAT(`u`.`postcode` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `postcode`
   ,GROUP_CONCAT(`u`.`dob` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `dob`
$pref_str
   ,GROUP_CONCAT(`u`.`first_collected` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `first_collected`
   ,GROUP_CONCAT(`u`.`last_collected` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `last_collected`
   ,GROUP_CONCAT(`u`.`first_draw` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `first_draw`
   ,GROUP_CONCAT(`u`.`death_reported` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `death_reported`
   ,GROUP_CONCAT(`u`.`death_by_suicide` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `death_by_suicide`
   ,GROUP_CONCAT(`u`.`mandate_status` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `mandate_status`
   ,GROUP_CONCAT(`u`.`collection_frequency` ORDER BY `u`.`updated` DESC, `u`.`milestone_date` DESC LIMIT 1) AS `collection_frequency`
  FROM `Updates` AS `u`
  GROUP BY `supporter_id`
  ORDER BY `signed`,`supporter_id`
  ;
";

