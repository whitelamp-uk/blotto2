<?php
if (defined('BLOTTO_TICKETS_GRATIS') && BLOTTO_TICKETS_GRATIS) {
}
else {
    $err = "This feature is not configured";
}

$download = false;
if (array_key_exists('download',$_GET)) {
    $download = true;
}

$tickets = [];
if (array_key_exists('csv_file',$_FILES)) {
    $errors = [];
    $ignored = [];
    $sold = [];
    try {
        $tickets = gratis_parse ($_FILES['csv_file']['tmp_name']);
    }
    catch (\Exception $e) {
        $err = $e->getMessage ();
    }
}


$earliest = '2024-07-12';
$latest = '2024-10-18';

?>

<?php if (count($tickets)): ?>
<pre>$tickets = <?php var_export ($tickets); ?></pre>
<?php endif; ?>

    <section id="gratis" class="content">

      <h2>Tickets sold externally</h2>

      <form method="post" enctype="multipart/form-data">

        <table class="report">
          <tbody>
            <tr>
              <td colspan="3"><h3>Ticket report</h3></td>
            </tr>
<?php foreach (array_reverse(gratis_report()) as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars ($row['occurred_at']); ?></td>
              <td><?php echo htmlspecialchars ($row['status']); ?></td>
              <td><?php echo intval ($row['chances']); ?></td>
            </tr>
<?php endforeach; ?>
            <tr>
              <td>Reserve more</td>
              <td colspan="2"><a href="./?support">Contact support</a></td>
            </tr>
            <tr>
            <tr>
              <td colspan="3"><h3>Download tickets</h3></td>
            </tr>
              <td>Collect all unsold tickets</td>
              <td colspan="2"><a href="./?gratiscollect" onclick="window.removeEventListener('beforeunload',unloading);return true">Download</a></td>
            </tr>
            <tr>
              <td colspan="3"><h3>Upload sales</h3></td>
            </tr>
            <tr>
              <td>Draw close date</td>
              <td colspan="2"><input type="date" id="draw_closed" name="draw_closed" min="<?php echo htmlspecialchars ($earliest); ?>" max="<?php echo htmlspecialchars ($latest); ?>"/> <a href="#" class="calendar-open" data-selectandclose="draw_closed">Draw calendar</a></td>
            </tr>
            <tr>
              <td>CSV data</td>
              <td colspan="2"><input type="file" id="csv_file" name="csv_file"/>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td colspan="2"><a href="#" onclick="this.closest('form').submit();return false">Upload</a>
            </tr>
          </tbody>
        </table>

      </form>

    </section>


<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('gratis');
    </script>



