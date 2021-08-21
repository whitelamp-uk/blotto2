<?php
$files = [];
if (defined('BLOTTO_DIR_INVOICE') && is_dir(BLOTTO_DIR_INVOICE)) {
    $scan = scandir (BLOTTO_DIR_INVOICE, SCANDIR_SORT_DESCENDING);
    foreach ($scan as $f) {
        if ($f != '.' && $f != '..') {
            $files[] = $f;
        }
    }
}
else {
    $err = "Invoice directory not correctly configured";
}
?>

    <section id="invoices" class="content">

        <h2>Invoices</h2>

<?php if (!count($files)): ?>
          <p>No invoices found</p>
<?php endif; ?>

<?php foreach ($files as $f): ?>
          <div>
            <a class="invoice" onclick="window.unloadSuppress=true;return true" href="?invoice=<?php echo htmlspecialchars ($f); ?>">
              <?php echo htmlspecialchars ($f); ?>
            </a>
          </div>
<?php endforeach; ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('invoices');
    </script>



