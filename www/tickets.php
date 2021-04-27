<?php

require './bridge.php';
require BLOTTO_WWW_FUNCTIONS;
require BLOTTO_WWW_CONFIG;

$apis = www_pay_apis ();
// print_r ($apis);

$api_code = null;
if (array_key_exists('method',$_GET)) {
    if (array_key_exists($_GET['method'],$apis)) {
        $api_code = $_GET['method'];
    }
}
elseif (array_key_exists('method',$_POST)) {
    if (array_key_exists($_POST['method'],$apis)) {
        $api_code = $_POST['method'];
    }
}

// Make this sign-and-pay page available for use in a charity website's iframe
header ('Access-Control-Allow-Origin: *');

$error = [];
$api = null;
if (count($_POST)) {
    if (www_verify_signup($error)) {
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
            $api->start ();
        }
        catch (Exception $e) {
             $error[] = 'Sorry we could not process your request - please try later';
             require __DIR__.'/views/signup.php';
        }
    }
}

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
<?php if (array_key_exists('css',$_GET)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars ($_GET['css']); ?>" />
<?php else: ?>
    <link rel="stylesheet" href="./media/style.css" />
<?php endif; ?>

  </head>

  <body>

<?php require __DIR__.'/views/signup.php'; ?>

<?php if ($api) { $api->start (); } ?>

  </body>

</html>

