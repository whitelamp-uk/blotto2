<?php

$msg                    = [];

$types = [
    'Account'          => 'I need to discuss the account',
    'Custom reports'   => 'I need a custom report',
    'Export'           => 'I need a lot of data exported',
    'Import'           => 'I need a batch of data to be imported',
    'GDPR risk'        => 'I need to report a data protection concern*',
    'GDPR breach'      => 'I need to report a known data breach*',
    'Bug report'       => 'I need to report a bug'
];
if (defined('BLOTTO_TICKETS_GRATIS') && BLOTTO_TICKETS_GRATIS) {
    $types['Reserve tickets'] = 'I would like to reserve more tickets';
}
$types['Other']         = 'Something else';
$type                   = '';
if (array_key_exists('type',$_POST)) {
    $type               = $_POST['type'];
}
$message                = '';
if (array_key_exists('message',$_POST)) {
    $message            = trim ($_POST['message']);
}

if (count($_POST)) {
    if (!array_key_exists('type',$_POST) || !array_key_exists($_POST['type'],$types)) {
        $msg            = ['Please select a support type'];
    }
    elseif (!array_key_exists('message',$_POST) || strlen($message)<5) {
        $msg            = ['Please type a full message'];
    }
    if (!count($msg)) {
        $org            = strtoupper (BLOTTO_ORG_USER);
        $body           = "User: {$_SESSION['blotto']} @ $org\n";
        $body          .= "Sent to: ".BLOTTO_EMAIL_TO."\n";
        $body          .= "Support type: $type\n";
        $body          .= "Message:\n$message\n\n";
        mail (
            BLOTTO_EMAIL_TO,
            BLOTTO_BRAND." for $org support request [$type]",
            $body
        );
        header ('Location: ?support&completed');
        exit;
    }
}

?>

    <section id="support" class="content">


        <h2>Request for support</h2>

<?php if (array_key_exists('completed',$_GET)): ?>

<?php   $msg = ['Your message has been sent, we will reply as soon as possible']; ?>

<?php else: ?>

        <form action="" method="post">
          <table>
          	<tbody>
          	  <tr>
                <td>Support type</td>
                <td>
                  <button type="submit" name="support">Post</button>
                  <select name="type">
                    <option value="">Select:</option>
<?php   foreach ($types as $value=>$description): ?>
                    <option value="<?php echo htmlspecialchars ($value); ?>" <?php if ($value==$type): ?>selected="selected"<?php endif; ?> >
                      <?php echo htmlspecialchars($description); ?>
                    </option>
<?php   endforeach; ?>
                  </select>
                </td>
          	  </tr>
              <tr>
                <td>&nbsp;</td>
                <td>
                  <small><sup>*</sup> Please make sure that your message does not contain sensitive data - we will get back to you</small>
                </td>
              </tr>
          	  <tr>
                <td>Message</td>
                <td>
                  <textarea name="message"><?php echo htmlspecialchars ($message); ?></textarea>
                </td>
          	  </tr>
          	</tbody>
          </table>
        </form>

<?php endif; ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('support');
    </script>



