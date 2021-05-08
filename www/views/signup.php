<?php
$v = www_signup_vars ();
$titles = explode (',',BLOTTO_TITLES_WEB);
?>
    <form class="signup" method="post" action="" <?php if (array_key_exists('demo',$_GET)): ?> onclick="alert('This is just to demonstrate integration!');return false" <?php endif; ?> >
      <input type="hidden" name="nonce_signup" value="<?php echo nonce('signup'); ?>" />

      <a name="about"></a>

      <fieldset>

        <legend>About you</legend>

        <select name="title" required />
          <option value="">Title:</option>
<?php   foreach ($titles as $t): ?>
          <option <?php if($t==$v['title']): ?>selected<?php endif; ?> value="<?php echo htmlspecialchars ($t); ?>"><?php echo htmlspecialchars ($t); ?></option>
<?php   endforeach; ?>
        </select>

        <hr/>

        <label for="first_name" class="hidden">First name</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars ($v['first_name']); ?>" placeholder="First name" title="First name" required />

        <label for="last_name" class="hidden">Last name</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars ($v['last_name']); ?>" placeholder="Last name" title="Last name" required />

        <hr/>

        <label for="dob">Date of birth:</label>
        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars ($v['dob']); ?>" required />

      </fieldset>

      <a name="contact"></a>

      <fieldset>

        <legend>Contact details</legend>

        <label for="email" class="hidden">Email address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars ($v['email']); ?>" placeholder="Email address" title="Email address" required />

<?php if (BLOTTO_SIGNUP_VFY_EML): ?>
        <label for="email_verify" class="hidden">Verify</label>
        <button data-verifytype="email">Send email</button>
        <input type="text" id="email_verify" name="email_verify" value="<?php echo htmlspecialchars ($v['email_verify']); ?>" placeholder="Verify code" title="Email verification code" required />
        <input type="hidden" name="nonce_email" value="<?php echo nonce('email'); ?>" />
<?php endif; ?>

        <hr/>

        <label for="mobile" class="hidden">Mobile number</label>
        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars ($v['mobile']); ?>" placeholder="Mobile number" title="Mobile number" pattern="[0-9]{10,12}" required />

<?php if (BLOTTO_SIGNUP_VFY_MOB): ?>
        <label for="mobile_verify" class="hidden">Verify</label>
        <button data-verifytype="mobile">Send SMS</button>
        <input type="text" id="mobile_verify" name="mobile_verify" value="<?php echo htmlspecialchars ($v['mobile_verify']); ?>" placeholder="Verify code" title="Mobile number verification code" required />
        <input type="hidden" name="nonce_mobile" value="<?php echo nonce('mobile'); ?>" />
<?php endif; ?>

        <hr/>

        <label for="telephone" class="hidden">Landline number</label>
        <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars ($v['telephone']); ?>" placeholder="Landline number" title="Landline number" pattern="\+?[\d\s]{10,}" />

      </fieldset>

      <a name="address"></a>

      <fieldset>

        <legend>Address</legend>

        <label for="postcode" class="hidden">Postcode</label>
        <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars ($v['postcode']); ?>" placeholder="Postcode" title="Postcode" required />

        <hr/>

        <label for="address_1" class="hidden">Address line 1</label>
        <input type="text" class="address-line" id="address_1" name="address_1" value="<?php echo htmlspecialchars ($v['address_1']); ?>" placeholder="Address line 1" title="Address line 1" required />

        <hr/>

        <label for="address_2" class="hidden">Address line 2</label>
        <input type="text" class="address-line" id="address_2" name="address_2" value="<?php echo htmlspecialchars ($v['address_2']); ?>" placeholder="Address line 2" title="Addres line 2" />

        <hr/>

        <label for="address_3" class="hidden">Address line 3</label>
        <input type="text" class="address-line" id="address_3" name="address_3" value="<?php echo htmlspecialchars ($v['address_3']); ?>" placeholder="Address line 3" title="Address line 3" />

        <hr/>

        <label for="town" class="hidden">Town/city</label>
        <input type="text" id="town" name="town" value="<?php echo htmlspecialchars ($v['town']); ?>" placeholder="Town/city" title="Town/city" required />

        <label for="county" class="hidden">County</label>
        <input type="text" id="county" name="county" value="<?php echo htmlspecialchars ($v['county']); ?>" placeholder="County" title="County" />

      </fieldset>

      <fieldset>

        <legend>Ticket requirements</legend>

        <div class="field">

          <label class="requirements">Number of chances each weekly draw</label>

            <select name="quantity" id="quantity">
              <?php
              if(!$v['quantity']) {
                $v['quantity'] = 1;
              }
              for ($i = 1; $i <= 10; $i++) {
                echo '<option value="'.$i.'"';
                if ($i == $v['quantity']) {
                  echo " selected";
                }
                echo '>'.$i.'</option>';
              }
              ?>
            </select>


          <!-- div>
            <input type="radio" name="quantity" id="quantity-1" value="1" <?php if(!$v['quantity'] || $v['quantity']==1): ?>checked<?php endif;?> />
            <label for="quantity-1">1 chance for £4.34 monthly</label>
          </div>
          <div>
            <input type="radio" name="quantity" id="quantity-2" value="2" <?php if($v['quantity']==2): ?>checked<?php endif;?> />
            <label for="quantity-2">2 chances for £8.68 monthly</label>
          </div -->


        <div class="field">

          <label class="requirements">Number of weekly draws</label>

            <select name="draws" id="draws">
              <?php
              if(!$v['draws']) {
                $v['draws'] = 1;
              }
              $max_weeks = intval(STRIPE_MAX_PAYMENT / $v['quantity']);
              for ($i = 1; $i <= $max_weeks; $i++) {
                echo '<option value="'.$i.'"';
                if ($i == $v['draws']) {
                  echo " selected";
                }
                echo '>'.$i.'</option>';
              }
              ?>
            </select>


          <!-- div>
            <input type="radio" name="draws" id="draws-1" value="1" <?php if(!$v['draws'] || $v['draws']==1): ?>checked<?php endif;?> />
            <label for="draws-1">1 draw</label>
          </div>
          <div>
            <input type="radio" name="draws" id="draws-2" value="2" <?php if($v['draws']==2): ?>checked<?php endif;?> />
            <label for="draws-2">2 chances for £8.68 monthly</label>
          </div -->

        </div>

      </fieldset>

      <a name="preferences"></a>

      <fieldset>

        <legend>Preferences</legend>

        <div class="field">
          <input type="checkbox" name="pref_1" <?php if ($v['pref_1']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by email?</label>
        </div>

        <div class="field">
          <input type="checkbox" name="pref_2" <?php if ($v['pref_2']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by SMS?</label>
        </div>

        <div class="field">
          <input type="checkbox" name="pref_3" <?php if ($v['pref_3']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by post?</label>
        </div>

        <div class="field">
          <input type="checkbox" name="pref_4" <?php if ($v['pref_4']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by telephone?</label>
        </div>

      </fieldset>

      <a name="gdpr"></a>

      <fieldset>

        <legend>Protecting your data</legend>

        <div class="consentBlock">

          <div>

            <h3>GDPR Statement</h3>

            <p>Your support makes our vital work possible.  We&#039;d love to keep in touch with you to tell you more about our work and how you can support it. We&#039;ll do this by the options you chose above and you can change these preferences at any time by calling or e-mailing us on <?php echo htmlspecialchars (STRIPE_ADMIN_PHONE); ?> or <a href="mailto:<?php echo htmlspecialchars (STRIPE_ADMIN_EMAIL); ?>"><?php echo htmlspecialchars (STRIPE_ADMIN_EMAIL); ?></a></p>

            <p>We will never sell your details on to anyone else.</p>

          </div>

          <div>
            <input id="gdpr" type="checkbox" name="gdpr" <?php if($v['gdpr']): ?>checked<?php endif; ?> required />
            <label for="gdpr">I have read and understood the above.</label>
          </div>

        </div>

      </fieldset>


      <fieldset>

        <legend>Protecting your data</legend>

        <div class="field">
          <input id="terms" type="checkbox" name="terms" <?php if($v['terms']): ?>checked<?php endif; ?> required />
          <label for="terms">I accept the <a target="_blank" href="<?php echo htmlspecialchars (STRIPE_TERMS); ?>">terms &amp; conditions</a> and <a target="_blank" href="<?php echo htmlspecialchars (STRIPE_PRIVACY); ?>">privacy policy</a>.</label>
        </div>

        <div class="field">
          <input id="age" type="checkbox" name="age" <?php if($v['age']): ?>checked<?php endif; ?> required />
          <label for="age">I confirm that I am aged 18 or over.</label>
        </div>

      </fieldset>

      <fieldset>

        <legend>Select payment method</legend>

<?php foreach ($apis as $code=>$api): ?>

        <style>
form.signup input[name="<?php echo $code; ?>"] {
    background-image:  url('./media/<?php echo strtolower ($api->name); ?>.png');
}
        </style>
        <input type="submit" name="<?php echo $code; ?>" value="&nbsp;" title="Pay with <?php echo htmlspecialchars ($api->name); ?>" alt="<?php echo htmlspecialchars ($api->name); ?> logo" />

<?php endforeach; ?>

      </fieldset>

    </form>

