

function clickHandler (evt) {
    var element;
    if (evt.target.id=='adminer') {
        window.top.menuActivate ('adminer');
        return;
    }
    if ('date' in evt.target.dataset) {
        dateActivate (evt.target);
        return;
    }
    if ('go' in evt.target.dataset) {
        if (evt.target.dataset.url.indexOf('#huge')===0) {
           evt.preventDefault ();
           message ('Sorry, that is too much data via the web; request help from your account administrator','err');
        }
        dateGo (evt.target);
        return;
    }
    if (evt.target.getAttribute('name')=='excel_leading_zero') {
        elzSet (evt.target);
        return;
    }
    if (evt.target.getAttribute('name')=='group_by_ticket_number') {
        groupSet (evt.target);
        return;
    }
    if (evt.target.classList.contains('form-close')) {
        evt.preventDefault ();
        evt.target.form.classList.remove ('active');
    }
    if (evt.target.id=='post-mandate') {
        evt.preventDefault ();
        mandateUpdate (evt.target.form);
    }
    if (evt.target.id=='post-supporter') {
        evt.preventDefault ();
        supporterUpdate (evt.target.form);
    }
}

function findInQueryString (k) {
    var m;
    m = RegExp ('[?&]' + k + '=([^&]*)').exec (window.location.search);
    return m && decodeURIComponent (m[1].replace (/\+/g,' '));
}

function form2Query (form) {
    return (new URLSearchParams(new FormData(form))).toString();
}

function frameCheck ( ) {
    if (window.self==window.top) {
// alert ('frameCheck(): load in top = ./?P='+window.self.location.href);
        window.top.location.href = './?P='+window.self.location.href;
    }
    // Make sure URL of framed page is not ./?login or ./?wait
    if (window.self.location.href.indexOf('./?login')) {
        window.history.replaceState ({},document.title,window.top.home);
    }
    else if (window.self.location.href.indexOf('./?wait')) {
        window.history.replaceState ({},document.title,window.top.home);
    }
    else {
// alert ('frameCheck(): home = '+window.self.location.href);
        window.top.home = window.self.location.href;
    }
}

function handlers ( ) {
    document.body.addEventListener ('click',window.clickHandler);
    document.body.addEventListener ('submit',window.submitHandler);
    document.body.addEventListener ('input',window.inputHandler);
}

function inputActor (elementId) {
    if (['search','expert'].includes(elementId)) {
        supporterSearch (elementId);
    }
}

function inputHandler (evt) {
    if (!evt.target.id) {
        return;
    }
    if (!window.inputHandlerTO) {
        window.inputHandlerTO = {}
    }
    if (window.inputHandlerTO[evt.target.id]) {
        clearTimeout (window.inputHandlerTO[evt.target.id]);
    }
    window.inputHandlerTO[evt.target.id] = setTimeout ('inputActor("'+evt.target.id+'")',750);
}

function mandateSelect (evt) {
    evt.preventDefault ();
    var form,query,xhttp;
    query = './?search&t=m&r=' + evt.target.dataset.clientref;
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4 && this.status==200) {
            mandateSelectResult (xhttp.responseText);
        }
    };
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function mandateSelectResult (responseText) {
    var body,cancel,cell,day,fields,fname,form,heading,i,input,option,response,row,select;
    try {
        response = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
        }
        console.log ('Error: '+responseText);
        message ('Select request failed','err');
        return;
    }
    if ('error' in response) {
        message ('Select failed error='+response.error,'err');
        return;
    }
    form = document.getElementById ('change-mandate');
    form.ClientRef.value = response.data[0].ClientRef;
    heading = form.querySelector ('thead th:nth-of-type(2)');
    heading.textContent = response.data[0].ClientRef;
    response.data[0].Sortcode = '';
    response.data[0].Account = '';
    fields = {
        Name: "Account name",
        Sortcode: "Bank sort code",
        Account: "Bank account number",
        Freq: "Payment frequency",
        Amount: "Payment amount",
        StartDate: "Start date",
    }
    body = form.querySelector ('tbody');
    body.innerHTML = '';
    for (fname in fields) {
        row = document.createElement ('tr');
        cell = document.createElement ('td');
        cell.textContent = fields[fname];
        row.appendChild (cell);
        cell = document.createElement ('td');
        if (fname=='Freq') {
            select = document.createElement ('select');
            select.setAttribute ('name',fname);
            option = document.createElement ('option');
            option.value = 'Monthly';
            option.textContent = 'Monthly';
            select.appendChild (option);
            option.value = 'Annually';
            option.textContent = 'Annually';
            select.appendChild (option);
            cell.appendChild (select);
        }
        else if (fname=='StartDate') {
            day = new Date(Date.now()+1000*60*60*24*7); // 1 week
            input = document.createElement ('input');
            input.setAttribute ('type','date');
            input.setAttribute ('name',fname);
            input.min = day.toISOString().split("T")[0];
            cell.appendChild (input);
        }
        else {
            input = document.createElement ('input');
            input.setAttribute ('type','text');
            input.setAttribute ('name',fname);
            input.value = response.data[0][fname];
            cell.appendChild (input);
        }
        row.appendChild (cell);
        body.appendChild (row);
    }
    form.classList.add ('active');
}

function mandateUpdate (form) {
    var data,query,xhttp;
    query = './?update&t=m&r=' + form.ClientRef.value;
    data = new FormData (form);
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4 && this.status==200) {
            mandateUpdateResult (xhttp.responseText);
        }
    };
    try {
        xhttp.open ('POST',query,true);
        xhttp.send (data);
    }
    catch (e) {
        console.log (e.message);
    }
}

function mandateUpdateResult (responseText) {
    var form,table;
    form = document.getElementById ('change-mandate');
    table = form.querySelector ('table');
    try {
        results = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
        }
        console.log ('Error: '+responseText);
        message ('Update request failed','err');
        return;
    }
    if ('error' in results) {
        message ('Update failed error='+results.error,'err');
        return;
    }
    message ('BACS request posted - mandate available 5 working days');
    form.classList.add ('updated');
    form.classList.remove ('changed');
}

function message (msg,type='ok') {
    var element;
    element = document.createElement ('h4');
    element.classList.add ('splash');
    element.classList.add (type);
    element.textContent = msg;
    document.body.appendChild (element);
}

function menuActivate (buttonId) {
    var b, button, buttons;
    button = document.getElementById (buttonId);
    if (!button) {
        return;
    }
    buttons = document.querySelectorAll ('#options a');
    for (b of buttons) {
        if (b.id=='logoout' || b==button) {
            continue;
        }
        b.classList.remove ('active');
    }
    buttons = document.querySelectorAll ('#footer a');
    for (b of buttons) {
        if (b==button) {
            continue;
        }
        b.classList.remove ('active');
    }
    button.classList.add ('active');
}

function negatise (selector) {
    var e, elements;
    elements = document.querySelectorAll (selector);
    for (e of elements) {
        if (e.textContent.indexOf('-')===0) {
            e.classList.add ('negative');
        }
    }
}

function setFrame (path) {
    var p;
    p = findInQueryString ('P');
    if (p) {
        window.history.replaceState ({},document.title,'./');
        path = p;
    }
    document.getElementById ('frame').setAttribute ('src',path);
}

function submitHandler (evt) {
    if (evt.target.classList.contains('search')) {
        evt.preventDefault ();
    }
}

function supporterSearch (elementId) {
    var form,query,xhttp;
    form = document.getElementById(elementId).form;
    query = './?search&' + form2Query(form);
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4 && this.status==200) {
            supporterSearchResults (xhttp.responseText);
        }
    };
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function supporterSearchResults (responseText) {
    var body,cell,cells,fn,foot,i,key,link,mandate,results,row,supporter;
    body = document.getElementById ('search-results');
    if (!body) {
        return;
    }
    mandate = document.getElementById ('bacs');
    supporter = document.getElementById ('supporter');
    if (mandate && mandate.contains(body)) {
        fn = window.mandateSelect;
    }
    else if (supporter && supporter.contains(body)) {
        fn = window.supporterSelect;
    }
    else {
        return;
    }
    foot = document.getElementById ('search-notice');
    if (!foot) {
        return;
    }
    body.innerHTML = '';
    foot.innerHTML = '';
    try {
        results = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
        }
        console.log ('Error: '+responseText);
        message ('Search request failed','err');
        return;
    }
    if ('error' in results) {
        message ('Search failed error='+results.error,'err');
        return;
    }
    if ('short' in results) {
        foot.innerHTML = '<td>Make search longer</td>';
        return;
    }
    if ('count' in results) {
        if (results.count==0) {
            foot.innerHTML = '<td>No results</td>';
        }
        else {
            foot.innerHTML = '<td>'+results.count+' results - make search longer</td>';
        }
        return;
    }
    for (i=0;results[i];i++) {
        cells = 0;
        row = document.createElement ('tr');
        for (key in results[i]) {
            cells++;
            cell = document.createElement ('td');
            if (key=='ClientRef') {
                link = document.createElement ('a');
                link.textContent = results[i][key];
                link.setAttribute ('href','#');
                link.setAttribute ('data-clientref',results[i][key]);
                link.addEventListener ('click',fn);
                cell.appendChild (link);
            }
            else {
                cell.textContent = results[i][key];
            }
            row.appendChild (cell);
        }
        body.appendChild (row);
    }
    foot.innerHTML = '<td colspan="'+cells+'">'+results.length+' results</td>';
}

function supporterSelect (evt) {
    evt.preventDefault ();
    var form,query,xhttp;
    query = './?search&t=s&r=' + evt.target.dataset.clientref;
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4 && this.status==200) {
            supporterSelectResult (xhttp.responseText);
        }
    };
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function supporterSelectResult (responseText) {
    var body,cancel,cell,fields,fname,form,heading,i,input,response,row,tips;
    try {
        response = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
        }
        console.log ('Error: '+responseText);
        message ('Select request failed','err');
        return;
    }
    if ('error' in response) {
        message ('Select failed error='+response.error,'err');
        return;
    }
    form = document.getElementById ('change-supporter');
    form.supporter_id.value = response.data[0].supporter_id;
    heading = form.querySelector ('thead th:nth-of-type(2)');
    heading.textContent = response.data[0].client_ref;
    fields = {
        title: "Title",
        name_first: "First name",
        name_last: "Last name",
        email: "Email address",
        mobile: "Mobile number",
        telephone: "Home phone",
        address_1: "Address 1",
        address_2: "Address 2",
        address_3: "Address 3",
        town: "Town / city",
        county: "County",
        postcode: "Postcode",
        dob: "Date of birth"
    }
    tips = {};
    if (!response.fields) {
        console.log ('Response has no fields - response = '+responseText);
        return;
    }
    for (i=0;response.fields[i];i++) {
        fields['p'+response.fields[i].p_number] = response.fields[i].legend;
        tips['p'+response.fields[i].p_number] = '? = Not Known';
    }
    body = form.querySelector ('tbody');
    body.innerHTML = '';
    for (fname in fields) {
        row = document.createElement ('tr');
        cell = document.createElement ('td');
        cell.textContent = fields[fname];
        row.appendChild (cell);
        cell = document.createElement ('td');
        input = document.createElement ('input');
        input.setAttribute ('type','text');
        input.setAttribute ('name',fname);
        if (fname in tips) {
            input.setAttribute ('title',tips[fname]);
        }
        input.value = response.data[0][fname];
        cell.appendChild (input);
        row.appendChild (cell);
        body.appendChild (row);
    }
    form.classList.add ('active');
}

function supporterUpdate (form) {
    var data,query,xhttp;
    query = './?update&t=s&r=' + form.supporter_id.value;
    data = new FormData (form);
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4 && this.status==200) {
            supporterUpdateResult (xhttp.responseText);
        }
    };
    xhttp.open ('POST',query,true);
    xhttp.send (data);
}

function supporterUpdateResult (responseText) {
    var form,table;
    form = document.getElementById ('change-supporter');
    table = form.querySelector ('table');
    try {
        results = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.trim().indexOf('<!doctype html>')===0) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
        }
        message ('Update request failed','err');
        return;
    }
    if ('error' in results) {
        message ('Update failed error='+results.error,'err');
        return;
    }
    message ('Details successfully updated');
    form.classList.add ('updated');
    form.classList.remove ('changed');
}

function topCheck ( ) {
    if (window.self!=window.top) {
// alert ('topCheck(): load in top');
        window.top.location.href = window.self.location.href;
    }
}

function unloading ( ) {
    if (!window.unloadSuppress) {
        setTimeout ('document.body.classList.add("unloading")',500);
        return;
    }
    window.unloadSuppress = false;
}

function updateChange (evt) {
    evt.currentTarget.classList.add ('changed');
    evt.currentTarget.classList.remove ('updated');
}

function updateHandle (formId) {
    document.getElementById(formId).addEventListener ('input',window.updateChange);
}


