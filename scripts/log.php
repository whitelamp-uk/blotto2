<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_CONFIG_DB);
if (!$zo) {
    fwrite (STDERR,"Could not connect to database\n");
    exit (101);
}

$dt = new \DateTime ();
$dt->sub (new \DateInterval('PT1H'));
$dt = $dt->format ('Y-m-d H');

try {
    $qs = "
      SELECT
        CONCAT_WS(' ',`created`,`remote_addr`,`hostname`,`http_host`,`user`,`type`,`status`) AS `line`
      FROM `blotto_log`
      WHERE `created` LIKE '$dt:__:__'
      ORDER BY `id`
    ";
    $lines = $zo->query ($qs);
    while ($line=$lines->fetch_assoc()) {
        echo $line['line']."\n";
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$e->getMessage());
}
