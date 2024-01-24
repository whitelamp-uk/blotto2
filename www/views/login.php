<?php

$modes = [
    0 => '[normal login]',
    1 => 'Please enter your username',
    2 => 'Please enter your email address',
    3 => 'Please enter the code we just sent to your email address',
    4 => 'Please enter the code we just sent to your phone',
    5 => 'Set your new password now'
];

$mode = www_auth_reset ($error_report);

// Figure out seconds remaining
if ($mode>0) {
    $t = 60 * BLOTTO_WWW_PWDRST_MINS;
    $now = new \DateTime ();
    $s = 1*strtotime($_SESSION['reset']['expires']) - 1*$now->format('U');
    if ($s<0) {
        $s = 0;
    }
}
?>

    <section id="login" class="<?php if (!is_https()): ?>in<?php endif; ?>secure">

      <form class="login" action="./" method="post">

        <section class="form-content">

<?php if($mode==0): ?>
<?php     if($error_report): ?>
            <p class="error"><?php echo htmlspecialchars ($error_report); ?></p>

<?php     else: ?>
          <p>&nbsp;</p>

<?php     endif; ?>
          <div class="usr">
            <input type="text" class="text" name="un" placeholder="Username" />
          </div>
          <div class="pwd">
            <span class="component"><input type="password" class="text squeezed" name="pw" placeholder="Password" /><input type="submit" class="image" name="auth" title="Login now" value="Login now" /></span>
          </div>
          <div id="reset-option">
            <input id="reset-start" type="submit" class="link" name="reset" title="Reset password" value="Reset password" />
          </div>

<?php elseif($mode==5): ?>
<?php     if($error_report): ?>
          <p class="error"><?php echo htmlspecialchars ($error_report); ?></p>
<?php     else: ?>
          <p><?php echo htmlspecialchars ($modes[$mode]); ?></p>
<?php     endif; ?>
          <input type="text" class="hidden" name="un" placeholder="Username" value="<?php echo htmlspecialchars ($_SESSION['reset']['un']); ?>" />
          <input type="hidden" name="reset" value="1" />
          <p>We recommend our password suggestion but you may change it [or <span class="copy">copy it</span>]</p>
          <div class="pwd">
            <span class="component"><span class="timer" data-seconds="<?php echo $t; ?>"><span class="timeremaining" data-seconds="<?php echo intval ($s); ?>"><span></span></span></span><input type="password" class="text squeezed" name="pw" placeholder="Password" /><input type="submit" class="image" name="auth" title="Reset password and login" value="Reset password and login" /></span>
          </div>
          <div id="reset-option">
            <a id="reset-cancel" class="link" href="./" title="Cancel reset process">Cancel reset process</a>
          </div>

<?php else: ?>
          <div class="reset">

<?php     if($error_report): ?>
            <p class="error"><?php echo htmlspecialchars ($error_report); ?></p>

<?php     else: ?>
            <p><?php echo htmlspecialchars ($modes[$mode]); ?></p>

<?php     endif; ?>
<?php     if($mode==1): ?>
            <span class="component"><span class="timer" data-seconds="<?php echo $t; ?>"><span class="timeremaining" data-seconds="<?php echo $s; ?>"><span></span></span></span><input type="text" class="text squeezed" name="un" placeholder="Username" /><input type="submit" class="image" name="reset" title="Post username and get email" value="Post username and get email" /></span>

<?php     elseif($mode==2): ?>
            <span class="component"><span class="timer" data-seconds="<?php echo $t; ?>"><span class="timeremaining" data-seconds="<?php echo $s; ?>"><span></span></span></span><input type="text" class="text squeezed" name="em" placeholder="Email address" /><input type="submit" class="image" name="reset" title="Confirm email PIN & get SMS" value="Confirm email PIN & get SMS" /></span>

<?php     elseif($mode==3): ?>
            <span class="component"><span class="timer" data-seconds="<?php echo $t; ?>"><span class="timeremaining" data-seconds="<?php echo $s; ?>"><span></span></span></span><input type="text" class="text squeezed" name="em_code_try" placeholder="Email PIN" /><input type="submit" class="image" name="reset" value="Submit" /></span>

<?php     elseif($mode==4): ?>
            <span class="component"><span class="timer" data-seconds="<?php echo $t; ?>"><span class="timeremaining" data-seconds="<?php echo $s; ?>"><span></span></span></span><input type="text" class="text squeezed" name="sms_code_try" placeholder="SMS PIN" /><input type="submit" class="image" name="reset" value="Submit" /></span>

<?php     endif; ?>
          </div>
          <div id="reset-option">
            <a id="reset-cancel" class="link" href="./" title="Cancel reset process">Cancel reset process</a>
          </div>

<?php endif; ?>

          </div>

        </section>

      </form>

      <img id="logo-login-form" src="./media/logo-login-form.png"/>

    </section>

    <img id="logo-login" src="./media/logo-login.png"/>
    <img id="logo" src="./logo-org.png"/>


<?php require './brand.php'; ?>

<script>

window.document.addEventListener (
    'DOMContentLoaded',
    function ( ) {
        /*
        A browser prompt should not be invoked on refresh of the response to a POST
        request.
        A refresh should forget everything and you go to the beginning without comment
        from JS or the browser.
        */
        history.replaceState (null,null,'./');
    }
);

<?php if($mode>0): ?>
window.document.addEventListener (
    'DOMContentLoaded',
    function ( ) {
        var box,bar;
        box = document.querySelector ('form.login .timer');
        bar = document.querySelector ('form.login .timeremaining');
        interval = setInterval (
            function ( ) {
                var s = passwordResetTimerDecrement (box,bar);
                if (s==0) {
                    passwordResetAutoCancel ();
                }
            },
            1000
        );
        passwordResetTimerDecrement (box,bar,'start');
    }
);
<?php endif; ?>

<?php if($mode==5): ?>
warned = false;
window.document.addEventListener (
    'DOMContentLoaded',
    function ( ) {
        var cp = document.querySelector ('form.login span.copy');
        var pw = document.querySelector ('input[name="pw"]');
        pw.form.auth.focus ();
        /*
        We are not trying to protect ourselves directly here; by enforcing our own
        password generator [ TODO implement browser password suggestions when possible ]
        the aim is to offer better protection of organisation data (especially mods to
        BACS data).
        */
        pw.value = passwordSuggestion ();
        pw.addEventListener (
            'focus',
            function (evt) {
                evt.currentTarget.type = 'text';
            }
        );
        pw.addEventListener (
            'blur',
            function (evt) {
                evt.currentTarget.type = 'password';
            }
        );
        pw.addEventListener (
            'click',
            function (evt) {
                if (!warned) {
                    evt.currentTarget.type = 'text';
                    evt.currentTarget.select ();
                    navigator.clipboard.writeText (evt.currentTarget.value);
                    var msg = 'Password has been copied to clipboard';
                    msg += '\nWe recommend that you use this strong password';
                    msg += '\nor another suggested by your browser';
                    alert (msg);
                    warned = true;
                }
            }
        );
    }
);
<?php endif; ?>

</script>

