<?php

require __DIR__.'/functions.php';
cfg ();
require $argv[1];

$posted = require $argv[2];

print_r ($posted);

/*

The purpose of this script is to take the posted data from a bespoke script eg blotto-crucible/php/bwh.new_player.php and use it to create a new mandate. Cancellation of the old mandate can be considered but this is usually possible via a dashboard.

For some providers eg RSM, the whole process can be done via the dashboard so this script is not needed to deploy the API.

Other providers eg Paysuite require that all mandates be created via the API, That is what this script should do.

Derive the ClientRef for the new player like this:
$next_cref = clientref_advance ($current_cref);

*/

exit (0);

