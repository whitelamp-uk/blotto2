<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];


$zo = connect (BLOTTO_MAKE_DB);
if (!$zo) {
    exit (101);
}

if (!is_dir(BLOTTO_DIR_INVOICE)) {
    fwrite (STDERR,"Invoice directory BLOTTO_DIR_INVOICE='".BLOTTO_DIR_INVOICE."' does not exist\n");
    exit (102);
}



// Draw and payout invoices

$rdb = BLOTTO_RESULTS_DB;
$cdb = BLOTTO_CONFIG_DB;
$first = '0000-00-00';
if (defined('BLOTTO_INVOICE_FIRST') && BLOTTO_INVOICE_FIRST) {
    $first = BLOTTO_INVOICE_FIRST;
}

$qs = "
  SELECT
    `r`.`draw_closed`
   ,`w`.`ticket_number` IS NOT NULL AS `has_winners`
  FROM `$rdb`.`blotto_result` AS `r`
  LEFT JOIN `Wins` AS `w`
         ON `w`.`draw_closed`=`r`.`draw_closed`
  WHERE `r`.`draw_closed`>='$first'
  GROUP BY `r`.`draw_closed`
  ORDER BY `r`.`draw_closed`
  ;
";

$draws = [];
try {
    $ds = $zo->query ($qs);
    while ($d=$ds->fetch_assoc()) {
        $draws[] = $d;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (103);
}

/*
We still generate and keep the HTML files as they are useful to us even if we only 
send PDFs (as of March 2025).  
We render as A3 to get everything on the page.  A4 is not big enough and there is 
no clear scaling mechanism.

MP TODO (1) lack of scaling mechanism seems strange - is there not a standard approach?
Such as:
 * clever stuff with CSS media queries
 * using the right CSS units
 * use bigger PDF dpi
What about a media query for print with font size overrides in pt?
Does DomPDF not recognise CSS very well? 

MP TODO (2) it is nice that both HTML and PDF invoice formats are available via the front end.
It would be nice if the same applied to these:
 * statements
 * draw reports
 * summary graph data tables
There is a question mark over whether we move to a standard policy of always emailing files as
 * PDFs
 * both formats
Obviously the always-HTML option has already been rejected
*/
try {
    foreach ($draws as $draw) {
        // Raise invoice for game services
        $file   = BLOTTO_DIR_INVOICE.'/';
        $file  .= strtolower(BLOTTO_ORG_USER).'_'.$draw['draw_closed'].'_game';
        $pdf    = $file.'.pdf';
        $file  .= '.html';
        if (!file_exists($file)) {
            if ($inv=invoice_game($draw['draw_closed'],false)) {
                file_put_contents($file,$inv);
                //html_file_to_pdf_file($file,$pdf,'a3');
                html_file_to_pdf_file_openapi($file,$pdf);
                if (defined('BLOTTO_FEE_EMAIL') && BLOTTO_FEE_EMAIL) {
                    mail_attachments (
                        BLOTTO_FEE_EMAIL,
                        BLOTTO_BRAND." invoice (game services)",
                        "Game invoice for draw period closing {$draw['draw_closed']}",
                        [$pdf]
                    );
                }
            }
            if (defined('BLOTTO_INVOICE_INSURANCE') && BLOTTO_INVOICE_INSURANCE) {
                $file   = BLOTTO_DIR_INVOICE.'/';
                $file  .= strtolower(BLOTTO_ORG_USER).'_'.$draw['draw_closed'].'_insurance';
                $pdf    = $file.'.pdf';
                $file  .= '.html';
                // insurance invoice "piggy-backs" the game invoice
                // if we (re)build the latter we always (re)build the former
                if (file_exists($file)) {
                    unlink ($file);
                }
                if (file_exists($pdf)) {
                    unlink ($pdf);
                }
                if ($inv=invoice_insurance($draw['draw_closed'],false)) {
                    file_put_contents($file,$inv);
                    //html_file_to_pdf_file($file,$pdf,'a3');
                    html_file_to_pdf_file_openapi($file,$pdf);
                    if (defined('BLOTTO_FEE_EMAIL') && BLOTTO_FEE_EMAIL) {
                        mail_attachments (
                            BLOTTO_FEE_EMAIL,
                            BLOTTO_BRAND." invoice (ticket insurance)",
                            "Ticket insurance invoice for draw period closing {$draw['draw_closed']}",
                            [$pdf]
                        );
                    }
                }
            }
        }
        // Raise invoice (pass on cost) of paying out to winners
        if ($draw['has_winners']) {
            $file   = BLOTTO_DIR_INVOICE.'/';
            $file  .= strtolower(BLOTTO_ORG_USER).'_'.$draw['draw_closed'].'_payout';
            $pdf    = $file.'.pdf';
            $file  .= '.html';
            if (!file_exists($file)) {
                if ($inv=invoice_payout($draw['draw_closed'],false)) {
                    file_put_contents($file,$inv);
                    //html_file_to_pdf_file($file,$pdf,'a3');
                    html_file_to_pdf_file_openapi($file,$pdf);
                    if (defined('BLOTTO_FEE_EMAIL') && BLOTTO_FEE_EMAIL) {
                        mail_attachments (
                            BLOTTO_FEE_EMAIL,
                            BLOTTO_BRAND." invoice (payout of winnings)",
                            "Payout invoice for draw period closing {$draw['draw_closed']}",
                            [$pdf]
                        );
                    }
                }
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,$e->getMessage()."\n");
    exit (104);
}



// Custom invoices

$org_code = strtoupper (BLOTTO_ORG_USER);

$qs = "
  SELECT
    `i`.*
   ,`o`.`invoice_address` AS `address`
  FROM `$cdb`.`blotto_invoice` AS `i`
  JOIN `$cdb`.`blotto_org` AS `o`
    ON `o`.`org_code`=`i`.`org_code`
  WHERE `i`.`raised` IS NOT NULL
    AND `i`.`raised`<=CURDATE()
    AND UPPER(`i`.`org_code`)='$org_code'
  ORDER BY `i`.`id`
  ;
";

$customs = [];
try {
    $is = $zo->query ($qs);
    while ($i=$is->fetch_assoc()) {
        $customs[] = $i;
    }
}
catch (\mysqli_sql_exception $e) {
    fwrite (STDERR,$qs."\n".$e->getMessage()."\n");
    exit (105);
}

try {
    foreach ($customs as $custom) {
        // Raise custom invoice
        $file   = BLOTTO_DIR_INVOICE.'/';
        $file  .= strtolower (
            BLOTTO_ORG_USER.'_'.$custom['raised'].'_'.strtoupper($custom['type'])
        );
        $pdf    = $file.'.pdf';
        $file  .= '.html';
        if (!file_exists($file)) {
            if ($inv=invoice_custom($custom,false)) {
                file_put_contents($file,$inv);
                //html_file_to_pdf_file($file,$pdf,'a3');
                html_file_to_pdf_file_openapi($file,$pdf);
                mail_attachments (
                    BLOTTO_FEE_EMAIL,
                    BLOTTO_BRAND." invoice (custom)",
                    "Custom invoice raised {$custom['raised']}",
                    [$pdf]
                );
            }
        }
    }
}
catch (\Exception $e) {
    fwrite (STDERR,"Failed to create custom invoices without errors\n");
    fwrite (STDERR,$e->getMessage()."\n");
    // Do not abort build for this
}

