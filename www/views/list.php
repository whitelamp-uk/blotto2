<?php

$tables         = [
    'ANLs'             => 'ANLs by issue date',
    'Cancellations'    => 'Cancellations by cancel date',
    'Changes'          => 'Canvassing company returns by date',
    'Draws'            => 'Draw entries by close date',
    'Insurance'        => 'Insurance by draw close date',
    'MoniesWeekly'     => 'Profit analysis by lottery week ending (Friday)',
    'Supporters'       => 'Supporters by create date (1 row / ticket)',
    'SupportersView'   => 'Supporters by create date (1 row / member)',
    'Updates'          => 'CRM data by date updated',
    'Wins'             => 'Winners by draw close date'
];

$org_code   = BLOTTO_ORG_USER;
$days       = [];
$months     = [];
$day1       = day_one (in_array($table,['MoniesWeekly','Wins']));
$day2       = null;
$last       = false;
$dates      = [];
$dow        = BLOTTO_WEEK_ENDED;
if ($table=='Changes') {
    // From/to days for reporting are different - defined in org config by start_value
    $zo = connect (BLOTTO_CONFIG_DB);
    if ($zo) {
        // Get the first found weekly CCR schedule
        $qs = "
          SELECT
            `format`
           ,`start_value`
          FROM `blotto_schedule`
          WHERE `type`='ccr'
            AND `org_code`='$org_code'
            AND ( `interval`='P7D' OR `interval`='P1W' )
          LIMIT 0,1
          ;
        ";
        try {
            $schedule = $zo->query ($qs);
            $schedule = $schedule->fetch_assoc ();
            if ($schedule) {
                // Today
                $try = new \DateTime ();
                // Wind forward to next start day for this schedule
                $count = 0;
                while ($try->format($schedule['format'])!=$schedule['start_value']) {
                    // Next schedule start day
                    $try->add (new \DateInterval('P1D'));
                    // Sanity
                    $count++;
                    if ($count>7) {
                        throw new \Exception ('Insane start_value or format');
                    }
                }
                // Previous day is week-ended day
                $try->sub (new \DateInterval('P1D'));
                $dow = $try->format ('w');
            }
        }
        catch (\mysqli_sql_exception $e) {
            error_log ('Error retrieving day of week from CCR schedule: '.$e->getMessage());
        }
        catch (\Exception $e) {
            error_log ('Error retrieving day of week from CCR schedule: '.$e->getMessage());
        }
    }
}


if ($day1) {
    $day1 = $day1->format ('Y-m-d');
    if ($table=='ANLs' || $table=='Wins') {
// Stop doing this because it is horrendously slow and Crucible in any event is a daily reporting system
//        www_letter_status_refresh ();
    }
    if ($table=='Insurance') {
        $day2   = new DateTime ();
        $day2->add (new DateInterval('P7D'));
        $day2   = $day2->format ('Y-m-d');
    }
    elseif ($table=='Wins') {
        $day2   = new DateTime (win_last());
        $day2->add (new DateInterval('P7D'));
        $day2   = $day2->format ('Y-m-d');
    }
    $days       = array_reverse (weeks($dow,$day1,$day2));
    $months     = array_reverse (months($day1,$day2));
}

if (array_key_exists(0,$days)) {
    $latest = $days[0];
}
else {
    $latest = day_yesterday($day2)->format('Y-m-d');
}


foreach ($months as $m=>$month) {
    if (!array_key_exists($m,$dates)) {
        $dates[$m] = [];
    }
    foreach ($days as $f) {
        if (substr($f,0,8)!=substr($m,0,8)) {
            continue;
        }
        $dates[$m][] = $f;
    }
    $dates[$m] = array_reverse ($dates[$m]);
}


?>

    <script src="./media/list.js"></script>

    <section id="list" class="content">

          <form id="list-aux">
            <h3>CSV options</h3>
<?php if(in_array($table,['Cancellations','Supporters','Draws'])): ?>
            <input type="radio" name="group_by_ticket_number" value="0" checked /><label>1 row/ticket</label>
            <input type="radio" name="group_by_ticket_number" value="1" /><label>1 row/member</label>
            &nbsp; &nbsp;
<?php endif; ?>
            <input type="checkbox" name="excel_leading_zero" /><label>Excel-friendly &apos;012</label>
          </form>

        <h2><?php echo htmlspecialchars($tables[$table]); ?></h2>

        <div class="row">
          <button class="col1 subtitle" data-interval="all" data-date="<?php echo $latest; ?>">All data</button>
          <span class="col2 choices">
            <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$latest)); ?>">View</button>
            <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$latest)); ?>">CSV</button>
          </span>
        </div>

<?php foreach($dates as $m=>$days): ?>
<?php     if(count($days)): ?>
<?php         $mlabel = date('Y M',strtotime($m)); ?>
        <div class="row month">
          <h3 class="col1"><?php echo htmlspecialchars($mlabel); ?></h3>
          <span class="col2 month-choices">
            <button data-interval="month" data-date="<?php echo $m; ?>">Month</button>
            <span class="choices">
              <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$m,'P1M')); ?>">View</button>
              <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$m,'P1M')); ?>">CSV</button>
            </span>
          </span>
          <span class="col3 fridays">
<?php         foreach($days as $f): ?>
            <span class="friday">
              <button data-interval="week" data-date="<?php echo $f; ?>"><?php echo $f; ?></button>
              <span class="choices">
                <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$f,'P7D')); ?>">View</button>
                <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$f,'P7D')); ?>">CSV</button>
              </span>
            </span>
<?php         endforeach; ?>
          </span>
        </div>
<?php     endif; ?>
<?php endforeach; ?>

    </section>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('<?php echo $table; ?>');
dateActivate ();
groupSet ();
elzSet ();
    </script>



