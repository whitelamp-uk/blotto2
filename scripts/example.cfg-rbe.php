<?php

// Global config
define ( 'BLOTTO_FILE_CFG',      '/home/blotto/config.global.php'       );
if (is_readable(BLOTTO_FILE_CFG)) {
    require BLOTTO_FILE_CFG;
}

// Bespoke functions
define ( 'BLOTTO_BESPOKE_FUNC',   __DIR__.'/abc_def.bespoke.php'        );
require BLOTTO_BESPOKE_FUNC;

// Org
define ( 'BLOTTO_ORG_NAME',      'ABC/DEF shared number-match'          );
define ( 'BLOTTO_ORG_USER',      'sdw'               );
define ( 'BLOTTO_ORG_ID',        0                   );
define ( 'BLOTTO_RBE_DBS',       'mylotto_org1,mylotto_org2'            );
define ( 'BLOTTO_RBE_MAKES',     'mylotto_org1_make,mylotto_org2_make'  );
define ( 'BLOTTO_RBE_ORGS',      '1,2'                                  );
define ( 'BLOTTO_MAKE_DB',       'mylotto_abc_def_make'                 );
define ( 'BLOTTO_DUMP_FILE',     '/home/blotto/export/abc_def/dump.sql' );
define ( 'BLOTTO_DB',            'mylotto_abc_def'                      );
define ( 'BLOTTO_DIR_EXPORT',    '/home/blotto/export/abc_def'          );
define ( 'BLOTTO_LOG_DIR',       '/home/blotto/log/abc_def'             );
define ( 'BLOTTO_TICKET_DB',     'blotto_ticket_zaffo'                  );
define ( 'BLOTTO_TICKET_MIN',    '000000'            );
define ( 'BLOTTO_TICKET_MAX',    '999999'            );
define ( 'BLOTTO_RESULTS_DB',    BLOTTO_MAKE_DB      );
define ( 'BLOTTO_PROOF_DIR',     __DIR__.'/../export/abc_def/proof'     );
define ( 'BLOTTO_OUTFILE',       '/tmp/blotto.abc_def.outfile.csv'      );
define ( 'BLOTTO_TICKET_PRICE',  4                   );  // In pennies
define ( 'BLOTTO_TICKETS_AUTO',  false               );
define ( 'BLOTTO_WIN_FIRST',     '2016-01-01'        );  // Only report wins/reconciles on or after this date
define ( 'BLOTTO_WEEK_ENDED',    5                   );  // Reports up to and including Friday
define ( 'BLOTTO_DRAW_CLOSE_1',  '2020-09-11'        );  // First draw close for the game
define ( 'BLOTTO_BELL',          ''                  );
// Only to stop breaking SQL instantiation
define ( 'BLOTTO_CANCEL_RULE',   '0 MONTH'           );
define ( 'BLOTTO_CC_NOTIFY',     '0 MONTH'           ); 

/*

define ( 'CAMPAIGN_MONITOR',        '/path/to/createsend-php/csrest_transactional_smartemail.php' );

define ( 'VOODOOSMS',               '/home/blotto/voodoosms/SMS.class.php'        );

*/

