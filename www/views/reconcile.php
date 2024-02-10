<?php
$supersum   = "summary";
$daye       = day_yesterday()->format ('Y-m-d');
$day1       = day_one (true);
if ($day1) {
    $day1   = $day1->format ('Y-m-d');
}
else {
    $day1   = $daye;
}
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
    <script>
var profits = <?php echo profits(); ?>

    </script>


    <section class="content">
      <h2>Summary data</h2>
    </section>

    <form id="profit" data-price="<?php echo intval (BLOTTO_TICKET_PRICE) ?>">

      <table>
        <caption><strong>Profit analysis</strong> &nbsp; &nbsp;<small class="wrn">&nbsp; Caution! This tool is experimental... &nbsp;</small></caption>
        <tbody>
          <tr>
            <td><strong>Profit history</strong></td>
            <td>&nbsp;</td>
            <td><a title="Download historical profit data as HTML" class="link-profit html history" download="profit_history_<?php echo date('Y-m-d'); ?>.html" href="#"><img /></a><a title="Download historical profit data as CSV" class="link-profit csv history" download="profit_history_<?php echo date('Y-m-d'); ?>.csv" href="#"><img /></a></td>
          </tr>
          <tr>
            <td>Mean avg days sign-up to import</td>
            <td><input name="days_signup_import" type="number" min="5" max="10" step="0.1" data-reset="8" value="8" data-dp="1" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Mean avg days import to first play</td>
            <td><input name="days_import_entry" type="number" min="20" max="100" step="0.1" data-reset="30" value="30" data-dp="1" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Abortive cancellation <strong>%</strong> (of previous import)</td>
            <td><input name="abortive_pct" type="number" min="5.0" max="10.0" step="1" data-reset="8.00" value="8.00" data-dp="2" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Attritional cancellation <strong>%</strong> (of previous ticket count)</td>
            <td><input name="attritional_pct" type="number" min="2.00" max="4.00" step="0.01" data-reset="3.20" value="3.20" data-dp="2" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Tickets in play at projection start</td>
            <td><input name="tickets" type="number" min="0" max="1000000" step="1" value="0" data-reset="0" data-dp="0" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Mean avg chances per sign-up</td>
            <td><input name="cps" type="number" min="1.00" max="2.00" step="0.01" data-reset="1.30" value="1.30" data-dp="2" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>Sign-ups <strong>pcm</strong></td>
            <td><input name="supporters" type="number" min="100" max="1000" step="1" data-reset="217" value="217" data-dp="0" /></td>
            <td><a onclick="var i=this.closest('tr').querySelector('input');i.value=i.dataset.reset;i.dispatchEvent(new Event('input'));return false" title="Reset to 12-month average">Reset</a></td>
          </tr>
          <tr>
            <td>[per week]</td>
            <td><input name="supporters_pw" type="number" min="20" max="200" step="1" data-reset="50" value="50" data-dp="0" /></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td><strong>Profit projection</strong></td>
            <td>&nbsp;</td>
            <td><a title="Download projected profit data as HTML" class="link-profit html projection" download="profit_projection_<?php echo date('Y-m-d'); ?>.html" href="#"><img /></a><a title="Download projected profit data as CSV" class="link-profit csv projection" download="profit_projection_<?php echo date('Y-m-d'); ?>.csv" href="#"><img /></a></td>
          </tr>
        </tbody>
      </table>

      <ol data-profit-headings>
        <li title="History or projection">type</li>
        <li title="Month number">month_nr</li>
        <li title="Month">month</li>
        <li title="Average days from signup to import (ANL creation)">days_signup_import</li>
        <li data-positize title="Supporters loaded">supporters</li>
        <li data-positize title="Chances loaded">chances</li>
        <li data-negatize title="Cancellations with no money collected">abortive</li>
        <li data-negatize title="Cancellations after money collected">attritional</li>
        <li title="Average days from import to first play (draw entry)">days_import_entry</li>
        <li title="Number of draws">draws</li>
        <li title="Number of plays (draw entries)">entries</li>
        <li data-positize title="Revenue generated from plays">revenue</li>
        <li data-negatize title="Fee ticket">ticket</li>
        <li data-negatize title="Fee winners">winner_post</li>
        <li data-negatize title="Fee insurance">insure</li>
        <li data-negatize title="Fee loading">loading</li>
        <li data-negatize title="Fee ANL emails">anl_email</li>
        <li data-negatize title="Fee ANL postage">anl_post</li>
        <li data-negatize title="Fee ANL texts">anl_sms</li>
        <li data-negatize title="Fee admin">admin</li>
        <li data-negatize title="Fee email general">email</li>
        <li data-negatize title="Uninsured payout">payout</li>
        <li data-positize title="Lottery profit">profit</li>
        <li title="Lottery balance at month end">balance</li>
        <li title="Tickets playing at month end">tickets</li>
        <li title="CCR cancellation figures">ccr_cancels</li>
      </ol>

    </form>

    <script>
window.document.addEventListener (
    'DOMContentLoaded',
    function (evt) {
        var elm;
        for (elm of document.querySelectorAll('#profit a.history')) {
            elm.addEventListener (
                'click',
                linkProfitHistory
            );
        }
        for (elm of document.querySelectorAll('#profit a.projection')) {
            elm.addEventListener (
                'click',
                linkProfitProjection
            );
        }
        for (elm of document.querySelectorAll('#profit input[type="number"]')) {
            elm.addEventListener (
                'input',
                inputProfitParameter
            );
        }
        inputProfitSet (evt);
        linkProfitHeadingsCcrCancellations (evt);
    }
);
    </script>

    <section id="reconciliation">

      <form class="dates" method="get" action="./">
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
    'reconciliation-table',
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

      <section class="reconcile" id="reconcile-table-revenue-ccc">
        <a
          title="Download revenue by CCC as CSV"
          class="link-resource link-csv"
          download="revenue_ccc_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.csv"
          href="#"><img /></a>
        <a
          title="Download revenue by CCC as HTML"
          class="link-resource link-table"
          download="revenue_ccc_<?php echo htmlspecialchars($from); ?>_thru_<?php echo htmlspecialchars($to); ?>.html"
          href="#"><img /></a>
<?php
table (
    'revenue-ccc',
    'summary',
    'Revenue by CCC '.date_reformat($from,'Y M d').' to '.date_reformat($to,'Y M d'),
    ['CCC','Revenue '.BLOTTO_CURRENCY],
    revenue ($from,$to)
);
?>
        <script>
//negatise ('#reconciliation td:nth-of-type(3)');
linkTable (html,'revenue-ccc');
linkCsv ('revenue-ccc');
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


