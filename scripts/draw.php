
<div class="draw">

  <section id="draw-header">

    <?php require (__DIR__.'/../www/views/logo-invoice.php'); ?>

    <address><?php require __DIR__.'/../www/views/address.php'; ?></address>
  </section>

  <section id="draw-ref" class="draw-large">Draw report <?php echo htmlspecialchars ($draw->reference); ?></section>

<?php
    $table = table (
        'draw-winnings',
        'draw',
        'Winners for this draw',
        [ "Amount", "Name", "Ticket" ],
        $draw->wins,
        true
    );
    $table = table (
        'draw-properties',
        'draw',
        'Summary',
        null,
        [
            [ "Date:", $draw->date ],
            [ "Winning number:".plural(count($draw->results)), implode(', ',$draw->results) ],
            [ "Payout:", htmlspecialchars(BLOTTO_CURRENCY).number_format($draw->total,2) ],
            [ "Players:", "$draw->players (last draw $draw->players_prv)" ],
            [ "Tickets:", "$draw->tickets (last draw $draw->tickets_prv)" ]
        ],
        true
    );
    $table = table (
        'draw-entries',
        'draw',
        'Entries to this draw',
        [ "CCC", "Players", "Tickets" ],
        $draw->entries,
        true
    );
?>

</div>
