<!doctype html>
<html class="no-js" lang="">

  <head>

    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My.Org</title>

    <script defer src="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/winners.js'); ?>"></script>
    <style>

#lottery-results-latest-table,
#lottery-winners-latest-table {
    margin-top:         1em;
    min-width:          15em;
    table-layout:       fixed;
    border-collapse:    collapse;
    border:             1px solid #4a2100;
}

#lottery-results-latest-table:first-of-type,
#lottery-winners-latest-table:first-of-type {
    margin-top:         0;
}

#lottery-results-latest-table caption,
#lottery-winners-latest-table caption {
    margin:             0  0  0.5em 0;
    text-align:         left;
    font-weight:        bold;
}

#lottery-results-latest-table th,
#lottery-winners-latest-table th,
#lottery-results-latest-table td,
#lottery-winners-latest-table td {
    padding:            0.3em 0.5em 0.3em 0.5em;
    font-size:          0.8em;
}

#lottery-results-latest-table th:first-of-type,
#lottery-winners-latest-table th:first-of-type,
#lottery-results-latest-table td:first-of-type,
#lottery-winners-latest-table td:first-of-type {
    width:              50%;
}

#lottery-results-latest-table thead,
#lottery-winners-latest-table thead {
    display:            none;
}

#lottery-results-latest-table tbody tr:nth-child(odd),
#lottery-winners-latest-table tbody tr:nth-child(odd) {
    background-color:   #f7f7f7;
}

#lottery-results-latest-table tbody tr:nth-child(even),
#lottery-winners-latest-table tbody tr:nth-child(even) {
    background-color:   #eeeeee;
}

#lottery-results-latest-table tbody td:nth-child(2),
#lottery-winners-latest-table tbody td:nth-child(2) {
  text-align: right;
}

    </style>

  </head>

    <body>

      <h2>Welcome to My.Org</h2>

      <div id="lottery-winners-latest" data-dateformat="jS M Y"></div>

    </body>

  </html>


