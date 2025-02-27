<?php
// we *could* check if today is a draw_close - but as we need the query anyway that
// takes care of it.
require __DIR__.'/functions.php'; //barely needed - could just connect with mysqli().
cfg ();
require $argv[1];

$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}
// TODO get fancy and copy headers into query string.
$headers = ['entry_urn', 'draw_close_date', 'player_urn', 'ticket_number'];
$q = "SELECT `entry_urn`, `draw_close_date`, `player_urn`, `ticket_number`
  FROM `Insurance` WHERE `draw_close_date` = CURDATE()";

try {
    $result       = $zo->query ($q);
    if ($result->num_rows) {
        $fname = __DIR__.'/../../export/insurance/'.date('Y-m-d').'-'.BLOTTO_ORG_USER.".csv";

        $fp             = fopen ($fname,'w');
        if (!$fp) {
            error_log ('insurance_csv: could not open '.$fname);
            exit (102);
        }
        fputcsv (
            $fp,
            $headers,
            BLOTTO_CSV_DELIMITER,
            BLOTTO_CSV_ENCLOSER,
            BLOTTO_CSV_ESCAPER
        );

        while ($row=$result->fetch_assoc()) {
            fputcsv (
                $fp,
                $row,
                BLOTTO_CSV_DELIMITER,
                BLOTTO_CSV_ENCLOSER,
                BLOTTO_CSV_ESCAPER
            );
        }
        fclose ($fp);
    }

}
catch (\mysqli_sql_exception $e) {
    fclose ($fp);
    error_log ('insurance_csv: sql fail on '.$q);
    error_log ('insurance_csv: '.$e->getMessage());
    exit (103);
}