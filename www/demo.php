<?php
require '../config.www.php';
$org_code = explode ('/',BLOTTO_WWW_CONFIG);
$org_code = array_pop ($org_code);
$org_code = explode ('.',$org_code);
$org_code = array_shift ($org_code);
?><!doctype html>
<html class="no-js" lang="">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My.Org</title>
    <style>
      #features {
          position: absolute;
          box-sizing: border-box;
          left: 0;
          top: 8em;
          padding-left: 1em;
      }
    </style>
  </head>
  <body>
    <h2>Welcome to My.Org</h2>
    <p>In order to add these features to your website, view this page&#039;s source code and simply copy/paste/tweak. Ask your administrator if you need any support.</p>
    <div id="features">





<!-- 1. WINNER API -->

      <!--
        Latest results and latest winners
      -->
      <script defer src="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/winners.js'); ?>"></script>
      <style>
        /* Optional CSS */
        #lottery-results-latest-table,
        #lottery-winners-latest-table {
            margin-top:         1em;
            min-width:          18em;
            table-layout:       fixed;
            border-collapse:    collapse;
            border:             1px solid #4a2100;
        }
        #lottery-results-latest-table {
              /*
                Hide the number-match results table
                if the game has only raffle prizes
              */
/*            display:            none; */
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
              /*
                Hide the table column headings
              */
/*            display:            none; */
        }
        #lottery-results-latest-table thead th,
        #lottery-winners-latest-table thead th {
            text-align:         left;
        }
        #lottery-results-latest-table tbody tr:nth-child(odd),
        #lottery-winners-latest-table tbody tr:nth-child(odd) {
            background-color:   #f7f7f7;
        }
        #lottery-results-latest-table tbody tr:nth-child(even),
        #lottery-winners-latest-table tbody tr:nth-child(even) {
            background-color:   #eeeeee;
        }
        #lottery-results-latest-table thead th:nth-child(2),
        #lottery-results-latest-table tbody td:nth-child(2),
        #lottery-winners-latest-table thead th:nth-child(2),
        #lottery-winners-latest-table tbody td:nth-child(2) {
          text-align: right;
        }
      </style>
      <!--
        * The full list of date format characters:
          https://www.php.net/manual/en/datetime.format.php
        * For example 2022-04-30 in the format "jS M Y" becomes:
          30th Apr 2022
        * This code generates two tables:
          * #lottery-results-latest gives number-match results
            Use CSS displaywhich should be hidden if your game only has raffle
            prizes
            Shows: prize name, number drawn
          * #lottery-winners-latest gives winning tickets
            Shows: number drawn, prize name
      -->
      <div id="lottery-winners-latest" data-dateformat="jS M Y"></div>

<!-- 1 ENDS -->








      <h4 id="lottery-signup-heading">Get one-off-payment lottery tickets</h4>

<!-- 2. TICKETS ONLINE -->
      <style>
        /* Example CSS */
        :root {
            /* Move the sign-up form to the right by a certain amount */
            --lottery-left:      24em;
            /* Move the sign-up form down by a certain amount */
            --lottery-top:       2em;
        }
        #lottery-signup-heading {
            position: absolute;
            left: var(--lottery-left);
            top: 0em;
            width: calc(100vw - 1em - var(--lottery-left));
            margin: 0;
        }
        #lottery-signup {
            position: absolute;
            box-sizing: border-box;
            left: var(--lottery-left);
            top: var(--lottery-top);
            margin: 0;
            width: calc(100vw - 1em - var(--lottery-left));
            height: calc(100vh - var(--lottery-top));
            border-style: none;
            border-width: 0;
            overflow-x: auto;
            overflow-y: scroll;
        }
      </style>
      <!--
        * Remove demo=1 below in order to activate the sign-up form
        * Use css=[my stylesheet URL] to override form styling as demonstrated by
          the yellow background from:
            https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/demo.css'); ?>
      -->
      <iframe id="lottery-signup" src="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/tickets.php'); ?>?demo=1&amp;css=https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/demo.css'); ?>"></iframe>
      <!-- 2 ENDS -->



      <h4 id="lottery-signup-dd-heading">Self-service direct debit sign-up</h4>

<!-- 3. TICKETS (DIRECT DEBIT MANDATE) -->
      <style>
        /* Example CSS */
        :root {
            /* Move the sign-up form to the right by a certain amount */
            --lottery-left: 24em;
            /* Move the sign-up form down by a certain amount */
            --lottery-top: 2em;
        }
        #lottery-signup-dd-heading {
            position: absolute;
            left: var(--lottery-left);
            top: 105vh;
            width: calc(100vw - 1em - var(--lottery-left));
            margin: 0;
        }            
        #lottery-signup-dd {
            position: absolute;
            box-sizing: border-box;
            left: var(--lottery-left);
            top: calc(var(--lottery-top) + 105vh);
            margin: 0;
            width: calc(100vw - 1em - var(--lottery-left));
            height: calc(100vh - var(--lottery-top));
            border-style: none;
            border-width: 0;
            overflow-x: auto;
            overflow-y: scroll;
        }
      </style>
      <!
       * Use css=[my stylesheet URL] to override form styling
       * Replace ??? with your organisation code LOWER CASE
      >
      <iframe id="lottery-signup-dd" src="https://sss.burdenandburden.co.uk/?o=<?php echo $org_code; ?>&p=LT&amp;css=https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo str_replace('//','/',dirname($_SERVER['REQUEST_URI']).'/media/demo.css'); ?>"></iframe>
      <!-- 3 ENDS -->



      </div>
    </body>
  </html>


