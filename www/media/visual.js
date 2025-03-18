

function chartDownload (evt) {
    var link;
    link = evt.chart.canvas.parentElement.querySelector ('.link-image');
    link.setAttribute ('href',evt.chart.toBase64Image());
}

function chartFloat (result,sf) {
    return parseFloat (result.toPrecision(sf));
}

function chartRange (cdo,yratio) {
    if (yratio<1) {
       yratio       = 1 / yratio;
    }
    var datum,dmin,dmax,min,max,range,rounding,set,res;
    for (set of cdo.datasets) {
        for (datum of set.data) {
            datum = chartFloat (datum,6);
            if (dmin===undefined || dmin>datum) {
                dmin = datum;
            }
            if (dmax===undefined || dmax<datum) {
                dmax = datum;
            }
        }
    }
    if (dmax==dmin) {
        if (!dmin) {
            dmin    = 1;
            dmax    = 1;
        }
        dmin       -= Math.abs (dmin/2);
        dmax       += Math.abs (dmax/2);
    }
    range           = chartFloat (dmax-dmin,2);
    tick            = chartFloat (Math.pow(10,Math.floor(Math.log10(range)-1)),2);
    if (dmin<0 && dmax<0) {
        min         = chartFloat (dmin-tick,2);
        max         = chartFloat (dmin+(yratio*range)+tick,2);
    }
    else if (dmin<0 || dmax<0) {
        min         = chartFloat (dmin-tick,2);
        max         = chartFloat (dmax+tick,2);
    }
    else {
        min         = chartFloat (dmax-(yratio*range),2);
        max         = chartFloat (dmax+tick,2);
    }
    min             = chartFloat (min-(min%tick),2);
    max             = chartFloat (max-(max%tick),2);
    return {
        dmin: dmin,
        dmax: dmax,
        tick: tick,
        min: min,
        max: max
    }
}

function chartRender (canvasId,type,cdo,options) {
    // Types: bar, horizontalBar, pie, line, doughnut, radar, polarArea
    var c,comp,cht,cnv,clrs,cs,i,j,k,opts,range,test,style;
    try {
        cnv = document.getElementById (canvasId);
        cnv.parentElement.setAttribute ('title',options.title);
    }
    catch (e) {
        console.error ('Could not identify canvas "'+canvasId+'"');
        return false;
    }
    if (!cdo) {
        console.error ('No CDO given');
        return false;
    }
    if (cdo.seconds_to_execute>0) {
        console.log (canvasId+' - server took '+cdo.seconds_to_execute+' seconds to execute')
    }
    // Transform Chart Definition Object to produce data for Chart.js
    clrs = 24; // sane number of automatic colours
    cnv = cnv.getContext ('2d');
    test = document.createElement ('p');
    document.body.appendChild (test);
    style = {
        title: {},
        grid: {},
        legend: {}
    };
    test.classList.add ('chart-title');
    comp = getComputedStyle (test);
    style.title = {
        color: comp.getPropertyValue ('color'),
        fontFamily: comp.getPropertyValue ('font-family')
    };
    test.classList.remove ('chart-title');
    test.classList.add ('chart-grid');
    comp = getComputedStyle (test);
    style.grid = {
        backgroundColor: comp.getPropertyValue ('background-color'),
        color: comp.getPropertyValue ('color')
    };
    test.classList.remove ('chart-grid');
    test.classList.add ('chart-legend');
    comp = getComputedStyle (test);
    style.legend = {
        // Chart.js font size is integer 1 thru 12 (no CSS units)
        fontSize: 1 * comp.getPropertyValue('font-size').replace(/[^0-9]*$/,''),
        color: comp.getPropertyValue ('color')
    };
    test.classList.remove ('chart-legend');
    for (i=0;cdo.datasets[i];i++) {
        c = cdo.datasets[i].backgroundColor;
        if (Array.isArray(c)) {
            cs = [];
            k = 0;
            colors:
            while (c.length) {
                for (j=0;j<c.length;j++) {
                    k++;
                    if (k>clrs) {
                        break colors;
                    }
                    test.classList.add ('chart-bar-'+c[j]);
                    comp = getComputedStyle (test);
                    cs.push (comp.getPropertyValue('background-color'));
                    test.classList.remove ('chart-bar-'+c[j]);
                }
            }
            cdo.datasets[i].backgroundColor = cs;
            continue;
        }
        if (parseInt(c)!=c) {
            continue;
        }
        if (c==0) {
            cs = [];
            for (j=1;j<=clrs;j++) {
                test.classList.add ('chart-bar-'+j);
                comp = getComputedStyle (test);
                cs.push (comp.getPropertyValue('background-color'));
                test.classList.remove ('chart-bar-'+j);
            }
            cdo.datasets[i].backgroundColor = cs;
            continue;
        }
        test.classList.add ('chart-bar-'+c);
        comp = getComputedStyle (test);
        cdo.datasets[i].backgroundColor = comp.getPropertyValue('background-color');
        test.classList.remove ('chart-bar-'+c);
    }
    document.body.removeChild (test);
    // Define Chart.js options
    opts = {
        title: {
            display: true,
            text: options.title,
            fontFamily: style.title.fontFamily,
            fontColor: style.title.color
        },
        legend: {
            align: 'start',
            labels: {
                fontSize: style.legend.fontSize,
                fontColor: style.legend.color
            },
            position: 'top'
        }
    }
    if (options.noLegend) {
        opts.legend.display = false;
    }
    else if (['line','doughnut','radar','polarArea'].includes(type)) {
        opts.legend.position = 'right';
    }
    if (['bar','horizontalBar','line'].includes(type)) {
        opts.scales = {
            xAxes: [
                {
                    gridLines: {
                        color: style.grid.backgroundColor
                    },
                    ticks: {
                        fontColor: style.grid.color
                    }
                }
            ],
            yAxes: [
                {
                    gridLines: {
                        color: style.grid.backgroundColor
                    },
                    ticks: {
                        fontColor: style.grid.color,
                        beginAtZero: options.zero
                    }
                }
            ]
        }
        if (options.yratio && !options.zero) {
            range = chartRange (cdo,options.yratio);
            opts.scales.yAxes[0].ticks.suggestedMin = range.min;
            opts.scales.yAxes[0].ticks.suggestedMax = range.max;
        }
    }
    if (options.ynoticks) {
        opts.scales.yAxes[0].ticks.callback = function (value, index, values) {
            return '';
        }
    }
    if (options.ylogarithmic) {
        opts.scales.yAxes[0].type = 'logarithmic';
        // Convert things like 1e+0 into numbers for the less technically-minded
        if (!options.ynoticks) {
            opts.scales.yAxes[0].ticks.callback = function (value, index, values) {
                if (value>0) { // disagree with ChartJS about labelling 0 on a log scale
                    return Number (value.toString());
                }
                return '';
            }
        }
    }
    if (options.link) {
        opts.bezierCurve = false;
        opts.animation = {
            onComplete: chartDownload
        }
    }
    // Deploy Chart.js
    cht = new Chart (
        cnv,
        {
            type: type,
            data: cdo,
            options: opts
        }
    );
}


