<?php


define ( 'BLOTTO_UN',               '****'              );
define ( 'BLOTTO_PW',               '********'          );
define ( 'BLOTTO_ADMIN_USER',       'blotto'            );
define ( 'BLOTTO_ANONYMISER_DB',    'blotto_anonymiser' );
define ( 'BLOTTO_CONFIG_DB',        'blotto_config'     );
define ( 'BLOTTO_UPLOAD_MAX_MB',    4                   );
define ( 'BLOTTO_ROWS_PER_QRY',     1000                );
define ( 'BLOTTO_BOGONS_MAX',       2000                );
define ( 'MYSQL_CODE_DUPLICATE',    1062                );
define ( 'BLOTTO_TITLES',           'Baron,Baroness,Canon,Cllr,Br,Brother,Dame,Doctor,Dr,Father,Fr,Lady,Lord,Major,Miss,Mr,Mrs,Ms,Mx,Pr,Prof,Professor,Rabbi,Rev,Reverend,Sir,Sr,Sra' );
define ( 'BLOTTO_TITLES_WEB',       'Mrs,Mr,Miss,Ms,Mx,Dr,Prof,Rev,Sir,Dame,Lady');
define ( 'BLOTTO_MALE_TITLES',      'Baron,Br,Brother,Father,Fr,Lord,Mr,Sir,Sr' );
define ( 'BLOTTO_FEMALE_TITLES',    'Baroness,Dame,Lady,Miss,Mrs,Ms,Sra' );
define ( 'BLOTTO_EXEC_LAST_FILE',   'ls -tp "{{DIR}}" | grep -v /$ | head -1'   );
define ( 'BLOTTO_LOG_DURN_DAYS',    14                  );
define ( 'BLOTTO_MC_NAME',          'mymachine'         );
define ( 'BLOTTO_EMAIL_FROM',       'mymachine@mydomain'                        );
define ( 'BLOTTO_EMAIL_WARN_ON',    true                );
define ( 'BLOTTO_EMAIL_WARN_TO',    'techsupport@mysite.co.uk'                  );
define ( 'BLOTTO_EMAIL_BACS_TO',    'bacs@mysite.co.uk'                         );
define ( 'BLOTTO_EMAIL_TO',         'support@mysite.co.uk'                      );
define ( 'BLOTTO_EMAIL_WINS_ON',    true                );
define ( 'BLOTTO_MYSQLDUMP_AUTH',   '/root/mysqldump.auth'                      );
define ( 'BLOTTO_LOG_DAYS',         30                                          );

define ( 'BLOTTO_TRNG_API',         'random.org'                                );
define ( 'BLOTTO_TRNG_API_URL',     'https://api.random.org/json-rpc/2/invoke'  );
define ( 'BLOTTO_TRNG_API_VERSION', '2.0'               );
// This will not get used for the foreseeable future; we are sticking with the old API / licence
//define ( 'BLOTTO_TRNG_API_URL',     'https://api.random.org/json-rpc/4/invoke'  );
//define ( 'BLOTTO_TRNG_API_VERSION', '4.0'               );
define ( 'BLOTTO_TRNG_API_HEADER',  'Content-Type: application/json'            );
define ( 'BLOTTO_TRNG_API_METHOD',  'generateSignedIntegers'                    );
define ( 'BLOTTO_TRNG_API_KEY',     'f45a0115-67da-43e4-88aa-93866bb553a6'      );
define ( 'BLOTTO_TRNG_API_TIMEOUT', 30                                          );
define ( 'BLOTTO_TRNG_API_VERIFY',  'https://api.random.org/signatures/form'    );

define ( 'BLOTTO_TSA_URL',          'https://freetsa.org/tsr'                   );
define ( 'BLOTTO_TSA_CERT',         'https://freetsa.org/files/tsa.crt'         );
define ( 'BLOTTO_TSA_CACERT',       'https://freetsa.org/files/cacert.pem'      );

define ( 'BLOTTO_BRAND',            'mylotto'           );

define ( 'BLOTTO_SEARCH_LEN_MIN',   3               ); // at least one search term must be this long
define ( 'BLOTTO_SEARCH_CREF_MIN',  4               ); // CREF terms not matched if not this long
define ( 'BLOTTO_SEARCH_LIMIT',     20              ); // maximum number of front end results
define ( 'BLOTTO_CSV_FORCE_DELIM',  'BLOTTO '       ); // see csv()
define ( 'BLOTTO_CSV_DELIMITER',    ','             ); // see download_csv()
define ( 'BLOTTO_CSV_ENCLOSER',     '"'             ); // see download_csv()
define ( 'BLOTTO_CSV_ESCAPER',      "\\"            ); // see download_csv()
define ( 'BLOTTO_VERIFY_INTERVAL',  '15 MINUTE'     ); // time to use a verification code
define ( 'BLOTTO_NONCE_MINUTES',    15              ); // duration of a nonce value

define ( 'BLOTTO_BANK_NAME',        'My Lottery Account'    );
define ( 'BLOTTO_BANK_SORT',        '01-02-03'              );
define ( 'BLOTTO_BANK_ACNR',        '12345678'              );
define ( 'BLOTTO_CURRENCY',         '£'             ); // GBP, £, USD, etc
define ( 'BLOTTO_TAX',              0.20            ); // Sales tax as a decimal
define ( 'BLOTTO_TAX_REF',          'VAT reg nr 389 2652 49' ); // Eg VAT nr


/*

// Global - Paypal
define ( 'PAYPAL_TABLE_MANDATE',    'blotto_build_mandate'      );
define ( 'PAYPAL_TABLE_COLLECTION', 'blotto_build_collection'   );
define ( 'PAYPAL_CALLBACK_TO',      30          ); // Confirmation time-out

// Global - RSM
define ( 'RSM_URL',                 'https://rsm5.rsmsecure.com/ddcm/ddcmApi.php'   );
define ( 'RSM_PAY_INTERVAL',        '2 DAY' ); // Ignore recent collections - see BACS behaviour
define ( 'RSM_TABLE_MANDATE',       'blotto_build_mandate'      );
define ( 'RSM_TABLE_COLLECTION',    'blotto_build_collection'   );
define ( 'RSM_FLC_TO_PERIODS',      '1:Monthly,3:Quarterly,6:Six Monthly,12:Annually' );



// Global - Stripe
define ( 'STRIPE_TABLE_MANDATE',    'blotto_build_mandate'      );
define ( 'STRIPE_TABLE_COLLECTION', 'blotto_build_collection'   );
define ( 'STRIPE_CALLBACK_IPS_URL', 'https://stripe.com/files/ips/ips_webhooks.json' );
define ( 'STRIPE_CALLBACK_IPS_TO',  30          ); // seconds before giving up getting safe IPs

// Global - all payment providers
define ( 'DATA8_EMAIL_LEVEL',       'MX'        );
define ( 'VOODOOSMS_DEFAULT_COUNTRY_CODE', 44   );
define ( 'VOODOOSMS_FAIL_STRING',   'Sending SMS failed'        );
define ( 'VOODOOSMS_JSON',          __DIR__.'/voodoosms.cfg.json' );

*/

