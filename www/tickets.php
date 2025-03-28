<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
if (defined('CAMPAIGN_MONITOR') && CAMPAIGN_MONITOR) {
    require CAMPAIGN_MONITOR;
}
if (defined('VOODOOSMS') && VOODOOSMS) {
    require VOODOOSMS;
}
$e_default = 'Sorry something went wrong - please try later';
$org = org ();

// Session
session_start ();


// Verification by JS fetch
if (array_key_exists('verify',$_GET)) {
    $code                               = rand (1000,9999);
    $response                           = new \stdClass ();
    $request                            = json_decode (trim(file_get_contents('php://input')));
    if (!$request) {
        $response->e                    = $e_default;
        $response->eCode                = 101;
    }
    elseif (property_exists($request,'email')) {
$nonce=nonce_challenge('email',$request->nonce);
if (1) {
//error_log(__FILE__.' '.__LINE__);
//        if ($nonce=nonce_challenge('email',$request->nonce)) {
            $response->nonce = $nonce;
            if (www_signup_verify_store('email',$request->email,$code)) {
                try {
                    $api = email_api ();
                    $api->keySet ($org['signup_cm_key']);
                    $emref = $api->send (
                        $org['signup_cm_id_verify'],
                        $request->email,
                        [ 'Code' => $code ]
                    );
                    if (!$emref) {
                        error_log ($api->errorLast);
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
                        error_log (print_r($result,true));
                        $response->e    = $e_default;
                        $response->eCode = 102;
                    }
*/
                }
                catch (\Exception $e) {
                    $response->e        = $e_default;
                    $response->eCode    = 103;
                }
            }
            else {
                $response->e            = $e_default;
                $response->eCode        = 104;
            }
        }
        else {
            $response->e                = 'nonce';
            $response->eCode            = 105;
        }
    }
    elseif (property_exists($request,'mobile')) {
$nonce=nonce_challenge('mobile',$request->nonce);
if (1) {
//        if ($nonce=nonce_challenge('mobile',$request->nonce)) {
            $response->nonce            = $nonce;
            if (www_signup_verify_store('mobile',$request->mobile,$code)) {
                try {
                    $msg = str_replace ('{{Code}}',$code,$org['signup_verify_sms_message']);
                    $msg = str_replace ('{{Phone}}',$org['admin_phone'],$msg);
                    $response->result = sms (
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
                }
                catch (\Exception $e) {
                    $response->diagnostic = $sms_response;
                    $response->e        = $e_default;
                    $response->eCode    = 112;
                    error_log("tickets.php SMS: request: ".print_r($request,true)." response: ".print_r($response,true));
                }
            }
            else {
                $response->e            = $e_default;
                $response->eCode        = 113;
            }
        }
        else {
            $response->e                = 'nonce';
            $response->eCode            = 114;
        }
    }
    else {
        $response->e                    = $e_default;
        $response->eCode                = 115;
    }
    header ('Content-Type: application/json');
    echo json_encode ($response,JSON_PRETTY_PRINT);
    exit;
}


// Make this sign-and-pay page available for use in a charity website's iframe
header ('Access-Control-Allow-Origin: *');
header ('Content-Security-Policy: frame-ancestors '.BLOTTO_WWW_IFRAME_SOURCES);
//print_r($_POST);
$apis = www_pay_apis ();
//print_r ($apis);

$step = 1;
$error = [];
$go = null;
$api_code = false;

if (array_key_exists('gdpr',$_POST)) {
    // Our form
    //    elseif (!array_key_exists('nonce_signup',$_POST) || !nonce_challenge('signup',$_POST['nonce_signup'])) {
    if (!array_key_exists('nonce_signup',$_POST)) {
        // Probably just an attempt to refresh stage 2
        $error[] = 'Please post the form again';
    }

    elseif (www_validate_signup($org,$error,$go)) { // args 2 & 3 optional by reference
        $step = 2;
    }

}
else {
    // No AJAX or POST so new nonces are allowed
    nonce_set ('signup');
    nonce_set ('email');
    nonce_set ('mobile');
}

if (array_key_exists('gdpr',$_POST)) {
    // Our form
    $api_found = false;
    foreach ($apis as $api_code=>$api_object) {
        if (array_key_exists($api_code,$_POST)) {
            $api_found = true;
            // First found is the one that gets used
            break;
        }
    }
    if (!$api_found) {
        error_log ('Payment API could not be identified');
        error_log (print_r($_POST, true));
        $error[] = $e_default;
    }
}
elseif (array_key_exists('finished',$_GET)) {
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
        $api = new $class (connect(BLOTTO_MAKE_DB),$org);
    }
    catch (Exception $e) {
        error_log ($e->getMessage());
        $error[] = $e_default;
    }
}




// Output front end
?><!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="apple-touch-icon" href="./icon.png" />
    <?php if (count($_POST) && $api_code == 'STRP') { ?>
      <script src="https://js.stripe.com/v3/"></script>
    <?php } ?>
    <link rel="author" href="http://www.whitelamp.com/" />
    <title title="Buy lottery tickets now">Buy lottery tickets now</title>

<?php if ($step!=2): ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script defer type="text/javascript" src="https://webservices.data-8.co.uk/javascript/address_min.js"></script>
    <script type="text/javascript" src="./media/js-config.js"></script>
    <!-- script defer type="text/javascript" src="./media/custom-postcode-lookup.js"></script -->
    <script defer type="text/javascript" src="./media/postcode-lookup.js"></script>
    <script defer type="text/javascript" src="./media/signup.js"></script>
<?php endif; ?>

<?php if ($go): ?>
    <script>
window.location.href = '#<?php echo $go; ?>';
    </script>
<?php endif; ?>
<?php if(defined('BLOTTO_WWW_FACEBOOK_ID') && BLOTTO_WWW_FACEBOOK_ID): ?>
<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?php echo BLOTTO_WWW_FACEBOOK_ID; ?>');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=<?php echo urlencode (BLOTTO_WWW_FACEBOOK_ID); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
<?php endif; ?>

    <link rel="stylesheet" href="./media/normalize.css" />
    <link rel="stylesheet" href="./media/signup.css" />
<?php if (array_key_exists('css',$_GET)): // This allows charity to override styles ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars ($_GET['css']); ?>" />
<?php endif; ?>

  </head>
  <body>
    <section class="signup">

<?php
    if ($step==1) {
        require __DIR__.'/views/signup.php';
        //error_log("tickets.php signup");
    }
    elseif ($step==3) {
        require __DIR__.'/views/finished.php';
        //error_log("tickets.php finished");
    }
    else {
        $api->start ($error);
        //error_log("tickets.php cardnet");
    }
?>

    </section>

<?php if (count($error)): 
    error_log(print_r($error, true));
    ?>
      <script defer>
<?php     foreach($error as $e): ?>
        setTimeout ('userMessage("<?php echo str_replace ("\"","\\\"",$e); ?>")',1000);
<?php     endforeach; ?>
      </script>
<?php endif; ?>

  </body>

</html>

