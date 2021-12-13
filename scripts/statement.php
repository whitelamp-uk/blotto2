
<div class="statement">

  <section id="report-header">

    <?php require (__DIR__.'/../www/views/logo-invoice.php'); ?>

    <address><?php require __DIR__.'/../www/views/address.php'; ?></address>
  </section>

  <section class="report-large">Lottery proceeds <?php echo htmlspecialchars ($stmt->from); ?> through <?php echo htmlspecialchars ($stmt->to); ?></section>

  <p class="report-description"><?php echo htmlspecialchars ($stmt->description); ?></p>

<?php
    $table = table (
        'statement',
        'statement',
        'Lottery proceeds calculation',
        null,
        $stmt->rows,
        true
    );
?>

</div>

