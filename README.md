
# blotto2
---------


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



