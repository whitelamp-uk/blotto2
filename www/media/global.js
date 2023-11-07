

function clickHandler (evt) {
    var element;
    if (evt.target.id=='adminer') {
        window.top.menuActivate ('adminer');
        return;
    }
    if (evt.target.id=='small-print-view') {
        evt.preventDefault ();
        smallPrintOpen (evt.target);
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
        if (this.readyState==4) {
            if (this.status==200) {
                mandateSelectResult (xhttp.responseText);
            } else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
        }
    };
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function mandateSelectResult (responseText) {
    var body,cancel,cell,day,field,fields,fname,form,heading,i,input,msg,option,response,row,select;
    msg = document.querySelector ('.update-message');
    msg.classList.remove ('active');
    msg.innerHTML = '';
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
    fields = form.querySelectorAll ('button,input,select');
    for (field of fields) {
        field.disabled = false;
    }
    form.classList.remove ('changed');
    form.ClientRef.value = response.data[0].ClientRef;
    form.Provider.value = response.data[0].Provider;
    heading = form.querySelector ('thead th:nth-of-type(2)');
    heading.textContent = response.data[0].Provider+' - '+response.data[0].ClientRef;
    fields = {
        Name: "Account name",
        Sortcode: "Bank sort code",
        Account: "Bank account number",
        Freq: "Payment frequency",
        Amount: "Payment amount",
        StartDate: "Start date",
        CancelMandate: "Terminate Mandate",
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
            option.value = '1';
            option.textContent = 'Monthly';
            select.appendChild (option);
            option = document.createElement ('option');
            option.value = '12';
            option.textContent = 'Annually';
            select.appendChild (option);
            cell.appendChild (select);
            if (response.data[0]['Freq']=='Monthly') {
                select.selectedIndex = 0;
            }
            if (response.data[0]['Freq']=='Annually') {
                select.selectedIndex = 1;
            }
        }
        else if (fname=='StartDate') {
            day = new Date(Date.now()+1000*60*60*24*7); // 1 week
            input = document.createElement ('input');
            input.setAttribute ('type','date');
            input.setAttribute ('name',fname);
            input.min = day.toISOString().split("T")[0];
            cell.appendChild (input);
        }
        else if (fname=='CancelMandate') {
            input = document.createElement ('input');
            input.setAttribute ('type','checkbox');
            input.setAttribute ('name',fname);
            input.setAttribute ('style',"text-align:left");
            cell.appendChild (input);
            input.addEventListener('change', function() {
                form = document.getElementById ('change-mandate');
                myfields = form.querySelectorAll ('input[type=text],input[type=date],select'); // get all others
                for (myfield of myfields) {
                    myfield.disabled = this.checked;
                }
                console.log("myfields");
                console.log(myfields);
            });
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
    if (!form.classList.contains('changed')) {
        return;
    }
    if (form.CancelMandate.checked) {
        if (!confirm("You are terminating this mandate.\nAre you sure?\nClick OK if you are.")) {
            return;
        }
    }
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                mandateUpdateResult (xhttp.responseText);
            } else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
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
    try {
        results = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
            return;
        }
        console.log ('Error: '+responseText);
        updateView ('m',{ error : null, errorMessage : "Update request failed (unspecified error)" });
                console.log ('safdad ');
        return;
    }
    updateView ('m',results);
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

function smallPrintClose (evt) {
    var box;
    box = evt.currentTarget;
    box.classList.remove ('visible');
    box.removeEventListener ('click',smallPrintClose);
}

function smallPrintOpen (link) {
    var box;
    box = link.nextElementSibling;
    box.addEventListener ('click',smallPrintClose);
    box.classList.add ('visible');
}

function submitHandler (evt) {
    if (evt.target.classList.contains('search')) {
        evt.preventDefault ();
    }
}

// used in both mandate and supporter searches!
function supporterSearch (elementId) {
    var form,query,xhttp;
    form = document.getElementById(elementId).form;
    query = './?search&' + form2Query(form);
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                supporterSearchResults (xhttp.responseText);
            } else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
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
        if (responseText.indexOf('<!doctype html>')>=0) {
            // Looks like we have logged out or session has expired but log in case the server has broken
            console.log ('Response text is HTML instead of JSON: '+responseText);
            alert ('Sorry, it looks like your login has expired');
            return;
        }
        console.log ('Error (s): '+responseText);
        console.trace();
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
            console.log ("Key "+key);
            console.log ("val " +results[i][key]);
            if (key=='BCR') {
                if (results[i]['BCR'] != null) {
                    results[i]['Supporter'] += ' Last BCR: ' + results[i]['BCR'];
                }
            } else {
                cells++;
                cell = document.createElement ('td');
                if (key=='ClientRef') {
                    link = document.createElement ('a');
                    link.textContent = results[i][key];
                    link.setAttribute ('href','#');
                    link.setAttribute ('data-clientref',results[i][key]);
                    if (fn==window.mandateSelect && results[i].Freq=='Single') {
                        link.addEventListener ('click',function(evt){message('This mandate was for a single payment','err')});
                    }
                    else {
                        link.addEventListener ('click',fn);
                    }
                    cell.appendChild (link);
                }
                else {
                    cell.textContent = results[i][key];
                }
                row.appendChild (cell);
            }
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
        if (this.readyState==4) {
            if (this.status==200) {
                supporterSelectResult (xhttp.responseText);
            } else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
        }
    };    
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function supporterSelectResult (responseText) {
    var body,cancel,cell,field,fields,fname,form,heading,i,input,msg,response,row,tips;
    msg = document.querySelector ('.update-message');
    msg.classList.remove ('active');
    msg.innerHTML = '';
    msg.classList.remove ('error');
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
    fields = form.querySelectorAll ('button,input,select');
    for (field of fields) {
        field.disabled = false;
    }
    form.classList.remove ('changed');
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
    if (!form.classList.contains('changed')) {
        return;
    }
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                supporterUpdateResult (xhttp.responseText);
            } else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
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

function supporterUpdateResult (responseText) {
    try {
        results = JSON.parse (responseText);
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
            return;
        }
        console.log ('Error: '+responseText);
        updateView ('s',{ error : null, errorMessage : "Update request failed (unspecified error)" });
        return;
    }
    updateView ('s',results);
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
}

function updateHandle (formId) {
    document.getElementById(formId).addEventListener ('input',window.updateChange);
}

function updateView (type,results) {
    console.log("a");
    var err,field,fields,form,however,img,p,section,txt;
    section = document.querySelector ('section.update-message');
    if (type=='s') {
        form = document.getElementById ('change-supporter');
    }
    if (type=='m') {
        form = document.getElementById ('change-mandate');
    }
    if (results.ok || results.created) {
        form.classList.remove ('changed');
        fields = form.querySelectorAll ('button,input,select');
        for (field of fields) {
            field.disabled = true;
        }
        fields = form.querySelectorAll ('.form-close');
        for (field of fields) {
            field.disabled = false;
        }
    }
    txt = [];
    if (type=='s') {
        if (results.ok) {
            txt.push ('Supporter details have been updated successfully');
        }
    }
    if (type=='m') {
        if (results.created) {
            however = true;
            txt.push ('A replacement mandate has been created - mandate will be available within 5 working days');
            txt.push ('A BACS request has been posted to the administrator to cancel the old mandate');
        }
        else if (results.ok) {
            txt.push ('A BACS request has been posted to the administrator');
        }
        else if (results.error>=111) {
            however = true;
            txt.push ('A BACS request has been posted to the administrator');
        }
    }
    if (results.errorMessage) {
        section.classList.add ('error');
        err = '';
        if (results.error) {
            err = results.error + ' ';
        } 
        err += results.errorMessage;
        if (however) {
            txt.push ('However, an error occurred: '+err);
        }
        else {
            txt.push ('An error occurred: '+err);
        }
        if (results.error!=110) { // 110 is a missing field value
            txt.push ('Please copy this full message into an email to your administrator');
        }
    }
    console.log("b");

    section.innerHTML = "";
    img = document.createElement ('img');
    img.classList.add ('close');
    img.addEventListener ('click',function(evt){evt.currentTarget.parentElement.classList.remove('active')});
    section.appendChild (img);
    for (i=0;txt[i];i++) {
        p = document.createElement ('p');
        p.innerText = txt[i];
        section.appendChild (p);
    }
    console.log("c");
    section.classList.add ('active');
}

