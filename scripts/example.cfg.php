<?php

// Global config
define ( 'BLOTTO_FILE_CFG',         '/home/blotto/config.global.php'    );
if (is_readable(BLOTTO_FILE_CFG)) {
    require BLOTTO_FILE_CFG;
}

// Bogons
define ( 'BLOTTO_FILE_BOGONS',      __DIR__.'/ylh.cfg.bogons.php'       );
if (is_readable(BLOTTO_FILE_BOGONS)) {
    require BLOTTO_FILE_BOGONS;
}

// Bespoke functions
if (!function_exists('chances')) {
    define ( 'BLOTTO_BESPOKE_FUNC', __DIR__.'/ylh.bespoke.php'          );
    require BLOTTO_BESPOKE_FUNC;
}

// Bespoke SQL
define ( 'BLOTTO_BESPOKE_SQL_FNC',  __DIR__.'/ylh.bespoke.functions.sql' );
//define ( 'BLOTTO_BESPOKE_SQL_PRM',  __DIR__.'/ylh.bespoke.perms.sql'  );
//define ( 'BLOTTO_BESPOKE_SQL_UPD',  __DIR__.'/ylh.bespoke.updates.sql' );

// Org
define ( 'BLOTTO_ORG_NAME',         'Your Organisation'                 );
define ( 'BLOTTO_ORG_ID',           8                   );
define ( 'BLOTTO_ORG_USER',         'ylh'               );
define ( 'BLOTTO_GAME_NAME',        'Your Local Hospice'                );
define ( 'BLOTTO_MAKE_DB',          'mylotto_ylh_make'  );
define ( 'BLOTTO_DUMP_FILE',        '/home/blotto/export/ylh/dump.sql'  );
define ( 'BLOTTO_DB',               'mylotto_ylh'       );
define ( 'BLOTTO_BELL',             ''                  );
define ( 'BLOTTO_DIR_EXPORT',       '/home/blotto/export/ylh'           );
define ( 'BLOTTO_DIR_INVOICE',      '/home/blotto/invoice/ylh'          );
define ( 'BLOTTO_DIR_STATEMENT',    '/home/blotto/statement/ylh'        );
define ( 'BLOTTO_DIR_DRAW',         '/home/blotto/draw/ylh'             );
define ( 'BLOTTO_CSV_DIR_S',        '/home/sct/blotto/ylh/supporters'   );
define ( 'BLOTTO_CSV_DIR_M',        '/home/sct/blotto/ylh/mandates'     );
define ( 'BLOTTO_CSV_DIR_C',        '/home/sct/blotto/ylh/collections'  );
define ( 'BLOTTO_LOG_DIR',          '/home/blotto/log/ylh'              );
define ( 'BLOTTO_LOG_SEARCH_SQL'   ,false                               );
define ( 'BLOTTO_TICKET_DB',        'blotto_ticket_demo'                );
define ( 'BLOTTO_TICKET_MIN',       '000000'            );
define ( 'BLOTTO_TICKET_MAX',       '999999'            );
define ( 'BLOTTO_TICKET_CHKSUM',    'https://some.where/sum.txt'        );
define ( 'BLOTTO_RESULTS_DB',       BLOTTO_MAKE_DB      );
define ( 'BLOTTO_PROOF_DIR',        __DIR__.'/../export/ylh/proof'      );
define ( 'BLOTTO_OUTFILE',          '/tmp/blotto.ylh.outfile.csv'       );
define ( 'BLOTTO_TICKET_PRICE',     100                 );  // In pennies
define ( 'BLOTTO_TICKETS_AUTO',     true                );
define ( 'BLOTTO_WIN_FIRST',        '2016-01-01'        );  // Only report wins/reconciles on or after this date
define ( 'BLOTTO_INVOICE_FIRST',    '2021-05-07'        );  // Only generate invoices on or after this date
define ( 'BLOTTO_DAY_FIRST',        '2016-01-01'        );  // First date due day
define ( 'BLOTTO_DAY_LAST',         null                );  // Last date due day
//define ( 'BLOTTO_WIN_FIRST',        '2016-01-01'        );  // Only report wins/reconciliations on or after this date
define ( 'BLOTTO_WEEK_ENDED',       5                   );  // Reports up to and including Friday
define ( 'BLOTTO_DRAW_CLOSE_1',     '2016-07-08'        );  // First draw close for the game
define ( 'BLOTTO_DRAW_EMAIL',       'ylh-invoicing@thefundraisingfoundry.com' );
define ( 'BLOTTO_INSURE',           true                );  // Use insurance
define ( 'BLOTTO_INSURE_DAYS',      0                   );  // Days before draw close to close insurance
define ( 'BLOTTO_INSURE_FROM',      '2001-01-01'        );  // First draw requiring insurance (advance if insurance introduced mid-game)
define ( 'BLOTTO_CC_NOTIFY',        '10 MONTH'          );  // MySQL interval notify CC of ticket changes
// BLOTTO_PAY_DELAY and rsm-api's RSM_PAY_INTERVAL are both needed
define ( 'BLOTTO_PAY_DELAY',        'P2D'               );  // From DateDue to credit "available" (DateInterval)
// First collection date calculation
/*
    1 day to collect created mandate from DD provider
    1 day for printing and dispatch
    2 days in case its a weekend
    2 days for 2nd class delivery
*/
define ( 'BLOTTO_DD_COOL_OFF_DAYS', 'P16D'              );  // Plus 10 days for buyer's regret (this is a DateInterval)


define ( 'BLOTTO_CREF_MATCH',       '^[A-z0-9_]+$'      );  // Original supporter reference restriction
define ( 'BLOTTO_CREF_SPLITTER',    '-'                 );  // Split mandate detection character sequence
define ( 'BLOTTO_CANCEL_RULE',      '2 MONTH'           );  // Used to define when a supporter has "cancelled"
define ( 'BLOTTO_CANCEL_LEGACY',    0                   );  // 1 = use BLOTTO_CANCEL_RULE even if DD cancelled


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
define ( 'PAYPAL_EMAIL',            'paypal.account@my.domain'              );
define ( 'PAYPAL_ERROR_LOG',        false       );
define ( 'PAYPAL_REFNO_OFFSET',     200000000   );
define ( 'PAYPAL_DEV_MODE',         true        );

// Organisation - Stripe
define ( 'BLOTTO_PAY_API_STRIPE',           '/path/to/stripe-api/PayApi.php' );
define ( 'BLOTTO_PAY_API_STRIPE_CLASS',     '\Blotto\Stripe\PayApi'         );
define ( 'STRIPE_INIT_FILE',        '/path/to/stripe-php-7.77.0/init.php'   );
define ( 'STRIPE_ERROR_LOG',        false       );
define ( 'STRIPE_REFNO_OFFSET',     100000000   );
define ( 'STRIPE_DESCRIPTION',      'My Org Lottery'      );
define ( 'STRIPE_SECRET_KEY',       ''          );
define ( 'STRIPE_PUBLIC_KEY',       ''          );
define ( 'STRIPE_DEV_MODE',         true        );

*/

// Organisation - all payment providers
define ( 'BLOTTO_DEV_MODE',             false       ); // Dummy online payment form population
define ( 'BLOTTO_DEV_PAY_FREEZE',       false       ); // Leave blotto_build_* alone
define ( 'CAMPAIGN_MONITOR',            '/path/to/createsend-php/csrest_transactional_smartemail.php' );
define ( 'CAMPAIGN_MONITOR_TIMELINE',   '/path/to/createsend-php/csrest_transactional_timeline.php' );
define ( 'DATA8_USERNAME',              ''          );
define ( 'DATA8_PASSWORD',              ''          );
define ( 'DATA8_COUNTRY',               'GB'        );
define ( 'VOODOOSMS',                   '/home/blotto/voodoosms/SMS.class.php' );
define ( 'BLOTTO_SELFEX_EMAIL',         'SelfExclusionYLH@MyHelpDomain.com' );


// Email
define ( 'BLOTTO_ANL_EMAIL',            false           );
define ( 'BLOTTO_ANL_EMAIL_FROM',       '2023-01-25'    );

// Campaign Monitor
define ( 'BLOTTO_EMAIL_API_CAMPAIGNMONITOR', '/opt/campaignmonitor-api/CampaignMonitor.php'  );


// Snailmail
define ( 'BLOTTO_SNAILMAIL',            false           );
define ( 'BLOTTO_SNAILMAIL_PREFIX',     'YLH'           );
define ( 'BLOTTO_SNAILMAIL_TPL_ANL',    123             );
define ( 'BLOTTO_SNAILMAIL_TPL_WIN',    123             );
define ( 'BLOTTO_SNAILMAIL_FROM_ANL',   '2021-10-25'    );
define ( 'BLOTTO_SNAILMAIL_FROM_WIN',   '2021-10-25'    );

// Stannp snailmail service
define ( 'BLOTTO_SNAILMAIL_API_STANNP',         '/opt/stannp-api/Stannp.php'     );
define ( 'BLOTTO_SNAILMAIL_API_STANNP_CLASS',   '\Whitelamp\Stannp'        );
define ( 'STANNP_ERROR_LOG',            true            );


// Fees
define ( 'BLOTTO_FEE_EMAIL',      'ylh-invoicing@thefundraisingfoundry.com' );
define ( 'BLOTTO_FEE_LOADING',    '50:500,100:450,150:400,200:300,250' );
define ( 'BLOTTO_FEE_ANL',        80                );
define ( 'BLOTTO_FEE_ANL_EMAIL',  0                 );
define ( 'BLOTTO_FEE_ANL_SMS',    10                );
define ( 'BLOTTO_FEE_WL',         80                );
define ( 'BLOTTO_FEE_CM',         1131              );
define ( 'BLOTTO_FEE_ADMIN',      4500              );
define ( 'BLOTTO_FEE_MANAGE',     7                 );
define ( 'BLOTTO_FEE_INSURE',     7                 );

