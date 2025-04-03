<?php

/*

RBE game:
 * rules-based-entry mode or reverse-billed-entry mode - whichever you prefer
 * no payment processing or balance information
 * no CRM data accepted or processed
 * participating orgs can be running either standard or RBE games
 * draw entries are added by participating org games calling the RBE's API service
 * ticket fees are reverse-billed to participating orgs

 * participating orgs' supporters, from the perspective of this game:
   - are only stored by their code references except:
     * name and postal address for winners only
   - pay nothing for their tickets
   - are only able to gain entry to the game via their participating org

 * provides to this RBE game owners users (just like any other game does for *its* users):
   - draw reports 
   - lottery payout invoices
   - lottery game invoices with relevant fees only (eg ANL fees always zero)
   - front end relevant summary graphs and reconciliation data (eg sales funnel is always empty)

 * API server provides methods for each participating game to interact with a shared upstream RBE game

   - batch draw entry creation; use crefs eg DBH-1029 (supporter ID) for uniqueness across orgs
     [ so API must store CCC against each API key to correctly create crefs ]

     * ticketsInsertIgnore ( '2025-06-06', [ [ 1029, 2 ], [ 1030, 1 ], ... ] )
     * return array [
           [ 1029, [ '123456', '234567' ] ],
           [ 1030, [ '654321' ] ],
           ...
       ] // participating game maybe should not store upstream ticket numbers

   - list winner refs and prizes

     * winners ( '2025-06-06' )
     * return [
           [ 1029, '123456', '3rd prize £10', 10.00, 'delivered' ],
           [ 1047, '987654', '4th prize £5', 5.00, 'local_delivery' ],
           ...
       ] // participating game maybe should not store returned winners

   - submit blotto_contact fields relevant to posted letters

     * winnerAddress ( 1029, [ "name_last"=>"Bonnechance", "name_last"=>"Bob", ... ] )
     * return 'pending' // or false if it failed

   - get undelivered winner letters

     * winnersAllUndelivered ( )
     * return array [
           [ 1047, '987654', '4th prize £5', 5.00, 'local_delivery' ],
           ...
       ] // participating game maybe should not store upstream letter statuses

*/

define ( 'BLOTTO_RBE',           true               );


// dev mode
define ( 'BLOTTO_DEV_MODE',     false               ); // dummy online payment form population


// global config
define ( 'BLOTTO_FILE_CFG',      '/home/blotto/config.global.php' );
if (is_readable(BLOTTO_FILE_CFG)) {
    require BLOTTO_FILE_CFG;
}

// bespoke functions
define ( 'BLOTTO_BESPOKE_FUNC',     __DIR__.'/rgo.bespoke.php' );
require BLOTTO_BESPOKE_FUNC;
define ( 'BLOTTO_BESPOKE_SQL_FNC',  __DIR__.'/rgo.bespoke.functions.sql' );
define ( 'BLOTTO_BESPOKE_SQL_PRM',  __DIR__.'/rgo.bespoke.perms.sql' );
//define ( 'BLOTTO_BESPOKE_SQL_UPD', __DIR__.'/rgo.bespoke.updates.sql' );


// org
define ( 'BLOTTO_ORG_ID',           0                );
define ( 'BLOTTO_ORG_USER',         'rgo'           );
define ( 'BLOTTO_ORG_NAME',         'Your organisation (RBE Game Owner)' );
define ( 'BLOTTO_GAME_NAME',        'Your RBE Lottery Game' );
define ( 'BLOTTO_MAKE_DB',          'blotto2_rgo_make'                 );
define ( 'BLOTTO_DUMP_FILE',        '/home/blotto/export/rgo/dump.sql' );
define ( 'BLOTTO_DB',               'blotto2_rgo'                      );
define ( 'BLOTTO_DIR_EXPORT',       '/home/blotto/export/rgo'          );
define ( 'BLOTTO_DIR_INVOICE',      '/home/blotto/invoice/rgo'         );
define ( 'BLOTTO_DIR_STATEMENT',        '/home/blotto/statement/rgo'   );
define ( 'BLOTTO_DIR_DRAW',         '/home/blotto/draw/rgo'            );
define ( 'BLOTTO_LOG_DIR',          '/home/blotto/log/rgo'             );
define ( 'BLOTTO_TICKET_DB',        BLOTTO_MAKE_DB  );
define ( 'BLOTTO_TICKET_MIN',       '000000'        );
define ( 'BLOTTO_TICKET_MAX',       '999999'        );
define ( 'BLOTTO_RESULTS_DB',       BLOTTO_MAKE_DB  );
define ( 'BLOTTO_PROOF_DIR',        __DIR__.'/../export/rgo/proof'     );
define ( 'BLOTTO_OUTFILE',          '/tmp/blotto.rgo.outfile.csv'      );
define ( 'BLOTTO_TICKET_PRICE',     4               );  // in pennies
define ( 'BLOTTO_TICKETS_AUTO',     false           );
define ( 'BLOTTO_WIN_FIRST',        '2000-01-01'    );  // compulsory constant, always 2001-01-01 except for DBH and SHC
define ( 'BLOTTO_INVOICE_FIRST',    '2000-01-01'    );  // only generate invoices on or after this date
define ( 'BLOTTO_WEEK_ENDED',   5                   );  // reports up to and including Friday
define ( 'BLOTTO_DRAW_CLOSE_1',     '2020-09-11'    );  // first draw close for the game
define ( 'BLOTTO_DRAW_EMAIL',       'rgo-invoicing@thefundraisingfoundry.com' );
define ( 'BLOTTO_BELL',             ''              );
// RBE crefs need no upstream (BACS) rules but consistency with standard games seems sensible:
define ( 'BLOTTO_CREF_MATCH',       '^[A-z0-9_]+$'  );  
define ( 'BLOTTO_CREF_SPLITTER',    '-'             );  // split mandate detection character sequence
define ( 'BLOTTO_INSURE',           true            );  // use insurance
define ( 'BLOTTO_INSURE_DAYS',      0               );  // days before draw close to close insurance
define ( 'BLOTTO_INSURE_FROM',      '2001-01-01'    );  // first draw requiring insurance (advance if insurance introduced mid-game)
// only to stop breaking SQL instantiation:
define ( 'BLOTTO_CANCEL_RULE',      '0 MONTH'       );
define ( 'BLOTTO_CC_NOTIFY',        '0 MONTH'       ); 


// APIs
define ( 'CAMPAIGN_MONITOR',                    '/path/to/createsend-php/csrest_transactional_smartemail.php' );
define ( 'CAMPAIGN_MONITOR_TIMELINE',           '/path/to/createsend-php/csrest_transactional_timeline.php' );
define ( 'BLOTTO_EMAIL_API_CAMPAIGNMONITOR',    '/opt/campaignmonitor-api/CampaignMonitor.php' );
define ( 'VOODOOSMS',                           '/home/blotto/voodoosms/SMS.class.php' );


// fees
define ( 'BLOTTO_FEE_EMAIL',                    'rgo-invoicing@thefundraisingfoundry.com' );

