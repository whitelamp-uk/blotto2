<?php

define ( 'BLOTTO_UN',               '****'              );
define ( 'BLOTTO_PW',               '********'          );
define ( 'BLOTTO_ADMIN_USER',       'blotto'            );
define ( 'BLOTTO_ADMIN_IPS_CSV',    '31.125.150.68'     ); // Used to restrict certain processes invoked over the web
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
define ( 'BLOTTO_EMAIL_REPORT_DAY', 'Mon'               );
define ( 'BLOTTO_EMAIL_WINS_ON',    true                );
define ( 'BLOTTO_EMAIL_BOUNCE_DELAY', 180               ); // Seconds to sleep before checking ANL email bounces, mostly OK in 3 minutes
define ( 'BLOTTO_POSTCODE_PREG',    '^[A-Z][A-Z]?[0-9][A-Z0-9]?[0-9][A-Z][A-Z]$' );
define ( 'BLOTTO_LOG_DAYS',         30                  );
define ( 'BLOTTO_DIGEST',           '/var/log/blotto/digest.log'                );
define ( 'BLOTTO_TMP_DIR',          '/tmp'              );

// Postcodes are processed on the assumption they are UK - so territory options
// are one or more of UK (default), GB, BT, JE, GY, IM
define ( 'BLOTTO_TERRITORIES_CSV',  'GB'                                        );
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

define ( 'BLOTTO_BRAND',            'mylotto'       );

define ( 'BLOTTO_SEARCH_LEN_MIN',   3               ); // at least one search term must be this long
define ( 'BLOTTO_SEARCH_CREF_MIN',  4               ); // CREF terms not matched if not this long
define ( 'BLOTTO_SEARCH_LIMIT',     20              ); // maximum number of front end results
define ( 'BLOTTO_CSV_FORCE_DELIM',  'BLOTTO '       ); // see csv()
define ( 'BLOTTO_CSV_DELIMITER',    ','             ); // see download_csv()
define ( 'BLOTTO_CSV_ENCLOSER',     '"'             ); // see download_csv()
define ( 'BLOTTO_CSV_ESCAPER',      "\\"            ); // see download_csv()
define ( 'BLOTTO_PREFERENCES_SEP',  '::'            ); // see supporters.php
define ( 'BLOTTO_VERIFY_INTERVAL',  '15 MINUTE'     ); // time to use a verification code
define ( 'BLOTTO_NONCE_MINUTES',    15              ); // duration of a nonce value

define ( 'BLOTTO_ADMINER_URL',      'https://abc.lottery.example/adminer/'      ); // Used to internally email data URLs from CLI processes

define ( 'BLOTTO_BANK_NAME',        'My Lottery Account'    );
define ( 'BLOTTO_BANK_SORT',        '01-02-03'              );
define ( 'BLOTTO_BANK_ACNR',        '12345678'              );
define ( 'BLOTTO_CURRENCY',         '£'             ); // GBP, £, USD, etc
define ( 'BLOTTO_TAX',              0.20            ); // Sales tax as a decimal
define ( 'BLOTTO_TAX_REF',          'VAT reg nr 389 2652 49' ); // Eg VAT nr


// Email providers
define ( 'BLOTTO_EMAIL_API_CAMPAIGNMONITOR_CODE',   'CM'    );


// Campaign Monitor
// This key is for the internal Campaign Monitor account
define ( 'BLOTTO_CM_KEY', '******************************************************************************************************************************************************==' );
define ( 'BLOTTO_EMAIL_API_CAMPAIGNMONITOR',        '/opt/createsend-php/csrest_transactional_smartemail.php'   );
define ( 'BLOTTO_EMAIL_API_CAMPAIGNMONITOR_CLASS',  '\Blotto\CampaignMonitor'                                   );


// Snailmail
define ( 'BLOTTO_SNAILMAIL_COUNTRY',    'GB'                );
define ( 'BLOTTO_SNAILMAIL_RM_FIELDS',  'email,mobile,telephone'                    );


// Stannp snail-mail service

// Deprecated
define ( 'BLOTTO_STANNP',           '/home/blotto/stannp/Stannp.class.php'      );
define ( 'BLOTTO_STANNP_COUNTRY',   'GB'        );
define ( 'BLOTTO_STANNP_RM_FIELDS', 'email,mobile,telephone'                    );

define ( 'STANNP_TIMEOUT',          60          );
// Minimum length of left-match to campaign names for redaction limiting
define ( 'STANNP_REDACT_SCOPE_LEN', 4           );


/*


// WEB PAYMENT PROVIDERS

// Global - Paypal
define ( 'PAYPAL_CODE',             'PYPL'      ); // CCC and Provider
define ( 'PAYPAL_DD',               false       ); // Does not offer direct debit
define ( 'PAYPAL_TABLE_MANDATE',    'blotto_build_mandate'      );
define ( 'PAYPAL_TABLE_COLLECTION', 'blotto_build_collection'   );
define ( 'PAYPAL_CALLBACK_TO',      30          ); // Confirmation time-out

// Global - Stripe
define ( 'STRIPE_CODE',             'STRP'      ); // CCC and Provider
define ( 'STRIPE_DD',               false       ); // Does not offer direct debit
define ( 'STRIPE_TABLE_MANDATE',    'blotto_build_mandate'      );
define ( 'STRIPE_TABLE_COLLECTION', 'blotto_build_collection'   );
define ( 'STRIPE_CALLBACK_IPS_URL', 'https://stripe.com/files/ips/ips_webhooks.json' );
define ( 'STRIPE_CALLBACK_IPS_TO',  30          ); // seconds before giving up getting safe IPs

// Global - Cardnet
define ( 'CARDNET_CODE',             'CDNT'      ); // CCC and Provider
define ( 'CARDNET_DD',               false       ); // Does not offer direct debit
define ( 'CARDNET_TABLE_MANDATE',    'blotto_build_mandate'      );
define ( 'CARDNET_TABLE_COLLECTION', 'blotto_build_collection'   );



// BACS payment providers

// Global - RSM
define ( 'BLOTTO_PAY_API_RSM_SELECT', 'SELECT DISTINCT(`ClientRef`) AS `crf` FROM `rsm_mandate`' );
define ( 'RSM_CODE',                'RSM'       ); // Provider code
define ( 'RSM_DD',                  true        ); // Offers direct debit
define ( 'RSM_BUY',                 false       ); // Does not offer web integration
define ( 'RSM_URL',                 'https://rsm5.rsmsecure.com/ddcm/ddcmApi.php'   );
define ( 'RSM_PAY_INTERVAL',        '2 DAY' ); // Ignore recent collections - see BACS behaviour
define ( 'RSM_TABLE_MANDATE',       'blotto_build_mandate'      );
define ( 'RSM_TABLE_COLLECTION',    'blotto_build_collection'   );

// Global - Paysuite
define ( 'BLOTTO_PAY_API_PST_SELECT', 'SELECT DISTINCT(`ClientRef`) AS `crf` FROM `paysuite_mandate` WHERE LENGTH(IFNULL(`ContractGuid`,""))>0 AND `ContractGuid`!=`ClientRef`' );
define ( 'PST_CODE',                   'PST'                ); // Provider code
define ( 'PST_DD',                  true        ); // Offers direct debit
define ( 'PST_BUY',                 false       ); // Does not offer web integration
define ( 'PST_TABLE_MANDATE',       'blotto_build_mandate'            );
define ( 'PST_TABLE_COLLECTION',    'blotto_build_collection'         );
define ( 'PST_PAY_INTERVAL',        '2 DAY' ); // Ignore recent collections - see BACS behaviour

*/

// Global - all payment providers
define ( 'BLOTTO_DD_TRY_INTERVAL',  '14 DAY'     );
define ( 'DATA8_EMAIL_LEVEL',       'MX'        );
define ( 'VOODOOSMS_DEFAULT_COUNTRY_CODE', 44   );
define ( 'VOODOOSMS_FAIL_STRING',   'Sending SMS failed'        );
define ( 'VOODOOSMS_JSON',          __DIR__.'/voodoosms.cfg.json' );


// Other
define ( 'BLOTTO_CURL_ATTEMPTS', 4              );

