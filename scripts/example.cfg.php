<?php

// DEMO
define ( 'BLOTTO_DEMO',          true );

// Global config
define ( 'BLOTTO_FILE_CFG',      '/home/blotto/config.global.php'      );
if (is_readable(BLOTTO_FILE_CFG)) {
    require BLOTTO_FILE_CFG;
}

// Bogons
define ( 'BLOTTO_FILE_BOGONS',   __DIR__.'/abc.cfg.bogons.php'  );
if (is_readable(BLOTTO_FILE_BOGONS)) {
    require BLOTTO_FILE_BOGONS;
}

// Bespoke functions
if (!function_exists('chances')) {
    define ( 'BLOTTO_BESPOKE_FUNC',     __DIR__.'/abc.bespoke.php'      );
    require BLOTTO_BESPOKE_FUNC;
}

// Bespoke SQL
define ( 'BLOTTO_BESPOKE_SQL_FNC',  __DIR__.'/abc.bespoke.functions.sql' );
//define ( 'BLOTTO_BESPOKE_SQL_PRM',  __DIR__.'/abc.bespoke.perms.sql'  );
//define ( 'BLOTTO_BESPOKE_SQL_UPD',  __DIR__.'/abc.bespoke.updates.sql' );

// Org
define ( 'BLOTTO_ORG_NAME',      'Your Organisation'                    );
define ( 'BLOTTO_ORG_ID',        8                   );
define ( 'BLOTTO_ORG_USER',      'abc'               );
define ( 'BLOTTO_GAME_NAME',     'Your Lottery'                         );
define ( 'BLOTTO_MAKE_DB',       'mylotto_abc_make'  );
define ( 'BLOTTO_DUMP_FILE',     '/home/blotto/export/abc/dump.sql'     );
define ( 'BLOTTO_DB',            'mylotto_abc'       );
define ( 'BLOTTO_BELL',          ''                  );
define ( 'BLOTTO_DIR_EXPORT',    '/home/blotto/export/abc'              );
define ( 'BLOTTO_CSV_DIR_S',     '/home/sct/blotto/abc/supporters'      );
define ( 'BLOTTO_CSV_DIR_M',     '/home/sct/blotto/abc/mandates'        );
define ( 'BLOTTO_CSV_DIR_C',     '/home/sct/blotto/abc/collections'     );
define ( 'BLOTTO_LOG_DIR',       '/home/blotto/log/abc'                 );
define ( 'BLOTTO_LOG_SEARCH_SQL',false                                  );
define ( 'BLOTTO_TICKET_DB',     'blotto_ticket_demo'                   );
define ( 'BLOTTO_TICKET_MIN',    '000000'            );
define ( 'BLOTTO_TICKET_MAX',    '999999'            );
define ( 'BLOTTO_TICKET_CHKSUM', 'https://some.where/sum.txt'           );
define ( 'BLOTTO_RESULTS_DB',    BLOTTO_MAKE_DB      );
define ( 'BLOTTO_PROOF_DIR',     __DIR__.'/../export/abc/proof'         );
define ( 'BLOTTO_OUTFILE',       '/tmp/blotto.abc.outfile.csv'          );
define ( 'BLOTTO_TICKET_PRICE',  100                 );  // In pennies
define ( 'BLOTTO_TICKETS_AUTO',  true                );
define ( 'BLOTTO_WIN_FIRST',     '2016-01-01'        );  // Only report wins/reconciles on or after this date
define ( 'BLOTTO_DAY_FIRST',     '2016-01-01'        );  // First date due day
define ( 'BLOTTO_DAY_LAST',      null                );  // Last date due day
//define ( 'BLOTTO_WIN_FIRST',     '2016-01-01'        );  // Only report wins/reconciliations on or after this date
define ( 'BLOTTO_WEEK_ENDED',    5                   );  // Reports up to and including Friday
define ( 'BLOTTO_DRAW_CLOSE_1',  '2016-07-08'        );  // First draw close for the game
define ( 'BLOTTO_CC_NOTIFY',     '10 MONTH'          );  // MySQL interval notify CC of ticket changes
// BLOTTO_PAY_DELAY and rsm-api's RSM_PAY_INTERVAL are both needed
define ( 'BLOTTO_PAY_DELAY',     'P2D'               );  // From DateDue to credit "available" (DateInterval)

define ( 'BLOTTO_CREF_MATCH',    '^[A-z0-9_]+$'      );  // Original supporter reference restriction
define ( 'BLOTTO_CREF_SPLITTER', '-'                 );  // Split mandate detection character sequence
define ( 'BLOTTO_NO_CLAWBACK',   3                   );  // Used by early ticket reduction reporting
define ( 'BLOTTO_CANCEL_RULE',   '2 MONTH'           );  // Used to define when a supporter has "cancelled"

define ( 'BLOTTO_PAY_API_CLASS',    '/some/path/to/rsm-api/PayApi.php'              );
define ( 'RSM_USER',                '***_rsm_api'                                   );
define ( 'RSM_PASSWORD',            '**********'                                    );
define ( 'RSM_ERROR_LOG',           false                                           );
define ( 'RSM_FILE_DEBOGON',        __DIR__.'/***.cfg.bogons.rsm.sql'               );


