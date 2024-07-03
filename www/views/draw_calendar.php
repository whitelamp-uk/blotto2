<?php

// draw calendar dates from the beginning through a year ahead
$dcs            = [];
$dt             = new \DateTime (BLOTTO_DRAW_CLOSE_1);
$end            = new \DateTime ();
$end->add (new DateInterval('P12M'));
$end            = $end->format ('Y-m-d');
$count          = 0;
while (($next=$dt->format('Y-m-d'))<=$end) {
    // sanity
    $dc = draw_upcoming ($next);
    $dcs[] = $dc;
    $dt = new \DateTime ($dc);
    $dt->add (new \DateInterval('P1D'));
    $count++;
    if ($count>=1000) {
        echo "limited to $count dates\n";
        exit;
    }
}

?>

<script>
const calendarData = [
    '<?php echo implode ("'\n,    '",$dcs); ?>'
];
</script>

<style type="text/css">

* {
	margin: 0;
	padding: 0;
	font-family: 'Poppins', sans-serif;
}

/*
	display: flex;
	background: #888888;
	min-height: 100vh;
	padding: 0 10px;
	align-items: center;
	justify-content: center;
*/

.calendar-open {
    cursor: pointer;
}

.calendar-container {
    position: fixed;
    display: none;
    left: calc(50vw - 20em);
    top: calc(50vh - 16em);
    margin: auto;
    width: 40em;
    height: 32em;
	background: #fff;
	box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    padding-top: 0.5em;
    padding-left: 0.5em;
}

.calendar-container img.calendar-close {
    float: right;
    margin-top: 0.6em;
    margin-right: 0.6em;
    width: 2em;
    height: 2em;
    content: url('./media/close.png');  
	border-radius: 1em;
    cursor: pointer;
}

.calendar-container header {
	display: flex;
	align-items: center;
	padding: 25px 30px 10px;
	justify-content: space-between;
}

header .calendar-navigation {
	display: flex;
}

header .calendar-navigation span {
	height: 38px;
	width: 38px;
	margin: 0 1px;
	cursor: pointer;
	text-align: center;
	line-height: 38px;
	border-radius: 50%;
	user-select: none;
	color: #aeabab;
	font-size: 1.9rem;
}

.calendar-navigation span:last-child {
	margin-right: -10px;
}

header .calendar-navigation span:hover {
	background: #f2f2f2;
}

header .calendar-current-date {
	font-weight: 500;
	font-size: 1.45rem;
}

.calendar-body {
	padding: 20px;
}

.calendar-body ul {
	list-style: none;
	flex-wrap: wrap;
	display: flex;
	text-align: center;
}

.calendar-body .calendar-dates {
	margin-bottom: 20px;
}

.calendar-body li {
	width: calc(100% / 7);
	font-size: 1.07rem;
	color: #414141;
}

.calendar-body .calendar-weekdays li {
	cursor: default;
	font-weight: 500;
}

.calendar-body .calendar-dates li {
	margin-top: 30px;
	position: relative;
	z-index: 1;
	cursor: pointer;
}

.calendar-dates li::before {
	position: absolute;
	content: "";
	z-index: -1;
	top: 50%;
	left: 50%;
	width: 40px;
	height: 40px;
	border-radius: 50%;
	transform: translate(-50%, -50%);
}

.calendar-dates li label {
    display: block;
	position: absolute;
    margin-left: 5em;
    margin-top: -2.75em;
    width: 0;
    height: 0;
    overflow: visible;
    font-size: 0.7em;
}

.calendar-dates li label::before {
	position: absolute;
	content: "";
	z-index: -1;
	top: 50%;
	left: 50%;
	width: 15px;
	height: 15px;
	border-radius: 50%;
	transform: translate(-25%, 0%);
    background-color: #660000;
}

.calendar-dates li.inactive {
	color: transparent;
}

.calendar-dates li.draw {
	color: #fff;
}

.calendar-dates li.active::before {
	border-style: solid;
    border-width: 4px;
    border-color: #bb0000;
}

.calendar-dates li.draw::before {
    background-color: #000066;
}

.calendar-dates li:not(.draw):hover::before {
	background: #e4e1e1;
}

</style>

<div class="calendar-container">
  <img class="calendar-close" />
  <h2>Draw calendar</h2>
  <header class="calendar-header">
    <p class="calendar-current-date"></p>
    <div class="calendar-navigation">
      <span id="calendar-prev">&lt;</span>
      <span id="calendar-next">&gt;</span>
    </div>
  </header>
  <div class="calendar-body">
    <ul class="calendar-weekdays">
      <li>Sun</li>
      <li>Mon</li>
      <li>Tue</li>
      <li>Wed</li>
      <li>Thu</li>
      <li>Fri</li>
      <li>Sat</li>
    </ul>
  	<ul class="calendar-dates"></ul>
  </div>
</div>

