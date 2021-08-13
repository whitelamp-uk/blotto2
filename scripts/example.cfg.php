<?php

// DEMO
define ( 'BLOTTO_CREATE_ANON_DB', false ); // Inefficient to execute if exists
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
define ( 'BLOTTO_DIR_INVOICE',   '/home/blotto/invoice/abc'             );
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
define ( 'BLOTTO_INSURE_DAYS',   1                   );  // Days before draw close to insure entries
define ( 'BLOTTO_CC_NOTIFY',     '10 MONTH'          );  // MySQL interval notify CC of ticket changes
// BLOTTO_PAY_DELAY and rsm-api's RSM_PAY_INTERVAL are both needed
define ( 'BLOTTO_PAY_DELAY',     'P2D'               );  // From DateDue to credit "available" (DateInterval)

define ( 'BLOTTO_CREF_MATCH',    '^[A-z0-9_]+$'      );  // Original supporter reference restriction
define ( 'BLOTTO_CREF_SPLITTER', '-'                 );  // Split mandate detection character sequence
define ( 'BLOTTO_NO_CLAWBACK',   3                   );  // Used by early ticket reduction reporting
define ( 'BLOTTO_CANCEL_RULE',   '2 MONTH'           );  // Used to define when a supporter has "cancelled"


// rsm-api, an RSM payment class
define ( 'BLOTTO_PAY_API_RSM',          '/some/path/to/rsm-api/PayApi.php'  );
define ( 'BLOTTO_PAY_API_RSM_CLASS',    '\Blotto\Rsm\PayApi'                );
define ( 'RSM_USER',                    '***_rsm_api'                       );
define ( 'RSM_PASSWORD',                '**********'                        );
define ( 'RSM_ERROR_LOG',               false                               );
define ( 'RSM_FILE_DEBOGON',            __DIR__.'/***.cfg.bogons.rsm.sql'   );

/*

// Organisation - Paypal
define ( 'BLOTTO_PAY_API_PAYPAL',           '/path/to/paypal-api/PayApi.php' );
define ( 'BLOTTO_PAY_API_PAYPAL_CLASS',     '\Blotto\Paypal\PayApi'         );
define ( 'BLOTTO_PAY_API_PAYPAL_BUY',       true        ); // Provide integration
define ( 'PAYPAL_EMAIL',            'paypal.account@my.domain'              );
define ( 'PAYPAL_CODE',             'PYPL'      ); // CCC and Provider
define ( 'PAYPAL_ERROR_LOG',        false       );
define ( 'PAYPAL_REFNO_OFFSET',     200000000   );
define ( 'PAYPAL_DEV_MODE',         true        );

// Organisation - Stripe
define ( 'BLOTTO_PAY_API_STRIPE',           '/path/to/stripe-api/PayApi.php' );
define ( 'BLOTTO_PAY_API_STRIPE_CLASS',     '\Blotto\Stripe\PayApi'         );
define ( 'BLOTTO_PAY_API_STRIPE_BUY',       true        ); // Provide integration
define ( 'STRIPE_INIT_FILE',        '/path/to/stripe-php-7.77.0/init.php'   );
define ( 'STRIPE_CODE',             'STRP'      ); // CCC and Provider
define ( 'STRIPE_ERROR_LOG',        false       );
define ( 'STRIPE_REFNO_OFFSET',     100000000   );
define ( 'STRIPE_DESCRIPTION',      'My Org Lottery'      );
define ( 'STRIPE_SECRET_KEY',       ''          );
define ( 'STRIPE_PUBLIC_KEY',       ''          );
define ( 'STRIPE_DEV_MODE',         true        );


// Organisation - all payment providers
define ( 'BLOTTO_DEV_MODE',         true        );
define ( 'CAMPAIGN_MONITOR',        '/path/to/createsend-php/csrest_transactional_smartemail.php' );
define ( 'DATA8_USERNAME',          ''          );
define ( 'DATA8_PASSWORD',          ''          );
define ( 'DATA8_COUNTRY',           'GB'        );
define ( 'VOODOOSMS',               '/home/blotto/voodoosms/SMS.class.php' );

*/


// Fees
define ( 'BLOTTO_FEE_LOADING',   '50:500,100:450,150:400,200:300,250' );
define ( 'BLOTTO_FEE_ANL',        80                 );
define ( 'BLOTTO_FEE_WL',         80                 );
define ( 'BLOTTO_FEE_CM',         1131               );
define ( 'BLOTTO_FEE_ADMIN',      4500               );
define ( 'BLOTTO_FEE_MANAGE',     7                  );
define ( 'BLOTTO_FEE_INSURE',     7                  );

