<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;
if (defined(CAMPAIGN_MONITOR) && CAMPAIGN_MONITOR) {
    require CAMPAIGN_MONITOR;
}
if (defined(VOODOOSMS) && VOODOOSMS) {
    require VOODOOSMS;
}
// Make this sign-and-pay page available for use in a charity website's iframe
header ('Access-Control-Allow-Origin: *');
$apis = www_pay_apis ();
//print_r ($apis);
//print_r($_POST);


$step = 1;
$error = [];
if (count($_POST)) {

    $api_found = false;
    foreach ($apis as $api_code => $api_definition) { // NB also used at end of signup.php
        if (isset($_POST[$api_code])) {
            $api_found = true;
        }
    }

    if (!$api_found) { // if this happens forget what's wrong with the form!
        $error[] = 'Could not find the payment system!';
    }
    elseif (www_verify_signup($error)) { // passed by reference and zeroed out...
        if ($_POST['telephone'] && !www_verify_phone ($_POST['telephone'],'L')) {
            $error[] = 'Telephone number (landline) is not valid';
        }
        if ($_POST['mobile'] && !www_verify_phone($_POST['mobile'],'M')) {
            $error[] = 'Telephone number (mobile) is not valid';
        }
        if ($_POST['email'] && !www_verify_email($_POST['email'])) {
            $error[] = 'Email address is not valid';
        }
    }

    if (!count($error)) {
        $api = null;
        try {
            $file = $apis[$api_code]->file;
            $class = $apis[$api_code]->class;
            require $file;
            $api = new $class (connect(BLOTTO_MAKE_DB));
            $step = 2;
        }
        catch (Exception $e) {
             $error[] = 'Sorry we could not process your request - please try later';
        }
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
    <?php if (count($_POST)) { ?>
      <script src="https://js.stripe.com/v3/"></script>
    <?php } ?>
    <link rel="author" href="http://www.whitelamp.com/" />
    <title title="Buy lottery tickets now">Buy lottery tickets now</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script defer type="text/javascript" src="https://webservices.data-8.co.uk/javascript/address_min.js"></script>
    <script type="text/javascript" src="./media/js-config.js"></script>
    <script defer type="text/javascript" src="./media/custom-postcode-lookup.js"></script>
    <script defer type="text/javascript" src="./media/form.js"></script>
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
    <link rel="stylesheet" href="./media/stripe.css" />
<?php if (array_key_exists('css',$_GET)): // This allows charity to override styles ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars ($_GET['css']); ?>" />
<?php else: ?>
    <link rel="stylesheet" href="./media/style.css" />
<?php endif; ?>

  </head>
  <body>
    <section class="signup">


<?php
    if ($step==1) {
        require __DIR__.'/views/signup.php';
    }
    elseif ($step==2) {
        $api->start ();
    }
?>


<?php if (count($error)): ?>
        <div class="error">
          <button data-close></button>
<?php     foreach($error as $e): ?>
          <p><?php echo htmlspecialchars ($e); ?></p>
<?php     endforeach; ?>
        </div>
<?php endif; ?>

    </section>
  </body>

</html>

