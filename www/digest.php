<?php

require '../config.digest.php';

?><!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <title>
<?php 
        if (strpos ($_SERVER['SERVER_NAME'],'dev.')===0) {
            echo "dev:";
        }
        'Build digest @ '.htmlspecialchars (BLOTTO_BRAND); ?> <?php version(__DIR__); 
?>
    </title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

  </head>

  <body>
    <script>0</script>

    <h2 style="color:red">
      Lottery build digest -
<?php if (strpos($_SERVER['SERVER_NAME'],'dev.')===0): ?>
      <?php echo htmlspecialchars ($_SERVER['SERVER_NAME']); ?>
<?php else: ?>
      <strong style="color:red"><?php echo htmlspecialchars ($_SERVER['SERVER_NAME']); ?></strong>
<?php endif; ?>
    </h2>

    <section id="login" class="<?php if (!is_https()): ?>in<?php endif; ?>secure">

      <form action="" method="post">

        <section class="form-content">

<?php if($err): ?>
          <p class="error"><?php echo htmlspecialchars ($err); ?></p>

<?php else: ?>
          <p>&nbsp;</p>

<?php endif; ?>
          <div class="usr">
            <input type="text" class="text" name="un" value="<?php echo htmlspecialchars ($run); ?>" placeholder="Username" />
          </div>
          <div class="pwd">
            <span class="component"><input <?php if($run): ?> type="text" <?php else: ?> type="password" <?php endif; ?> class="text squeezed" name="pw" placeholder="Password" /><input type="submit" class="image" name="auth" title="Login now" value="Login now" /></span>
          </div>
          <div id="reset-option">
            <input id="reset-start" type="submit" class="link" name="reset" title="Reset password" value="Reset password" />
          </div>

        </section>

      </form>

    </section>

  </body>

</html>


