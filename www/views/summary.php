<?php
    $since = new DateTime ();
    $since->sub (new DateInterval('P3M'));
    $since = $since->format ('Y-m-d');
    $since_text = "3 months to date";
?>

    <script src="./media/visual.js"></script>

    <section id="visual" class="content">

      <h2>Summary</h2>

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
            title: 'Recent draw activity to <?php echo month_end_last('d M Y'); ?>',
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
            title: 'Tickets per player',
            link: true
        }
    );
    console.log ('Rendered data2');
}
      </script>

      <hr/>

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
            title: 'Recent recruitment and cancellation (except one-off payments)',
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
            title: 'Cumulative recruitment and cancellation',
            link: true,
            zero: true
        }
    );
    console.log ('Rendered data4');
}
      </script>

      <hr/>

      <section id="chart5" class="chart left">
        <?php echo links_report ('retention_of_ongoing_direct_debits',4,'Duration (months)',1); ?>
        <canvas id="retention-playing"></canvas>
      </section>
      <script>
var data5 = <?php echo chart (4,'graph',1); ?>;
if (data5) {
    chartRender (
        'retention-playing',
        'bar',
        data5,
        {
            title: 'Ongoing direct debit retention to <?php echo htmlspecialchars(day_cancels_known('j M Y')); ?> (<?php echo str_replace(' ','-',strtolower(BLOTTO_CANCEL_RULE)); ?> rule)',
            link: true,
            zero: true,
            noLegend: true
        }
    );
    console.log ('Rendered data5');
}
      </script>

      <section id="chart6" class="chart right">
        <?php echo links_report ('retention_of_cancelled_direct_debits',4,'Duration (months)',1,true); ?>
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
            title: 'Cancelled direct debit retention at <?php echo day_yesterday()->format ('j M Y'); ?> (<?php echo str_replace(' ','-',strtolower(BLOTTO_CANCEL_RULE)); ?> rule)',
            link: true,
            zero: true,
            noLegend: true
        }
    );
    console.log ('Rendered data6');
}
      </script>

      <hr/>

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
            title: "Canvassing company performance (cumulative import/retention)",
            link: true
        }
    );
    console.log ('Rendered data8');
}
      </script>

      <hr/>

<!--
      <section id="chart9" class="chart left">
        <?php // echo links_report ('retention_by_postcode_area',5,'Postal area'); ?>
        <canvas id="retention-geographical"></canvas>
      </section>
      <script>
var data9 = <?php // echo chart (5,'graph'); ?>;
/*
if (data9) {
    chartRender (
        'retention-geographical',
        'bar',
        data9,
        {
            title: 'Retention (ppt) by postcode area',
            link: true
        }
    );
}
*/
      </script>

      <hr/>
-->

<?php endif; ?>

      <script>
document.body.classList.add ('framed');
window.top.menuActivate ('summary');
      </script>

    </section>


