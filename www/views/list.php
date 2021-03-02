<?php

$tables         = array (
    'ANLs'             => 'ANLs by issue date',
    'Cancellations'    => 'Cancellations by cancel date',
    'Changes'          => 'Canvassing company changes by date',
    'Draws'            => 'Draw entries by close date',
    'Insurance'        => 'Insurance by draw close date',
    'Supporters'       => 'Supporters by create date',
    'Updates'          => 'CRM data by date updated',
    'Wins'             => 'Winners by draw close date'
);

$days       = [];
$months     = [];
$day1       = day_one($table=='Wins')->format ('Y-m-d');
$day2       = null;
$last       = false;
$dates      = [];


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
$days       = array_reverse (weeks(BLOTTO_WEEK_ENDED,$day1,$day2));
$months     = array_reverse (months($day1,$day2));

if (array_key_exists(0,$days)) {
    $latest = $days[0];
}
else {
    $latest = day_yesterday($day2)->format('Y-m-d');
}


foreach ($months as $m=>$month) {
    if (!array_key_exists($m,$dates)) {
        $dates[$m] = array ();
    }
    foreach ($days as $f) {
        if (substr($f,0,8)!=substr($m,0,8)) {
            continue;
        }
        array_push ($dates[$m],$f);
    }
    $dates[$m] = array_reverse ($dates[$m]);
}


?>

    <script src="./media/list.js"></script>

    <section id="list" class="content">

        <h2><?php echo htmlspecialchars($tables[$table]); ?></h2>
        <span class="all-data-choices">
          <button data-interval="all" data-date="<?php echo $latest; ?>">All data</button>
          <div class="choices">
            <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$latest)); ?>">View</button>
            <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$latest)); ?>">CSV</button>
          </div>
          <form id="list-aux">
            <input type="checkbox" name="excel_friendly_zero" /><label>Excel-friendly &apos;012</label>
<?php if(in_array($table,['Cancellations','Supporters','Draws'])): ?>
            <input type="radio" name="group_by_ticket_number" value="0" checked /><label>1 row/ticket</label>
            <input type="radio" name="group_by_ticket_number" value="1" /><label>1 row/member</label>
<?php endif; ?>
          </form>
        </span>

<?php foreach($dates as $m=>$days): ?>
<?php     if(count($days)): ?>
<?php         $mlabel = date('Y M',strtotime($m)); ?>
        <div class="month">
          <h3><?php echo htmlspecialchars($mlabel); ?></h3>
          <span class="month-choices">
            <button data-interval="month" data-date="<?php echo $m; ?>"><?php echo htmlspecialchars($mlabel); ?></button>
            <div class="choices">
              <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$m,'P1M')); ?>">View</button>
              <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$m,'P1M')); ?>">CSV</button>
            </div>
          </span>
          <div class="fridays">
<?php         foreach($days as $f): ?>
            <span class="friday">
              <button data-interval="week" data-date="<?php echo $f; ?>"><?php echo $f; ?></button>
              <div class="choices">
                <button data-go="view" data-url="<?php echo htmlspecialchars(link_query('adminer',$table,$f,'P7D')); ?>">View</button>
                <button data-go="pull" data-url="<?php echo htmlspecialchars(link_query('download',$table,$f,'P7D')); ?>">CSV</button>
              </div>
            </span>
<?php         endforeach; ?>
          </div>
        </div>
<?php     endif; ?>
<?php endforeach; ?>

    </section>

    <script>
document.body.classList.add ('framed');
window.top.menuActivate ('<?php echo $table; ?>');
dateActivate ();
groupSet ();
    </script>



