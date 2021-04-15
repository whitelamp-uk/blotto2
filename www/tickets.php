<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
require BLOTTO_SIGNUP_PAY_API;
require PAYPAL_CAMPAIGN_MONITOR;


try {
    $api = new PayApi (connect(BLOTTO_MAKE_DB));
}
catch (Exception $e) {
     $error = $e->getMessage ();
}

if (!$error) {

    if (array_key_exists('callback',$_GET)) {
        $api->callback ();
        exit;
    }

    header ('Access-Control-Allow-Origin: *');
    $error = null;
    $finished = null;

    if (count($_POST)) {
        // The user wants a "buy now" link
        try {
            $api->start ();
        }
        catch (Exception $e) {
             $error = $e->getMessage ();
        }
    }

}

?><!doctype html>
<html class="no-js" lang="">

  <head>

    <!-- Copyright 2020 Burden and Burden  http://www.burdenandburden.co.uk/ -->

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="apple-touch-icon" href="./icon.png" />


    <link rel="author" href="http://www.burdenandburden.co.uk" />
    <title title="Burden &amp; Burden self-sign-up service">Sign-up service</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script defer type="text/javascript" src="https://webservices.data-8.co.uk/javascript/address_min.js"></script>
    <script type="text/javascript" src="js-config.js"></script>
    <script defer type="text/javascript" src="postcode-lookup.js"></script>
    <script defer type="text/javascript" src="form.js"></script>
<?php if(defined('PAYPAL_FACEBOOK_ID') && PAYPAL_FACEBOOK_ID): ?>
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
  fbq('init', '<?php echo PAYPAL_FACEBOOK_ID; ?>');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=<?php echo urlencode (PAYPAL_FACEBOOK_ID); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
<?php endif; ?>

    <link rel="stylesheet" href="normalize.css" />
    <link rel="stylesheet" href="style.css" />
<?php if (array_key_exists('css',$_GET)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars ($_GET['css']); ?>" />
<?php endif; ?>

  </head>

  <body>

<?php $api->output_signup_form(); ?>

  </body>

</html>

