<?php
$v = www_signup_vars ();
if (!$v['quantity']) {
    $v['quantity'] = 1;
}
if (!$v['draws']) {
    $v['draws'] = 1;
}
$titles = explode (',',defn('BLOTTO_TITLES_WEB',false));
?>

    <form class="signup" method="post" action="" <?php if (array_key_exists('demo',$_GET)): ?> onclick="alert('This is just to demonstrate integration!');return false" onsubmit="alert('This is just to demonstrate integration!');return false" <?php endif; ?> >
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

        <label for="name_first" class="hidden">First name</label>
        <input type="text" id="name_first" name="name_first" value="<?php echo htmlspecialchars ($v['name_first']); ?>" placeholder="First name" title="First name" required />

        <label for="name_last" class="hidden">Last name</label>
        <input type="text" id="name_last" name="name_last" value="<?php echo htmlspecialchars ($v['name_last']); ?>" placeholder="Last name" title="Last name" required />

        <hr/>

        <label for="dob">Date of birth:</label>
        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars ($v['dob']); ?>" required />

      </fieldset>

      <a name="contact"></a>

      <fieldset>

        <legend>Contact details</legend>

        <label for="email" class="hidden">Email address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars ($v['email']); ?>" placeholder="Email address" title="Email address" required />

<?php if ($org['signup_verify_email']>0): ?>
        <label for="email_verify" class="hidden">Verify</label>
        <button name="verify_button_email" data-verifytype="email" type="button">Send email</button>
        <input type="text" id="email_verify" name="email_verify" value="<?php echo htmlspecialchars ($v['email_verify']); ?>" placeholder="Verify code" title="Email verification code" required />
        <input type="hidden" name="nonce_email" value="<?php echo nonce('email'); ?>" />
<?php endif; ?>

        <hr/>

        <label for="mobile" class="hidden">Mobile number</label>
        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars ($v['mobile']); ?>" placeholder="Mobile number" title="Mobile number" pattern="[0-9]{10,12}" required />

<?php if ($org['signup_verify_sms']>0): ?>
        <label for="mobile_verify" class="hidden">Verify</label>
        <button name="verify_button_mobile" data-verifytype="mobile" type="button">Send SMS</button>
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
        <input type="text" class="address-line" id="address_2" name="address_2" value="<?php echo htmlspecialchars ($v['address_2']); ?>" placeholder="Address line 2" title="Address line 2" />

        <hr/>

        <label for="address_3" class="hidden">Address line 3</label>
        <input type="text" class="address-line" id="address_3" name="address_3" value="<?php echo htmlspecialchars ($v['address_3']); ?>" placeholder="Address line 3" title="Address line 3" />

        <hr/>

        <label for="town" class="hidden">Town/city</label>
        <input type="text" id="town" name="town" value="<?php echo htmlspecialchars ($v['town']); ?>" placeholder="Town/city" title="Town/city" required />

        <label for="county" class="hidden">County</label>
        <input type="text" id="county" name="county" value="<?php echo htmlspecialchars ($v['county']); ?>" placeholder="County" title="County" />

      </fieldset>

      <a name="requirements"></a>

      <fieldset>

        <legend>Ticket requirements</legend>

        <div class="field radioset">

          <label data-ppt="<?php echo number_format (BLOTTO_TICKET_PRICE/100,2,'.',''); ?>" data-maxamount="<?php echo intval ($org['signup_amount_cap']); ?>" class="requirements">Tickets cost £<?php echo number_format (BLOTTO_TICKET_PRICE/100,2,'-',','); ?> per draw, maximum allowed purchase is £<?php echo number_format ($org['signup_amount_cap'],2,'-',','); ?></label>

<?php foreach ($org['signup_ticket_options'] as $i): ?>
          <div>
            <input type="radio" name="quantity" data-maxdraws="<?php echo intval ($org['signup_amount_cap']/($i*BLOTTO_TICKET_PRICE/100)); ?>" id="quantity-<?php echo 1*$i; ?>" value="<?php echo 1*$i; ?>" <?php if ($i==$v['quantity']): ?> checked <?php endif;?> />
            <label for="quantity-<?php echo 1*$i; ?>"><?php echo 1*$i; ?> ticket<?php echo plural($i); ?></label>
          </div>
<?php endforeach; ?>

        </div>

        <div class="field radioset">

          <label class="requirements">Number of weekly draws</label>

<?php foreach ($org['signup_draw_options'] as $i=>$label): ?>
          <div>
            <input type="radio" name="draws" id="draws-<?php echo 1*$i; ?>" value="<?php echo 1*$i; ?>" <?php if($i==$v['draws']): ?>checked<?php endif;?> />
            <label for="draws-<?php echo 1*$i; ?>"><?php echo htmlspecialchars ($label); ?></label>
          </div>
<?php endforeach; ?>

        </div>

        <div id="signup-cost" class="signup-cost">
          &pound;<output data-decsepchar="-"><?php echo number_format ($v['quantity']*$v['draws']*BLOTTO_TICKET_PRICE/100,2,'-',','); ?></output>
        </div>

      </fieldset>

      <a name="preferences"></a>

      <fieldset>

        <legend>Preferences</legend>

        <div class="field checkbox">
          <input type="checkbox" name="pref_email" <?php if ($v['pref_email']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by email?</label>
        </div>

        <div class="field checkbox">
          <input type="checkbox" name="pref_sms" <?php if ($v['pref_sms']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by SMS?</label>
        </div>

        <div class="field checkbox">
          <input type="checkbox" name="pref_post" <?php if ($v['pref_post']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by post?</label>
        </div>

        <div class="field checkbox">
          <input type="checkbox" name="pref_phone" <?php if ($v['pref_phone']): ?>checked <?php endif; ?> />
          <label class="field-label">Can we contact you by telephone?</label>
        </div>

      </fieldset>

      <a name="smallprint"></a>

      <fieldset>

        <legend>Protecting your data</legend>

        <div class="consentBlock">

          <div>

            <h3>GDPR Statement</h3>

            <p>Your support makes our vital work possible.  We&#039;d love to keep in touch with you to tell you more about our work and how you can support it. We&#039;ll do this by the options you chose above and you can change these preferences at any time by calling <?php echo htmlspecialchars ($org['admin_phone']); ?> or e-mailing <a href="mailto:<?php echo htmlspecialchars ($org['admin_email']); ?>"><?php echo htmlspecialchars ($org['admin_email']); ?></a>.</p>

            <p>We will never sell your details on to anyone else.</p>

          </div>

          <div class="field checkbox">
            <input id="gdpr" type="checkbox" name="gdpr" <?php if($v['gdpr']): ?>checked<?php endif; ?> required />
            <label for="gdpr">I have read and understood the above.</label>
          </div>

        </div>

      </fieldset>

      <fieldset>

        <legend>Protecting your data</legend>

        <div class="field checkbox">
          <input id="terms" type="checkbox" name="terms" <?php if($v['terms']): ?>checked<?php endif; ?> required />
          <label for="terms">I accept the <a target="_blank" href="<?php echo htmlspecialchars ($org['signup_url_terms']); ?>">terms &amp; conditions</a> and <a target="_blank" href="<?php echo htmlspecialchars ($org['signup_url_privacy']); ?>">privacy policy</a>.</label>
        </div>

        <div class="field checkbox">
          <input id="age" type="checkbox" name="age" <?php if($v['age']): ?>checked<?php endif; ?> required />
          <label for="age">I confirm that I am aged 18 or over.</label>
        </div>

      </fieldset>

      <fieldset>

        <legend>Select payment method to pay <span id="signup-cost-confirm" class="signup-cost">&pound;<output><?php echo number_format ($v['quantity']*$v['draws']*BLOTTO_TICKET_PRICE/100,2,'-',','); ?></output></span></legend>

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

