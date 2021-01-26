
function linkCsv (tableId) {
    var c,columns,cs,csv,data,ds,output,r,rows,rs,table;
    table = document.getElementById (tableId);
    link = table.parentElement.querySelector ('.link-csv');
    cs = table.querySelectorAll ('thead tr:first-of-type th');
    rs = table.querySelectorAll ('tbody tr');
    columns = [];
    for (c of cs) {
        columns.push (c.textContent);
    }
    rows = [];
    for (r of rs) {
        ds = r.querySelectorAll ('td');
        data = [];
        for (d of ds) {
            data.push (d.textContent);
        }
        rows.push (data);
    }
    if (columns.length) {
        csv = Papa.unparse ( { data: rows, fields: columns } );
    }
    else {
        csv = Papa.unparse ( { data: rows } );
    }
    link.setAttribute('href','data:text/csv;charset=utf-8,'+encodeURIComponent(csv));
}

function linkTable (html,tableId) {
    var caption,link,table;
    table = document.getElementById (tableId);
    caption = table.querySelector ('caption');
    link = table.parentElement.querySelector ('.link-table');
    html = html.replace ('{{SNIPPET}}',table.outerHTML);
    html = html.replace ('{{TITLE}}',caption.innerHTML);
    link.setAttribute('href','data:text/html;charset=utf-8,'+encodeURIComponent(html));
}

