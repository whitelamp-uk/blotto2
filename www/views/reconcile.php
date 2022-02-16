<?php
$supersum   = "summary";
$day1       = day_one(true)->format ('Y-m-d');
$daye       = day_yesterday()->format ('Y-m-d');
$dayy       = substr($daye,0,4).'-01-01';
if (array_key_exists('from',$_GET)) {
    $from   = $_GET['from'];
}
else {
    $from   = $dayy;
}
if (array_key_exists('to',$_GET)) {
    $to     = $_GET['to'];
}
else {
    $to     = $daye;
}
if (array_key_exists('sort',$_GET)) {
    $sort   = $_GET['sort'];
}
else {
    $sort   = 'date';
}
?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.1.0/papaparse.min.js"></script>
    <script src="./media/reconcile.js"></script>
    <script>
var html = `<?php html ("{{SNIPPET}}\n","{{TITLE}}"); ?>`;
    </script>

    <section id="reconcile" class="content">

      <h2>Summary data</h2>

      <form method="get" action="./">
        <input type="hidden" name="reconcile" />
        <input type="hidden" name="reconcile" />
        <input type="date" pattern="\d{4}-\d{2}-\d{2}" name="from" min="<?php echo htmlspecialchars($day1); ?>" max="<?php echo htmlspecialchars($daye); ?>" value="<?php echo htmlspecialchars($from); ?>" />
        <input type="date" pattern="\d{4}-\d{2}-\d{2}" name="to" min="<?php echo htmlspecialchars($day1); ?>" max="<?php echo htmlspecialchars($daye); ?>" value="<?php echo htmlspecialchars($to); ?>" />
        <input type="submit" value="Recalculate" />
      </form>

<?php if (!defined('BLOTTO_RBE_ORGS')): ?>
<?php  $supersum   = "super-summary"; ?>
      <section class="reconcile" id="reconcile-table-reconcile">
        <a
          title="Download reconciliation as CSV"
          class="link-resource link-csv"
          download="reconciliation_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.csv"
          href="#"><img /></a>
        <a
          title="Download reconciliation as HTML"
          class="link-resource link-table"
          download="reconciliation_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.html"
          href="#"><img /></a>
<?php
table (
    'reconciliation',
    'summary',
    'Reconciliation '.date_reformat($from,'Y M d').' to '.date_reformat($to,'Y M d'),
    null,
    calculate ($from,$to)
);
?>
        <script>
negatise ('#reconciliation td:nth-of-type(3)');
linkTable (html,'reconciliation');
linkCsv ('reconciliation');
        </script>

      </section>

      <section class="reconcile" id="reconcile-table-draw-summary">
        <a
          title="Download draw summary as CSV"
          class="link-resource link-csv"
          download="draw_summary_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.csv"
        ><img /></a>
        <a
          title="Download draw summary as HTML"
          class="link-resource link-table"
          download="draw_summary_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.html"
        ><img /></a>
<?php
table (
    'draw-summary',
    'summary',
    'Draw summary '.date_reformat($from,'Y M d').' to '.date_reformat($to,'Y M d'),
    ['Draw closed','CCC','Supporters','Tickets'],
    draws ($from,$to)
);
?>
        <script>
linkTable (html,'draw-summary');
linkCsv ('draw-summary');
        </script>
      </section>

      <hr/>

<?php endif; ?>

      <section class="reconcile" id="reconcile-table-draw-summary-super">
        <a
          title="Download draw <?php echo htmlspecialchars($supersum); ?> as CSV"
          class="link-resource link-csv"
          download="draw_summary-super_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.csv"
        ><img /></a>
        <a
          title="Download draw <?php echo htmlspecialchars($supersum); ?> as HTML"
          class="link-resource link-table"
          download="draw_summary-super_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.html"
        ><img /></a>
<?php
table (
    'draw-summary-super',
    'summary-super',
    'Draw '.$supersum.' '.date_reformat($from,'Y M d').' to '.date_reformat($to,'Y M d'),
    ['Draw close date','Supporters','Tickets'],
    draws_super ($from,$to)
);
?>
        <script>
linkTable (html,'draw-summary-super');
linkCsv ('draw-summary-super');
        </script>
      </section>

      <hr/>

    </section>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('reconcile');
    </script>


