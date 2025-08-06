<?php

// replace functions in bridge.php (Adminer login linking not required here)

function cookie2end ($cookie_key) {
    if (!array_key_exists($cookie_key,$_COOKIE)) {
        return true;
    }
    if (!strlen($_COOKIE[$cookie_key])) {
        return true;
    }
    if ($_COOKIE[$cookie_key]<time()) {
        return true;
    }
    return false;
}

function cookie2pwd ($cookie_key) {
    return '';
}

function cookie2value ($cookie_key) {
    return '';
}

function pwd2cookie ($password) {
    return '';
}




require __DIR__.'/digest.config.php';

// force login by default
$login = true;
$err = false;

// if a request, authenticate
if (array_key_exists('auth',$_POST)) {
    www_auth (BLOTTO_DB,$timestamp,$err,$msg);
    if (!$err) {
        $login = false;
    }
}

// else check session
elseif (www_session($timestamp)) {
    $login = false;
}


?><!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <title><?php echo gethostname (); ?> digest</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

  </head>

  <body>
    <script>0</script>


    <h2>
      Lottery build digest for <?php echo htmlspecialchars (ucfirst(BLOTTO_BRAND)); ?> <?php echo version (__DIR__); ?>
<?php if (strpos($_SERVER['SERVER_NAME'],'dev.')===0): ?>
      @ <strong style="color:red"><?php echo gethostname (); ?></strong>
<?php else: ?>
      @ <?php echo gethostname (); ?>
<?php endif; ?>
    </h2>


<?php if ($err): ?>
      <p class="error"><?php echo htmlspecialchars ($msg); ?></p>

<?php else: ?>
      <p>&nbsp;</p>

<?php endif; ?>


<?php if ($login): ?>
    <form action="" method="post">
      <div class="usr">
        <input type="text" class="text" name="un" value="" placeholder="Username" />
      </div>
      <div class="pwd">
        <input type="password" name="pw" placeholder="Password" />
      </div>
      <div class="submit">
        <input type="submit" name="auth" title="Login" value="Login" /></span>
      </div>
    </form>

<?php else: ?>



    <h3>Welcome, user <?php echo htmlspecialchars ($_SESSION['blotto']); ?></h3>





<?php endif; ?>

  </body>

</html>


