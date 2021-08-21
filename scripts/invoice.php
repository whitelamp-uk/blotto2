
<div class="invoice">

  <section id="invoice-header">

    <?php require (__DIR__.'/../www/view/logo-invoice.php'); ?>

    <address>Unit 3 Saxon Way ⎮ Melbourn ⎮ Cambridgeshire ⎮ SG8 6DN</address>
  </section>

  <section id="invoice-ref" class="invoice-large">Invoice <?php echo htmlspecialchars ($invoice->reference); ?></section>

  <section id="invoice-date">
    <h4>Date</h4>
    <div class="invoice-pre"><?php echo htmlspecialchars ($invoice->date); ?></div>
  </section>

  <section id="invoice-address">
    <h4>To</h4>
    <div class="invoice-pre"><?php echo htmlspecialchars ($invoice->address); ?></div>
  </section>

<?php
    $table = table (
        $invoice->html_table_id,
        'invoice',
        $invoice->description,
        [ "", "Quantity", "Unit price",  "Subtotal", "VAT", "Total" ],
        $invoice->items,
        true,
        [ $invoice->totals, $invoice->grand_total ]
    );
?>

  <section id="invoice-terms">
    <h4>Terms</h4>
    <div class="invoice-pre"><?php echo htmlspecialchars ($invoice->terms); ?></div>
  </section>

  <section id="invoice-payment-details">
    <h4>Bank details</h4>
    <div class="invoice-pre"><?php echo htmlspecialchars (BLOTTO_BANK_NAME."\n".BLOTTO_BANK_SORT."\n".BLOTTO_BANK_ACNR); ?></div>
  </section>

</div>

