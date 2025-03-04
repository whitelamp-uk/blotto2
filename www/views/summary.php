<?php
$year           = null;
$month          = null;
$from           = null;
if (array_key_exists('year',$_GET) && array_key_exists('month',$_GET)) {
    if ($_GET['year'] && $_GET['month']) {
        $year   = intval ($_GET['year']);
        $month  = str_pad (intval($_GET['month']),2,'0',STR_PAD_LEFT);
    }
}
if ($year && $month) {
    // user-entered year/month specifies year-end ytd
    $dt         = new \DateTime ($year.'-'.$month.'-01');
    $dt->add (new DateInterval('P1M'));
    $dt->sub (new DateInterval('P1D'));
    $to         = $dt->format ('Y-m-d');
    $me         = $dt->format ('d M Y');
    $dt->add (new DateInterval('P1D'));
    $dt->sub (new DateInterval('P12M'));
    $from       = $dt->format ('Y-m-d');
}
else {
    // insufficient user input so load for-all-time data
    $year       = null;
    $month      = null;
    $dt         = new \DateTime (gmdate('Y-m-01'));
    $dt->sub (new DateInterval('P1D'));
    $to         = null;
    $me         = $dt->format ('d M Y');
}
$months     = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
?>

    <script src="./media/visual.js"></script>

    <section id="visual" class="content">

      <h2>Summary graphs</h2>

      <form class="dates" method="get" action="./">
        <input type="hidden" name="summary" />
        <input type="number" name="year" min="2000" value="<?php echo htmlspecialchars($year); ?>" placeholder="YTD year" />
        <select name="month">
          <option value="">YTD Month</option>
<?php for ($i=1;$i<=12;$i++): ?>
          <option value="<?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?>" <?php if ($month && $i==intval($month)): ?> selected <?php endif; ?> ><?php echo str_pad($months[$i-1],2,'0',STR_PAD_LEFT); ?></option>
<?php endfor; ?>
        </select>
        <input type="submit" value="Recalculate" />
      </form>

      <section id="chart9" class="chart left" style="position:relative;">
        <?php echo links_report ('noshow_benchmarking',9,'Month'); ?>
        <canvas id="noshow-benchmarking"></canvas>
        <div class="chart-overlay">
            <div class="chart-overlay-labels">
              <span class="chart-overlay-label">No-shows per 100 sign-ups&nbsp;</span>
              <span class="chart-overlay-box chart-overlay-1">&nbsp;</span> <span class="chart-overlay-label">Benchmark&nbsp;</span>
              <span class="chart-overlay-box chart-overlay-2">&nbsp;</span> <span class="chart-overlay-label">Outcome was better&nbsp;</span>
              <span class="chart-overlay-box chart-overlay-3">&nbsp;</span> <span class="chart-overlay-label">Outcome was worse&nbsp;</span>
            </div>
        </div>
      </section>
      <script>
var data9 = <?php echo chart (9,'graph',$to); ?>;
if (data9) {
    chartRender (
        'noshow-benchmarking',
        'bar',
        data9,
        {
            // empty title lines are to compensate for noLegend=true
            title: ['No-show benchmarking YTD <?php echo $me; ?>','',''],
            link: true,
            zero: true,
            yratio: 1.3,
            noLegend: true
        }
    );
    console.log ('Rendered data9');
}
      </script>

      <section id="chart1" class="chart left">
        <?php echo links_report ('recent_draw_activity',1,'Month'); ?>
        <canvas id="draw-activity"></canvas>
      </section>
      <script>
var data1 = <?php echo chart (1,'graph'); ?>;
if (data1) {
    chartRender (
        'draw-activity',
        'bar',
        data1,
        {
            title: 'Draw activity (recent)',
            link: true,
            zero: false,
            yratio: 1.3
        }
    );
    console.log ('Rendered data1');
}
      </script>

      <section id="chart2" class="chart right doughnut">
        <?php echo links_report ('tickets_per_player',2,'Chances'); ?>
        <canvas id="ticket-distribution"></canvas>
      </section>
      <script>
var data2 = <?php echo chart (2,'graph'); ?>;
if (data2) {
    chartRender (
        'ticket-distribution',
        'doughnut',
        data2,
        {
            title: 'Tickets per player (all-time)',
            link: true
        }
    );
    console.log ('Rendered data2');
}
      </script>

<?php if (!defined('BLOTTO_RBE_ORGS')): ?>

      <section id="chart3" class="chart left">
        <?php echo links_report ('recent_recruitment_and_cancellation',3,'Month'); ?>
        <canvas id="recruitment"></canvas>
      </section>
      <script>
var data3 = <?php echo chart (3,'graph'); ?>;
if (data3) {
    chartRender (
        'recruitment',
        'bar',
        data3,
        {
            title: 'Recruitment/cancellation (recent)',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data3');
}
      </script>

      <section id="chart4" class="chart right">
        <?php echo links_report ('cumulative_recruitment_and_cancellation',3,'Month',true); ?>
        <canvas id="recruitment-cumulative"></canvas>
      </section>
      <script>
var data4 = <?php echo chart (3,'graph',true); ?>;
if (data4) {
    chartRender (
        'recruitment-cumulative',
        'bar',
        data4,
        {
            title: 'Cumulative recruitment/cancellation (recent)',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data4');
}
      </script>



<!--
MP: I think this one is pretty pointless
      <section id="chart5" class="chart left">
        <?php // echo links_report ('retention_of_ongoing_direct_debits',4,'Duration (months)',1); ?>
        <canvas id="retention-playing"></canvas>
      </section>
      <script>
var data5 = <?php // echo chart (4,'graph',1); ?>;
if (data5) {
    chartRender (
        'retention-playing',
        'bar',
        data5,
        {
            title: 'Retention (recent) using <?php // echo str_replace(' ','-',strtolower(BLOTTO_CANCEL_RULE)); ?> rule',
            link: true,
            zero: true,
            noLegend: true
        }
    );
    console.log ('Rendered data5');
}
      </script>
-->

      <section id="chart6" class="chart right">
        <?php echo links_report ('retention_of_cancelled_supporters',4,'Duration (months)',1,true); ?>
        <canvas id="retention-cancelled"></canvas>
      </section>
      <script>
var data6 = <?php echo chart (4,'graph',1,true); ?>;
if (data6) {
    chartRender (
        'retention-cancelled',
        'bar',
        data6,
        {
            title: 'Retention of cancelled supporters (all-time) using <?php echo str_replace(' ','-',strtolower(BLOTTO_CANCEL_RULE)); ?> rule',
            link: true,
            zero: true,
            noLegend: true
        }
    );
    console.log ('Rendered data6');
}
      </script>

      <section id="chart8" class="chart right doughnut">
        <?php echo links_report ('ccc_performance_cumulative',6,'CCC'); ?>
        <canvas id="ccc-cumulative"></canvas>
      </section>
      <script>
var data8 = <?php echo chart (6,'graph'); ?>;
if (data8) {
    chartRender (
        'ccc-cumulative',
        'doughnut',
        data8,
        {
            title: "Canvassing company performance (cumulative import/retention, ignoring single payments)",
            link: true
        }
    );
    console.log ('Rendered data8');
}
      </script>

<?php
$since = new DateTime ($to);
$since->sub (new DateInterval('P3M'));
$since = $since->format ('Y-m-d');
$since_text = "3 months to date";
?>
      <section id="chart7" class="chart left doughnut">
        <?php echo links_report ('ccc_performance_recent',5,'CCC',3); ?>
        <canvas id="ccc-recent"></canvas>
      </section>
      <script>
var data7 = <?php echo chart (5,'graph',$since); ?>;
if (data7) {
    chartRender (
        'ccc-recent',
        'doughnut',
        data7,
        {
            title: "Canvassing company performance (imports <?php echo $since_text; ?>)",
            link: true
        }
    );
    console.log ('Rendered data7');
}
      </script>

<?php endif; ?>

      <script>
document.body.classList.add ('framed');
window.top.menuActivate ('summary');
      </script>

    </section>


