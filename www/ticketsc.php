<?php
session_start();
error_log("---------------------------------------");
// View error log for client e.g. /var/log$ sudo  tail -f /var/log/apache2/bwh-443-error.log

// New card pay form
// example usage for bwh,
//
// <iframe 
//     style="width: 100%"
//     id="crx"
//     src="https://bwh.crucible.burdenandburden.co.uk/ticketsc.php?d=2025-07-03&amp;font=https://www.bwhospitalscharity.org.uk/fresco/clients/aboveandbeyondrebrand/assets/Roc%20Grotesk%20Regular.otf&amp;color1=FFE600&amp;color2=FF4900"
// ></iframe>

// ****** IN PROGRESS SO NO SUBMIT ACTIONS YET ON SMS OR PAY BUTTONS ******

// Include necessary files, including any shared functions
require './bridge.php';  // Assuming this is required
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
if (defined('CAMPAIGN_MONITOR') && CAMPAIGN_MONITOR) {
    require CAMPAIGN_MONITOR;
}
if (defined('VOODOOSMS') && VOODOOSMS) {
    require VOODOOSMS;
}
$e_default = 'Sorry something went wrong - please try later';
$org = org();

// echo "DEV AREA";   // this file is for dev as am testing in site where client can see original url so dev here till ready.

// Set headers for iframe embedding
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests
header('Content-Security-Policy: frame-ancestors ' . BLOTTO_WWW_IFRAME_SOURCES); // Specify allowed iframe sources

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) && $_GET['action'] === 'postcode_lookup' &&
    isset($_GET['postcode'])
) {
    header('Content-Type: application/json; charset=utf-8');

    $postcode = trim($_GET['postcode']);
    $doFind   = isset($_GET['find']) && $_GET['find'] == '1';
    $building = '';

    function GetFullAddress($postcode, $building, $doFind, $licence = 'SmallUserFull')
    {
        $options = array(
            "Option" => array(
                array("Name" => "ReturnResultCount", "Value" => "true"),
                array("Name" => "IncludeAliases", "Value" => "false"),
            ),
        );

        $params = array(
            "username" => DATA8_USERNAME,
            "password" => DATA8_PASSWORD,
            "licence"  => $licence,
            "postcode" => $postcode,
            "building" => $building,
            "options"  => $options,
        );

        try {
            $client = new SoapClient("https://webservices.data-8.co.uk/addresscapture.asmx?WSDL");
            $result = $client->GetFullAddress($params);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'request failed: ' . $e->getMessage()]);
            exit;
        }

        $gfr = $result->GetFullAddressResult ?? null;
        if (!$gfr || (isset($gfr->Status->Success) && (int)$gfr->Status->Success === 0)) {
            $err = isset($gfr->Status->ErrorMessage) ? $gfr->Status->ErrorMessage : 'Unknown error';
            echo json_encode(['success' => false, 'message' => "Error: " . $err]);
            exit;
        }

        if ($doFind === true) {
            echo json_encode(['success' => true, 'message' => 'Postcode exists ok']);
            exit;
        }

        $resultCount = (int) ($gfr->ResultCount ?? 0);
        if ($resultCount <= 0) {
            echo json_encode(['success' => false, 'message' => 'No addresses found for the given postcode.']);
            exit;
        }

        // ---- Normalize Results to a list of FormattedAddress objects ----
        $toList = function ($x) { return is_array($x) ? $x : [$x]; };

        $resultsNode   = $gfr->Results ?? null;
        $formattedList = [];

        if (isset($resultsNode->FormattedAddress)) {
            $formattedList = $toList($resultsNode->FormattedAddress);
        } elseif (is_array($resultsNode)) {
            foreach ($resultsNode as $entry) {
                $formattedList = array_merge(
                    $formattedList,
                    isset($entry->FormattedAddress) ? $toList($entry->FormattedAddress) : [$entry]
                );
            }
        } elseif ($resultsNode) {
            $formattedList[] = isset($resultsNode->FormattedAddress) ? $resultsNode->FormattedAddress : $resultsNode;
        }

        if (empty($formattedList)) {
            echo json_encode(['success' => false, 'message' => 'No addresses found for the given postcode.']);
            exit;
        }

        // ---- Build structured addresses ----
        $addresses = [];

        foreach ($formattedList as $fa) {
            if (!isset($fa->RawAddress)) continue;

            $address = $fa->RawAddress;

            // ----- Street: include dependent thoroughfare ("Oswald Terrace") + main ("Sturton Street")
            $depName = isset($address->DependentThoroughfareName) ? trim($address->DependentThoroughfareName) : '';
            $depDesc = isset($address->DependentThoroughfareDesc) ? trim($address->DependentThoroughfareDesc) : '';
            $mainName = isset($address->ThoroughfareName) ? trim($address->ThoroughfareName) : '';
            $mainDesc = isset($address->ThoroughfareDesc) ? trim($address->ThoroughfareDesc) : '';

            $dep = trim(($depName . ' ' . $depDesc));
            $main = trim(($mainName . ' ' . $mainDesc));
            $street = trim(($dep ? $dep . ' ' : '') . $main);

            // Building number: 0 means none
            $buildingNumber = (!empty($address->BuildingNumber) && (int)$address->BuildingNumber !== 0)
                ? (string)$address->BuildingNumber : '';

            // address_line_1 / 2 rules
            if (!empty($address->Organisation)) {
                $address_line_1 = $address->Organisation;
                $address_line_2 = !empty($address->BuildingName) ? $address->BuildingName : $street;
            } elseif ($buildingNumber !== '') {
                $address_line_1 = trim($buildingNumber . ' ' . $street);
                $address_line_2 = !empty($address->BuildingName) ? $address->BuildingName : '';
            } elseif (!empty($address->BuildingName)) {
                $address_line_1 = $address->BuildingName;
                $address_line_2 = $street;
            } else {
                $address_line_1 = $street;
                $address_line_2 = '';
            }

            // address_line_3 preference chain
            if (!empty($address->SubBuildingName)) {
                $address_line_3 = $address->SubBuildingName;
            } elseif (!empty($address->DoubleDependentLocality)) {
                $address_line_3 = $address->DoubleDependentLocality;
            } elseif (!empty($address->DependentLocality)) {
                $address_line_3 = $address->DependentLocality;
            } elseif (!empty($address->Department)) {
                $address_line_3 = $address->Department;
            } else {
                $address_line_3 = '';
            }

            // County
            $county = !empty($address->PostalCounty) ? $address->PostalCounty
                : (!empty($address->TraditionalCounty) ? $address->TraditionalCounty
                : (!empty($address->AdministrativeCounty) ? $address->AdministrativeCounty : ''));

            // PO Box
            $poBox = !empty($address->PoBox) ? ('PO Box ' . $address->PoBox) : '';

            // Compose full address (incl. postcode) — legacy field kept for compatibility
            $full_address_parts = array_filter(array_unique([
                $address_line_1,
                $address_line_2,
                $address_line_3,
                $poBox,
                isset($address->Locality) ? $address->Locality : '',
                $county,
                isset($address->Postcode) ? $address->Postcode : '',
            ]));

            // Compose display address (EXCLUDES postcode, for dropdown label)
            $display_parts = array_filter(array_unique([
                $address_line_1,
                $address_line_2,
                $address_line_3,
                isset($address->Locality) ? $address->Locality : '',
                $county,
            ]));

            // Title-case lines (keep postcode uppercase separately)
            $pc = isset($address->Postcode) ? strtoupper($address->Postcode) : '';
            $displayAddress = ucwords(strtolower(implode(' ', $display_parts)));
            $fullAddress    = ucwords(strtolower(implode(' ', $full_address_parts)));
            // Re-append uppercase PC to legacy fullAddress (last token)
            if ($pc !== '') $fullAddress = trim(preg_replace('/\s+' . preg_quote($pc, '/') . '$/i', '', $fullAddress) . ' ' . $pc);

            $addresses[] = [
                'address_line_1' => ucwords(strtolower($address_line_1)),
                'address_line_2' => ucwords(strtolower($address_line_2)),
                'address_line_3' => ucwords(strtolower($address_line_3)),
                'po_box'         => $poBox,
                'town'           => isset($address->Locality) ? ucwords(strtolower($address->Locality)) : '',
                'county'         => ucwords(strtolower($county)),
                'postcode'       => $pc,
                'address'        => $fullAddress,      // legacy: includes postcode
                'display_address'=> $displayAddress,   // NEW: excludes postcode (use for dropdown)
            ];
        }

        if (empty($addresses)) {
            echo json_encode(['success' => false, 'message' => 'No addresses found for the given postcode.']);
            exit;
        }

        return $addresses;
    }

    $addressData = GetFullAddress($postcode, $building, $doFind);

    echo json_encode([
        'success' => true,
        'message' => 'Find successful',
        'data'    => $addressData,
    ]);
    exit;
}

// Verification by JS fetch
if (array_key_exists('verify', $_GET)) {
    $code                               = rand(1000, 9999);
    $response                           = new \stdClass();
    $request                            = json_decode(trim(file_get_contents('php://input')));
    if (!$request) {
        $response->e                    = $e_default;
        $response->eCode                = 101;
    } elseif (property_exists($request, 'email')) {
        $nonce = nonce_challenge('email', $request->nonce);
        if (1) {
            //error_log(__FILE__.' '.__LINE__);
            //        if ($nonce=nonce_challenge('email',$request->nonce)) {
            $response->nonce = $nonce;
            if (www_signup_verify_store('email', $request->email, $code)) {
                try {
                    $emailApi = email_api();
                    $emailApi->keySet($org['signup_cm_key']);
                    $emref = $emailApi->send(
                        $org['signup_cm_id_verify'],
                        $request->email,
                        ['Code' => $code]
                    );
                    if (!$emref) {
                        error_log($emailApi->errorLast);
                        $response->e    = $e_default;
                        $response->eCode = 102;
                    }
                    /*
                    $result = campaign_monitor (
                        $org['signup_cm_key'],
                        $org['signup_cm_id_verify'],
                        $request->email,
                        [ 'Code' => $code ]
                    );
                    $ok = $result->http_status_code == 202;
                    if (!$ok) {
                        error_log (__FILE__.' '.__LINE__.' '.print_r($result,true));
                        $response->e    = $e_default;
                        $response->eCode = 102;
                    }
*/
                } catch (\Exception $e) {
                    $response->e        = $e_default;
                    $response->eCode    = 103;
                }
            } else {
                $response->e            = $e_default;
                $response->eCode        = 104;
            }
        } else {
            $response->e                = 'nonce';
            $response->eCode            = 105;
        }
    } elseif (property_exists($request, 'mobile')) {
        $nonce = nonce_challenge('mobile', $request->nonce);
        if (1) {
            //        if ($nonce=nonce_challenge('mobile',$request->nonce)) {
            $response->nonce            = $nonce;
            if (www_signup_verify_store('mobile', $request->mobile, $code)) {
                try {
                    $msg = str_replace('{{Code}}', $code, $org['signup_verify_sms_message']);
                    $msg = str_replace('{{Phone}}', $org['admin_phone'], $msg);
                    $response->result = sms(
                        $org,
                        $request->mobile,
                        $msg,
                        $sms_response
                    );
                    if (!$response->result) {
                        $response->diagnostic = $sms_response;
                        $response->e    = $e_default;
                        $response->eCode = 111;
                    }
                } catch (\Exception $e) {
                    $response->diagnostic = $sms_response;
                    $response->e        = $e_default;
                    $response->eCode    = 112;
                }
            } else {
                $response->e            = $e_default;
                $response->eCode        = 113;
            }
        } else {
            $response->e                = 'nonce';
            $response->eCode            = 114;
        }
    } else {
        $response->e                    = $e_default;
        $response->eCode                = 115;
    }
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

$apis = www_pay_apis();
error_log("=== [ticketsc.php] loaded apis keys: " . implode(', ', array_keys($apis)));
foreach ($apis as $k => $apiObj) {
    error_log("=== [ticketsc.php] api key: $k, class: " . (is_object($apiObj) ? get_class($apiObj) : gettype($apiObj)));
}

$clientCode = defined('BLOTTO_ORG_USER') ? BLOTTO_ORG_USER : null;

$customFontUrl = isset($_GET['font']) ? $_GET['font'] : ''; // Accept optional font from ?font=...
$color1 = isset($_GET['color1']) ? $_GET['color1'] : ''; // Accept optional color1 font from ?color1=...
$color2 = isset($_GET['color2']) ? $_GET['color2'] : ''; // Accept optional color2 font from ?color2=...
$raffleMode = array_key_exists('raffle', $_GET);

// Get layout from query param, default to '5step'
$allowedLayouts = ['5step', '3step', 'full'];
$layout = isset($_GET['layout']) && in_array($_GET['layout'], $allowedLayouts, true) ? $_GET['layout'] : '5step';

// Set initial step for form display
$step = 1;
$error = [];
$go = null;
$api_code = false;


// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // If you want, collect and parse POST vars here
//     // $vars = www_signup_vars();

//     // Use your standard validation
//     if (www_validate_signup($org, $error, $go)) {
//         $step = 2;
//     } else {
//         // Validation error
//         header('Content-Type: application/json');
//         echo json_encode([
//             'success' => false,
//             'message' => 'form errors',
//             'errors' => $error,
//             'go' => $go,
//         ]);
//         exit;
//     }
// } else
// if (array_key_exists('gdpr', $_POST)) {
//     // Our form
//     //    elseif (!array_key_exists('nonce_signup',$_POST) || !nonce_challenge('signup',$_POST['nonce_signup'])) {
//     // if (!array_key_exists('nonce_signup', $_POST)) {
//     //     // Probably just an attempt to refresh stage 2
//     //     $error[] = 'Please post the form again';
//     // } else
//     if (www_validate_signup($org, $error, $go)) { // args 2 & 3 optional by reference
//         // TODO debug here errors etc as not set $api
//         $step = 2;
//     }
// } else {
//     // No AJAX or POST so new nonces are allowed
//     nonce_set('signup');
//     nonce_set('email');
//     nonce_set('mobile');
// }

if (array_key_exists('gdpr', $_POST)) {
    // form submit
    error_log("=== [ticketsc.php] step=$step submit_data=" . var_export($_POST, true));
    $api_found = false;
    foreach ($apis as $api_code => $api_object) {
        if (array_key_exists($api_code, $_POST)) {
            $api_found = true;
            // First found is the one that gets used
            error_log("=== [ticketsc.php] Matched payment API code: $api_code");
            break;
        }
    }
    if (!$api_found) {
        error_log('Payment API could not be identified');
        error_log(__FILE__.' '.__LINE__.' '.print_r($_POST, true));
        $error[] = $e_default;
    } else {
        $step = 2;
    }
} elseif (array_key_exists('finished', $_GET)) {
    // Provider finish
    $api_code = $_GET['finished'];
    $step = 3;
}

if (!count($error) && $api_code) {
    $api = null;
    try {
        $file = $apis[$api_code]->file;
        $class = $apis[$api_code]->class;
        require $file;
        $api = new $class(connect(BLOTTO_MAKE_DB), $org);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $error[] = $e_default;
    }
}

// Price per ticket per draw (hard-coded for this form for now)
$ticketPrice = number_format(BLOTTO_TICKET_PRICE / 100, 2, '.', '');

$signup_url_privacy = org()['signup_url_privacy'];
$signup_url_privacy = htmlspecialchars($signup_url_privacy, ENT_QUOTES, 'UTF-8');

$signup_url_terms = org()['signup_url_terms'];
$signup_url_terms = htmlspecialchars($signup_url_terms, ENT_QUOTES, 'UTF-8');

// Available quantities and week-options
$quantities  = org()['signup_ticket_options'];  // [1, 2, 3, 4, 5, 10, 15, 20];

// week options
// $weekOptions = [1 => '1 week'];
// $weekOptions = [1 => '1 week', 2 => '2 weeks', 4 => '4 weeks'];
$weekOptions = org()['signup_draw_options']; // e.g. '1 1 week'

// amount cap
$max_purchase = org()['signup_amount_cap'];    // 20;

// Accept nextDrawDate as a query parameter if passed
//$nextDrawDateParam = isset($_GET['d']) ? $_GET['d'] : null;
//$nextDrawDateRaw = $nextDrawDateParam ?: '2025-07-05';  // TODO use passed in value
// Convert to formatted string (e.g., "Saturday 5th July 2025")
//$nextDrawDateFormatted = date("l jS F Y", strtotime($nextDrawDateRaw));

$now = new \DateTime ();
$today = $now->format ('Y-m-d');
if (empty($_GET['d']) || $_GET['d'] == 'next_superdraw' || $_GET['d'] < $today) {
    $nextDrawDateRaw = www_signup_next_superdraw($today);
} else {
    $nextDrawDateRaw = $_GET['d'];
}

// Convert to formatted string (e.g., "Saturday 5th July 2025")
$nextdd = new \DateTime ($nextDrawDateRaw);
$nextDrawDateFormatted = $nextdd->format ('l jS F Y');

// custom terms message
$custom_terms_message = '';
if ($clientCode === 'bwh') {
    $custom_terms_message = 'We respect your privacy and are committed to protecting your personal information. Please read our <a href="' . $signup_url_privacy . '" target="_blank">privacy policy</a> for more details.';
}

$contact_email = org()['admin_email'];
$contact_phone = org()['admin_phone'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title title="Buy tickets now">Buy tickets now</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./ticketsc/ticketsc.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- <script type="text/javascript" src="./ticketsc/js-config.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body>

    <!-- <div id="debug-toolbar" style="padding: 10px; text-align: center; background: #f0f0f0; margin-bottom: 1rem;">
        <strong>Demo mode:</strong>
        <button data-debug-step="oneStep">1-Step</button>
        <button data-debug-step="twoStep">2-Step</button>
        <button data-debug-step="threeStep">3-Step</button>
        <button data-debug-step="fourStep">4-Step</button>
        <button data-debug-step="fiveStep">5-Step</button>
    </div> -->

    <?php
    error_log("=== [ticketsc.php] step=$step api_code=" . var_export($api_code, true) . " api isset=" . (isset($api) && $api ? 'yes' : 'no'));

    if (isset($api) && $api) {
        // Use property_exists to avoid errors if $api->name is not set
        $apiName = property_exists($api, 'name') ? $api->name : get_class($api);
        error_log("=== [ticketsc.php] step=$step api_name=" . var_export($apiName, true));
    }
    ?>

    <?php if ($step == 1): ?>
        <!-- data entry form -->
        <div id="ticket-widget-app"></div>
        <script>
            window.TICKET_WIDGET_CONFIG = <?php echo json_encode([
                                                'clientCode'      => $clientCode,
                                                'customFontUrl'   => $customFontUrl,
                                                'customerColor1'  => "#$color1",
                                                'customerColor2'  => "#$color2",
                                                'ticketPrice'     => $ticketPrice,
                                                'maxPurchase'     => intval($max_purchase),
                                                'quantities'      => $quantities,
                                                'weekOptions'     => $weekOptions,
                                                'nextDrawDate'    => $nextDrawDateFormatted,
                                                'nextDrawDateRaw' => $nextDrawDateRaw,
                                                'raffleMode'      => $raffleMode,
                                                'signupUrlPrivacy' => $signup_url_privacy,
                                                'signupUrlTerms'  => $signup_url_terms,
                                                'customTermsMessage' => $custom_terms_message,
                                                'contactEmail'    => $contact_email,
                                                'contactPhone'    => $contact_phone,
                                            ]); ?>;
        </script>
        <script defer src="./ticketsc/ticketsc.js"></script>
    <?php elseif ($step == 2): ?>
        <!-- payment form ONLY -->
        <?php
        if (isset($api) && $api) {
            error_log('=== [ticketsc.php] Rendering payment form for API: ' . get_class($api));
            $api->start_c($error);
        } else {
            error_log('=== [ticketsc.php] Payment system unavailable, $api not set.');
            echo "<p>Error: Payment system unavailable.</p>";
        }
        ?>
    <?php elseif ($step == 3): ?>
        <!-- Thank you / Finished Page -->
        <div class="ticket-widget-wrapper">
            <div class="card">
                <div class="card-body">
                    <?php require __DIR__ . '/views/finishedc.php'; ?>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.try-again-link').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Send message to parent to reload
                        window.parent.postMessage({
                            type: 'reloadParent'
                        }, '*');
                    });
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>
