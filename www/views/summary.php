<?php
// Determine year and month
if (array_key_exists('year',$_GET) && array_key_exists('month',$_GET)) {
    // form submitted
    if ($_GET['year'] && $_GET['month']) {
        // Form had value(s)
        $year   = intval ($_GET['year']);
        $month  = str_pad (intval($_GET['month']),2,'0',STR_PAD_LEFT);
    }
    else {
        // For all time
        $year   = null;
        $month  = null;
    }
}
else {
    // form not submitted so default to this month ending
    $dt         = new \DateTime ();
    $year       = intval ($dt->format ('Y'));
    $month      = $dt->format ('m');
}
// to and month ending dates
$ma             = 'recent/pending';
if ($year && $month) {
    // user-entered year/month specifies year-end ytd
    $dt         = new \DateTime ($year.'-'.$month.'-01');
    $dt->add (new DateInterval('P1M'));
    $dt->sub (new DateInterval('P1D'));
    $to         = $dt->format ('Y-m-d');
    $me         = 'YTD '.$dt->format ('d M Y');
    $ma         = $dt->format ('d M Y').', recent/pending';
    $dt->add (new DateInterval('P1D'));
    $dt->sub (new DateInterval('P12M'));
}
else {
    // for-all-time dates
    $year       = null;
    $month      = null;
    $dt         = new \DateTime (gmdate('Y-m-01'));
    $dt->sub (new DateInterval('P1D'));
    $to         = null;
    $me         = '(for all time)';
}
$months     = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
$today      = gmdate ('j M Y');
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

      <section id="chart7" class="chart left">
        <?php echo links_report ('sales_funnel',5,'Journeys',3); ?>
        <canvas id="workflow"></canvas>
      </section>
      <script>
var data7 = <?php echo chart (5,'graph','sales_funnel'); ?>;
if (data7) {
    chartRender (
        'workflow',
        'bar',
        data7,
        {
            title: "Sales funnel - supporter journeys (at this time)",
            link: true,
            ylogarithmic: true,
            ynoticks : true
        }
    );
    console.log ('Rendered data7');
}
      </script>

      <section id="chart5" class="chart left">
        <?php echo links_report ('new_player_first_draws_'.gmdate('Y-m-d'),7,"New player first draws $ma calculated @ $today",$to); ?>
        <canvas id="new-player-first-draws"></canvas>
      </section>
      <script>
var data5 = <?php echo chart (7,'graph','new_player_first_draws_'.gmdate('Y-m-d'),$to); ?>;
if (data5) {
    chartRender (
        'new-player-first-draws',
        'bar',
        data5,
        {
            title: 'New player first draws <?php echo $ma; ?> calculated @ <?php echo $today; ?>',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data5');
}
      </script>

      <section id="chart9" class="chart right" style="position:relative;">
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
var data9 = <?php echo chart (9,'graph','noshow_benchmarking',$to); ?>;
if (data9) {
    chartRender (
        'noshow-benchmarking',
        'bar',
        data9,
        {
            // empty title lines are to compensate for noLegend=true
            title: [ 'No-show benchmarking <?php echo $me; ?>',' ',' ',' ' ],
            link: true,
            zero: true,
            yratio: 1.3,
            noLegend: true,
            tooltipLabel: function (item) {
                return item.yLabel + '% no-shows';
            }
        }
    );
    console.log ('Rendered data9');
}
      </script>

      <section id="chart2" class="chart left doughnut">
        <?php echo links_report ('chances_per_player',2,'Chances'); ?>
        <canvas id="chances-distribution"></canvas>
      </section>
      <script>
var data2 = <?php echo chart (2,'graph','chances_per_player',$to); ?>;
if (data2) {
    chartRender (
        'chances-distribution',
        'doughnut',
        data2,
        {
            title: 'Chances per supporter signed up <?php echo $me; ?>',
            link: true
        }
    );
    console.log ('Rendered data2');
}
      </script>

      <section id="chart1" class="chart right">
        <?php echo links_report ('draw_activity',1,'Month (nr draws)'); ?>
        <canvas id="draw-activity"></canvas>
      </section>
      <script>
var data1 = <?php echo chart (1,'graph','draw_activity',$to); ?>;
if (data1) {
    chartRender (
        'draw-activity',
        'bar',
        data1,
        {
            title: 'Draw activity <?php echo $me; ?>',
            link: true,
            zero: false,
            yratio: 1.3
        }
    );
    console.log ('Rendered data1');
}
      </script>

<?php if (!defined('BLOTTO_RBE_ORGS')): ?>

      <section id="chart3" class="chart left">
        <?php echo links_report ('rolling_recruitment_and_cancellation',3,'Month'); ?>
        <canvas id="recruitment-rolling"></canvas>
      </section>
      <script>
var data3 = <?php echo chart (3,'graph','rolling_recruitment_and_cancellation',$to); ?>;
if (data3) {
    chartRender (
        'recruitment-rolling',
        'bar',
        data3,
        {
            title: 'Rolling recruitment/cancellation <?php echo $me; ?>',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data3');
}
      </script>

      <section id="chart4" class="chart right">
        <?php echo links_report ('cumulative_recruitment_and_cancellation',3,'Month',null,true); ?>
        <canvas id="recruitment-cumulative"></canvas>
      </section>
      <script>
var data4 = <?php echo chart (3,'graph','cumulative_recruitment_and_cancellation',$to,true); ?>;
if (data4) {
    chartRender (
        'recruitment-cumulative',
        'bar',
        data4,
        {
            title: 'Cumulative recruitment/cancellation <?php echo $me; ?>',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data4');
}
      </script>

      <section id="chart6" class="chart left">
        <?php echo links_report ('retention_of_cancelled_supporters',4,'Duration (months)',true); ?>
        <canvas id="retention-cancelled"></canvas>
      </section>
      <script>
var data6 = <?php $chart = chart (4,'graph','retention_of_cancelled_supporters',true); echo $chart; ?>;
<?php $game_age = json_decode ($chart); $game_age = $game_age->game_age; ?>
if (data6) {
    chartRender (
        'retention-cancelled',
        'bar',
        data6,
        {
            title: 'Cancelled supporter length of retention (<?php echo str_replace(' ','-',strtolower(BLOTTO_CANCEL_RULE)); ?> rule), age of game: <?php echo intval($game_age); ?> months',
            link: true,
            zero: true,
            noLegend: true,
            ylogarithmic: true,
            tooltipLabel: function (item) {
                return item.yLabel + ' cancellations';
            }
        }
    );
    console.log ('Rendered data6');
}
      </script>

      <section id="chart8" class="chart left">
        <?php echo links_report ('revenue_by_chances',10,'Duration (months)'); ?>
        <canvas id="revenue-chances"></canvas>
      </section>
      <script>
var data8 = <?php $chart = chart (10,'graph','revenue_by_chances'); echo $chart; ?>;
<?php $game_age = json_decode ($chart); $game_age = $game_age->game_age; ?>
if (data8) {
    chartRender (
        'revenue-chances',
        'bar',
        data8,
        {
            title: 'Revenue by chances per supporter, age of game: <?php echo intval($game_age); ?> months',
            link: true,
            zero: true,
            ylogarithmic: true,
            noLegend: true,
            yTick: function (value,index,values) {
                if (value>0) {
                    return Number(value.toString()) + '%';
                }
                return '';
            },
            tooltipLabel: function (item) {
                return item.yLabel + '%';
            }
        }
    );
    console.log ('Rendered data8');
}
      </script>

<?php endif; ?>

      <script>
document.body.classList.add ('framed');
window.top.menuActivate ('summary');
      </script>

    </section>


