<?php
$files = [];
if (defined('BLOTTO_DIR_STATEMENT') && is_dir(BLOTTO_DIR_STATEMENT)) {
    $scan = scandir (BLOTTO_DIR_STATEMENT.'/', SCANDIR_SORT_DESCENDING);
    foreach ($scan as $f) {
        if ($f != '.' && $f != '..') {
            $files[$f] = filemtime (BLOTTO_DIR_STATEMENT.'/'.$f);
        }
    }
    arsort ($files);
    $files = array_keys ($files);
}
else {
    $err = "Statements directory not correctly configured";
}
?>

    <section id="statements" class="content">

        <h2>Statements</h2>

<?php if (!count($files)): ?>
          <p>No statements found</p>
<?php endif; ?>

<?php foreach ($files as $f): ?>
          <div>
            <a class="statement" onclick="window.unloadSuppress=true;return true" href="?statement=<?php echo htmlspecialchars ($f); ?>">
              <?php echo htmlspecialchars ($f); ?>
            </a>
          </div>
<?php endforeach; ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('statements');
    </script>



