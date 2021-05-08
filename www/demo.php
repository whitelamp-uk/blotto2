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

#features {
    position: absolute;
    box-sizing: border-box;
    left: 0;
    top: 8em;
    padding-left: 1em;
}

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

#lottery-winners-latest-table tbody td:nth-child(2):before {
  content: "£";
}

#lottery-signup-heading {
    position: absolute;
    left: 18em;
    top: 0em;
    width: calc(100vw - 18em);
    margin: 0;
}

#lottery-signup {
    position: absolute;
    box-sizing: border-box;
    left: 18em;
    top: 2em;
    width: calc(100vw - 18em);
    height: calc(100vh - 10em);
    border-style: none;
    overflow-x: auto;
    overflow-y: scroll;
}

    </style>

  </head>

    <body>

      <h2>Welcome to My.Org</h2>

      <p>In order to add these features to your website, view this page&#039;s source code and simply copy/paste/tweak. Ask your administrator if you need any support.</p>

      <div id="features">

        <!-- Latest results and latest winners -->
        <!-- List of format characters: https://www.php.net/manual/en/datetime.format.php -->
        <div id="lottery-winners-latest" data-dateformat="jS M Y"></div>

        <h4 id="lottery-signup-heading">Sign-up form</h4>

        <!-- Public sign-up of -->
        <!-- Remove demo=1 in order to activate the sign-up form -->
        <!-- Use css=[my stylesheet URL] to override form styling as demonstrated here -->
        <iframe id="lottery-signup" src="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/tickets.php'); ?>?demo=1&amp;css=https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/demo.css'); ?>"></iframe>

      </div>

    </body>

  </html>


