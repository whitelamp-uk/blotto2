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
table#draw-summary-super tbody td:nth-of-type(2),
table#draw-summary-super tbody td:nth-of-type(3) {
    text-align:         right;
}
#invoice-ref {
}
#invoice-address {
}
.invoice-large {
    font-size:  1.6em;
    font-weight: bold;
}
.invoice-pre {
    white-space: pre-line;
    margin-left: 2em;
}
table.invoice tfoot {
    font-weight: bold;
}
table.invoice td {
    font-family: 'courier new';
    text-align: right;
}
table.invoice td:nth-of-type(1) {
    font-family: inherit;
    text-align: left;
}
    </style>
  </head>
  <body>
<?php echo $snippet; ?>
  </body>
</html>
