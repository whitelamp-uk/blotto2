<?php

require __DIR__.'/../scripts/functions.php';

function calcdraw1 ( ) {
    global $coll1,$importdate,$payday,$dcday,$model,$insuredays;
    $coll1 = collection_startdate ($importdate,$payday);
    if ($model=='accrue') {
        $dc1            = draw_first_zaffo_model ($coll1,$dcday);
    }
    elseif ($model=='asap') {
        $dc1            = draw_upcoming_weekly ($dcday,$coll1);
        // How many days from first collection to insurance deadline?
        $d1             = new \DateTime ($coll1);
        $d2             = new \DateTime ($dc1);
        $days           = $d1->diff($d2)->format ('%r%a');
        if ($days<$insuredays) {
            // There is not enough time
            // The first draw for this collection date
            // must be the following one so add a day...
            $d2->add (new \DateInterval('P1D'));
            // ... in order to find the next draw
            $dc1        = draw_upcoming_weekly ($dcday,$d2->format('Y-m-d'));
        }
    }
    return $dc1;
}


$reverse            = false;
if (count($_POST)) {
    $importdate     = $_POST['importdate'];
    $payday         = $_POST['payday'];
    $deliverydays   = $_POST['deliverydays'];
    $cooloff        = $_POST['cooloff'];
    $dcday          = $_POST['dcday'];
    $insuredays     = $_POST['insuredays'];
    $model          = $_POST['model'];
    $dc1date        = $_POST['dc1date'];
    if (array_key_exists('calculate_reverse',$_POST) && $_POST['calculate_reverse']) {
        $reverse    = true;
    }
}
else {
    $importdate     = gmdate ('Y-m-d');
    $payday         = 1;
    $deliverydays   = 6;
    $cooloff        = 5;
    $dcday          = 5;
    $insuredays     = 0;
    $model          = 'accrue';
    $dc1date        = '';
}

$wait = 'P'.($deliverydays+$cooloff).'D';
define ( 'BLOTTO_DD_COOL_OFF_DAYS', $wait );
define ( 'BLOTTO_DRAW_CLOSE_1', '2001-01-01' );
define ( 'BLOTTO_PAY_DELAY', 'P2D' );
if ($reverse) {
    $dt = new \DateTime ($dc1date);
    while (1) {
        $dt->sub (new \DateInterval('P1D'));
        $importdate = $dt->format ('Y-m-d');
        if (($dc1=calcdraw1())<=$dc1date) {
            break;
        }
    }
}
else {
    $dc1 = calcdraw1 ();
}

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
    <link rel="icon" href="./media/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="./media/favicon.ico" type="image/x-icon" />

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="author" href="http://www.thefundraisingfoundry.com/" />
    <title title="First draw calculator by The Fundraising Foundry">First draw calculator</title>

    <style>

section {
    margin-top: 1em;
    margin-left: 0.3em;
}
body > section {
    margin-top: 0;
    position: fixed;
    top: 0;
    left: 0;
    background-color: white;
}
#years {
    margin-top: 0;
    position: fixed;
    top: 2em;
    left: 2em;
    border-style: solid;
    border-width: 1px;
    padding: 1em;
    background-color: white;
}

label {
    display: inline-block;
    width: 20em;
}
#years label {
    display: inline-block;
    width: 12em;
}
label strong {
    font-style: italic;
}

input,
select {
    width: 12em;
}
input[type="number"] {
    text-align: right;
}
input[type="date"],
select {
    text-align: center;
}
input[type="submit"] {
    width: auto;
    text-align: center;
    margin-right: 1.2em;
    font-weight: bold;
}

table {
    position: absolute;
    top:  0;
    right: 0;
    border-spacing: 1em 0;
    text-align: right;
    font-family: 'courier new';
    font-size: 0.9em;
}
thead {
    position: sticky;
    top:  0;
    right: 0;
    background-color: white;
}
th {
    text-align: left;
}
td {
    padding-right: 2em;
}
td.positive {
    color: black;
}

.negative {
    color: darkred;
}
.self-fund-no {
    background-color: hsla(40,100%,97.5%,1.0);
}
.self-fund-yes {
    background-color: hsla(120,100%,97.5%,1.0);
}
.summary {
    display: inline-block;
    width: 9em;
    text-align: center;
}

    </style>

  </head>

  <body id="profit-calculator">

      <form method="post" action="">

         <section>
           <h3>First draw calculator</h3>
         </section>

        <section>
          <label>Date of import</label>
          <input name="importdate" type="date" value="<?php echo htmlspecialchars ($importdate); ?>" />
        </section>

        <section>
          <label>Payment day</label>
          <input name="payday" type="number" min="1" max="28" step="1" value="<?php echo intval($payday); ?>" />
        </section>

        <section>
          <label>Days from import to postal delivery</label>
          <input name="deliverydays" type="number" min="6" max="6" step="1" value="<?php echo intval($deliverydays); ?>" />
        </section>

        <section>
          <label>Days from postal delivery to first collection (cooling-off)</label>
          <input name="cooloff" type="number" min="3" max="10" step="1" value="<?php echo intval($cooloff); ?>" />
        </section>

        <section>
          <label>Draw close day</label>
          <select name="dcday">
            <option value="5" <?php if ($dcday==5): ?>selected<?php endif; ?> >Friday</option>
            <option value="4" <?php if ($dcday==4): ?>selected<?php endif; ?> >Thursday</option>
          </select>
        </section>

        <section>
          <label>Days from insurance close to draw close</label>
          <input name="insuredays" type="number" min="0" max="3" step="1" value="<?php echo intval($insuredays); ?>" />
        </section>

        <section>
          <label>First draw model</label>
          <select name="model">
            <option value="accrue" <?php if ($model=='accrue'): ?>selected<?php endif; ?> >Continuity by accrued balance</option>
            <option value="asap" <?php if ($model=='asap'): ?>selected<?php endif; ?> >ASAP</option>
          </select>
        </section>

        <section>
          <label>&nbsp;</label>
          <span class="summary"><input name="calculate_draw" type="submit" value="Recalculate" /></span>
        </section>

        <section>
          <label>First collection</label>
          <span id="start-date" class="summary"><?php echo htmlspecialchars ($coll1); ?></span>
        </section>

        <section>
          <label>First draw close</label>
          <span id="draw-close" class="summary"><?php echo htmlspecialchars ($dc1); ?></span>
        </section>

        <section>
          <h3>Reverse calculation</h3>
        </section>

        <section>
          <label>First draw close</label>
          <input name="dc1date" type="date" value="<?php echo htmlspecialchars ($dc1date); ?>" />
        </section>

        <section>
          <label>&nbsp;</label>
          <span class="summary"><input name="calculate_reverse" type="submit" value="Recalculate" /></span>
        </section>

      </form>

  </body>

</html>


