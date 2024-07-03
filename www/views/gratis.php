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

$sold = 0;
$reserved = 1000;
$assigned = [];

$earliest = '2024-07-12';
$latest = '2024-10-18';

?>

    <section id="gratis" class="content">

      <h2>Tickets sold externally</h2>

      <form method="post" enctype="multipart/form-data">

        <table class="report">
          <tbody>
            <tr>
              <td colspan="2"><h3>Reserve tickets</h3></td>
            </tr>
            <tr>
              <td>Sold</td>
              <td><?php echo intval ($sold); ?></td>
            </tr>
            <tr>
              <td>Reserved</td>
              <td><?php echo intval ($reserved); ?></td>
            </tr>
            <tr>
              <td>Reserve more</td>
              <td><a href="./?support">Contact support</a></td>
            </tr>
            <tr>
              <td>Collect</td>
              <td><a href="./?gratiscollect" onclick="window.removeEventListener('beforeunload',unloading);return true">Unused tickets</a></td>
            </tr>
          </tbody>
          <tbody>
            <tr>
              <td colspan="2"><h3>Assign more tickets</h3></td>
            </tr>
            <tr>
              <td>Draw close date</td>
              <td><input type="date" id="draw_closed" name="draw_closed" min="<?php echo htmlspecialchars ($earliest); ?>" max="<?php echo htmlspecialchars ($latest); ?>"/> <a href="#" class="calendar-open" data-selectandclose="draw_closed">Draw calendar</a></td>
            </tr>
          </tbody>
          <tbody>
            <tr>
              <td colspan="2"><h3>Assigned tickets</h3></td>
            </tr>
<?php if (!count($assigned)): ?>
            <tr>
              <td colspan="2">No assigned tickets were found</td>
              <td><?php echo intval ($ts); ?></td>
            </tr>
<?php endif; ?>
<?php foreach ($assigned as $dc=>$ts): ?>
            <tr>
              <td><?php echo htmlspecialchars ($dc); ?></td>
              <td><?php echo intval ($ts); ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>

      </form>

    </section>


<?php require __DIR__.'/messages.php'; ?>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('gratis');
    </script>



