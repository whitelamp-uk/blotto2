
    <form class="signup" onsubmit="return false">

<?php if ($api->success()): ?>

      <fieldset class="finished">

        <legend>Thank you</legend>

        <div>

          <h3>Sign-up completed successfully</h3>

          <p>Thank you for your support!</p>

          <p>Sign-up reference: <span class="signup reference"><?php echo htmlspecialchars ($api->reference()); ?></span></p>

          <p>You have been sent an email confirming your sign-up.</p>

        </div>

      </fieldset>

<?php else: ?>

      <fieldset class="finished">

        <legend>Thank you</legend>

        <div>

          <h3>Sorry that did not work</h3>

          <p>Reported message: <?php echo htmlspecialchars ($api->errorMessage()); ?></p>

<!--          <p>You can try again <a href="<?php echo  ($_GET['d'] ? './ticketsc.php?d=' . $_GET['d'] : './ticketsc.php'); ?>">here</a>.</p> -->
              <p>You can try again <a href="./ticketsc.php?fromfinishedc=1<?php echo ($_GET['d'] ? '&d=' . urlencode($_GET['d']) : ''); ?>" class="try-again-link">here</a>.</p>


        </div>

      </fieldset>

<?php endif; ?>

    </form>

