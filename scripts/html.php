<html class="no-js" lang="">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lottery Report <?php echo htmlspecialchars ($title); ?></title>
    <style>
table {
    margin-top:         1em;
    table-layout:       fixed;
    border-collapse:    collapse;
    border:             1px solid #4a2100;
}
table caption {
    white-space:        nowrap;
    margin-bottom:      0.3em;
    text-align:         left;
}
th, td {
    padding:            0.3em 0.5em 0.3em 0.5em;
    font-size:          0.8em;
}
tbody tr:nth-child(odd) {
    background-color:   hsla(60,1%,92%,1);
}
tbody tr:nth-child(even), tfoot tr {
    background-color:   hsla(60,1%,85%,1);
}
table#reconciliation tbody tr:nth-of-type(1),
table#reconciliation tbody tr:nth-of-type(12) {
    font-weight:        bold;
}
tfoot >tr > td {
    text-align:         center;
}
table#reconciliation tbody td:nth-of-type(1),
table#reconciliation tbody td:nth-of-type(3):first-letter {
    text-transform:     uppercase;
}
table#reconciliation tbody td:nth-of-type(1),
table#reconciliation tbody td:nth-of-type(2) {
    text-align:         right;
}
table#draw-summary tbody td:nth-of-type(3),
table#draw-summary tbody td:nth-of-type(4) {
    text-align:         right;
}
table#revenue-ccc thead th:nth-of-type(2),
table#revenue-ccc tbody td:nth-of-type(2) {
    text-align:         right;
}
table#draw-summary-super tbody td:nth-of-type(2),
table#draw-summary-super tbody td:nth-of-type(3) {
    text-align:         right;
}
div.draw,
div.invoice,
div.report,
div.statement {
    margin: 2em;
    width: 50em;
    font-family: 'arial','sans';
}
div.statement {
    text-align: center;
}
div.draw table.draw {
    margin: 2em 0 0 0;
    font-size: 1.2em;
}
div.invoice table.invoice {
    width: 100%;
    font-size: 1.2em;
}
div.statement table {
    margin: 2em auto 0 auto;
    font-size: 1.2em;
}
#draw-header,
#invoice-header,
#report-header {
    text-align: center;
}
#draw-header svg,
#invoice-header svg,
#report-header svg {
    width: 10em;
    height: 5.1em;
}
#draw-header img,
#invoice-header img,
#report-header img {
    width: 10em;
    height: 5.1em;
}

#draw-header address,
#invoice-header address,
#report-header address,
#statement-header address {
    padding: 2em 0 3em 0;     
    font-size: 0.8em;
    font-style: normal;
    color: #797979;
}
#draw-ref,
#invoice-ref {
    text-align: center;
}
#invoice-date {
}
#invoice-address {
}
#draw-winnings {
    float: right;
    margin: 2em 0 0 0;
}
#invoice-game {
}
#invoice-payout {
}
#invoice-terms {
}
.draw-large,
.invoice-large,
.report-large {
    font-size: 1.6em;
    font-weight: bold;
}
.invoice-pre {
    white-space: pre-line;
    margin-left: 2em;
}
table.draw caption,
table.invoice caption,
table.statement caption {
    font-size: 0.9em;
}
table.draw caption {
    font-weight: bold;
}
table.statement caption {
    text-align: center;
}
table.draw tfoot,
table.invoice tfoot {
    font-weight: bold;
}
table.invoice td {
    font-family: 'courier new';
    text-align: right;
}
table.draw td,
table.invoice td:nth-of-type(1) {
    font-family: inherit;
    text-align: left;
}
table.statement td:nth-of-type(1),
table.statement td:nth-of-type(2),
table.statement td:nth-of-type(3) {
    font-family: 'courier new';
}
table.statement td:nth-of-type(2) {
    text-align: right;
}
table.statement tr:nth-of-type(1),
table.statement tr:nth-of-type(7),
table.statement tr:nth-of-type(13),
table.statement tr:nth-of-type(24) {
    font-weight: bold;
}
table.statement tr:nth-of-type(16),
table.statement tr:nth-of-type(17),
table.statement tr:nth-of-type(18),
table.statement tr:nth-of-type(19),
table.statement tr:nth-of-type(20),
table.statement tr:nth-of-type(21),
table.statement tr:nth-of-type(22) {
    color: #797979;
}
table.statement tr:nth-of-type(16) td:nth-of-type(4),
table.statement tr:nth-of-type(17) td:nth-of-type(4),
table.statement tr:nth-of-type(18) td:nth-of-type(4),
table.statement tr:nth-of-type(19) td:nth-of-type(4),
table.statement tr:nth-of-type(20) td:nth-of-type(4),
table.statement tr:nth-of-type(21) td:nth-of-type(4),
table.statement tr:nth-of-type(22) td:nth-of-type(4) {
    padding-left: 3em;
}
    </style>
  </head>
  <body>
<?php echo $snippet; ?>
  </body>
</html>
