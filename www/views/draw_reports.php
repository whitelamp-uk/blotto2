<?php
$files = [];
if (defined('BLOTTO_DIR_DRAW') && is_dir(BLOTTO_DIR_DRAW)) {
    $scan = scandir (BLOTTO_DIR_DRAW, SCANDIR_SORT_DESCENDING);
    foreach ($scan as $f) {
        if ($f != '.' && $f != '..') {
            $files[] = $f;
        }
    }
}
else {
    $err = "Draw report directory not correctly configured";
}
?>

    <section id="draw-reports" class="content">

        <h2>Draw reports</h2>

<?php if (!count($files)): ?>
          <p>No draw reports found</p>
<?php endif; ?>

<?php foreach ($files as $f): ?>
          <div>
            <a class="draw-report" onclick="window.unloadSuppress=true;return true" href="?drawreport=<?php echo htmlspecialchars ($f); ?>">
              <?php echo htmlspecialchars ($f); ?>
            </a>
          </div>
<?php endforeach; ?>

    </section>

<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('drawreports');
    </script>



