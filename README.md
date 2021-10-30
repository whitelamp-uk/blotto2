
# blotto2
---------


Manually inserting external number-matches
==========================================

For example Zaffo's lotto.de first six digits of Saturday Spiel77.

# Draw closed date: 2021-02-19
# Last character of level method (aka group): 6
# Number to insert: 123456

root>$ /bin/bash /home/mark/blotto/blotto2/scripts/blotto.bash -m 2021-02-19 6 123456 /home/mark/blotto/config/mark.cfg.php

If results are shared (eg DBH and SHC used to share crucible_ticket_zaffo.blotto_result) then it is only necessary to add the numbers once using any of config files.


# To see rehearse by inspecting the insert SQL use one of:

root>$ /bin/bash /home/mark/blotto/blotto2/scripts/blotto.bash -rm 2021-02-19 6 123456 /home/mark/blotto/config/mark.cfg.php

root>$ /bin/bash /home/mark/blotto/blotto2/scripts/blotto.bash -mr 2021-02-19 6 123456 /home/mark/blotto/config/mark.cfg.php

root>$ /bin/bash /home/mark/blotto/blotto2/scripts/blotto.bash -r -m 2021-02-19 6 123456 /home/mark/blotto/config/mark.cfg.php

# In other words, parameters must immediately follow switch "m"



Cloning static data to a new game
=================================

Set up a new my_org.cfg.php and create any new databases that it needs.

Then use blotto.bash to pull the data in from the origin database:

root>$ /bin/bash /home/mark/blotto/blotto2/scripts/blotto.bash -c existing_db /my/clone.cfg.php

The new BLOTTO_MAKE_DB will then have the data from a selection of tables from existing_db. See the variable "$tables" in scripts/clone.php

These tables are cloned because at least one column in these tables cannot be derived from payment data or by calculation.



Making new charts
=================

A Chart Definition Object is a bespoke product which will be probably in-house but possibly commissioned. A CDO is a PHP object representing a JS object for Chart.js but it has the possibility of variable style references to CSS classes.

The CDO receives a list of parameters from chart() as an array $p and outputs $cdo. Then chart() prints the CDO as a JS object. The object is instantiated by chartRender() in visual.js to create a Chart.js object; modification of chartRender() offers more CSS options.

Up to 24 bar colour classes are recognised from .chart-bar-1 to .chart-bar-24; there are four ways to define bar colors (example snippet from, say, chart-0123.php):

// 1.  datasets[0] has all bars one hard-wired colour
$cdo->datasets[0]->backgroundColor = 'rgba(127,127,127,0.5)';

// 2. datasets[1] has all bars .chart-bar-5
$cdo->datasets[1]->backgroundColor = 5;

// 3. datasets[2] rotates around .chart-bar-1 thru .chart-bar-24
$cdo->datasets[2]->backgroundColor = 0;

// 4. datasets[3] rotates .chart-bar-1, .chart-bar-4, .chart-bar-3, .chart-bar-1, ...
$cdo->datasets[3]->backgroundColor = [1,4,3];


  * The charts are invoked by home page summary.php
  * The style guide variables are --blotto-color-bgd-chart-bar-* in guide.css
  * The classes themselves are in style.css

Chart.js trick
--------------

Chart.js has a limitation in that it can only handle one set of legend colours for a doughnut - labels are given the corresponding colours for datasets[0] which is the outer doughnut. So if you want a legend for each segment of each ring in the doughnut you need a couple of tricks which rely on a zero datapoint taking up no space and a zero-width ring being invisible.

See chart-0006.php which has a third outer ring which is hidden:
    $cdo->datasets[0]->weight = 0;

The real data is relegated to datasets[1] and datasets[2].

The legend is visually split into outer and inner with the use of a blank legend for a zero data point spliced into each dataset.




Notification bell
=================

To get a completion notification bell when running manually:

(A) Set BLOTTO_BELL to '1' in config file.

(B) Select bell option for profile in your terminal preferences.

(C) Activate terminal bell on your local OS.

    For example on Linux Mint you would use pulseaudio config:

    # Add this to /etc/pulse/default.pa
    load-sample-lazy bell /usr/share/sounds/freedesktop/stereo/complete.oga
    load-module module-x11-bell sample=bell

    # Either reboot local OS or:
    pulseaudio -k

    # blotto.bash uses these magic runes to ring the bell:
    echo $'\a'

Anonmymising data for demo
==========================
In the belief that the more realistic the data, the better the demo, 
I've preserved as much as possible, as follows.

Title (Mr / Mrs) is preserved; if it's gender neutral or absent then
I've assigned them a gender in the correct proportions.  Names are drawn
from a dataset of random but realistic full names - more Helens than
Hermiones, a McGregor is more likely to be Cameron than a Smith.

Email addresses are "first.last@fakeisp.com".  The first five digits of
phone numbers are preserved (basically, area code) but the rest
randomised (if they exist).

The postcode district is preserved ("CB12") but the rest randomised.  A
random streetname & town pair in the same district are assigned from an
Ordnance Survey dataset (so "New Dover Road" occurs with a Canterbury
postcode, not with an Aberdeen one), and a random house number.  County
is preserved if given.  Obvs the postcode doesn't actually match the
random streetname and is often not even a valid postcode.  Names don't 
match - just as many McGregors in Canterbury as Aberdeen.

Year of birth (if given) is preserved but the rest randomised.

And obviously, sortcodes and account numbers are totally random.

Data sources for anonmyisation
==============================
OS data is from here:
https://osdatahub.os.uk/downloads/open/OpenNames
and licensed like so:
http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/
Random names from here:
https://britishsurnames.co.uk/random