

function anlResend (form) {
    var data,query,xhttp;
    if (!confirm('Resend ANL for supporter #'+form.supporter_id.value+'?')) {
        return;
    }
    data = new FormData (form);
    query = './?anlreset&r=' + form.supporter_id.value;  //backend refers to "reset" because that's what it is.
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                try {
                    results = JSON.parse (xhttp.responseText);
                    updateView ('a',results);
                }
                catch (e) {
                    if (responseText.indexOf('<!doctype html>')!==false) {
                        // Looks like we have logged out or session has expired
                        window.top.location.href = './';
                        return;
                    }
                    console.log ('Error: '+xhttp.responseText);
                    updateView ('a',{ error : null, errorMessage : "ANL resend request failed (unspecified error)" });
                    return;
                }
            }
            else {
                updateView ('a',{ error : null, errorMessage : "ANL resend request failed: server status " + this.status });
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
    if (evt.target.id=='post-supporter-anl-resend') {
        evt.preventDefault ();
        anlResend (evt.target.form);
    }
    if (evt.target.id=='post-supporter-mandate-block') {
        evt.preventDefault ();
        supporterUpdateBlock (evt.target.form);
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

function inputProfitParameter (evt) {
    var elm;
    if (evt.currentTarget.name=='supporters') {
        elm = evt.currentTarget.form.supporters_pw;
        elm.value = (12*evt.currentTarget.value*7/365.25).toFixed (elm.dataset.dp);
    }
    else if (evt.currentTarget.name=='supporters_pw') {
        elm = evt.currentTarget.form.supporters;
        elm.value = ((evt.currentTarget.value/7)*365.25/12).toFixed (elm.dataset.dp);
    }
    evt.currentTarget.value = (1*evt.currentTarget.value).toFixed (evt.currentTarget.dataset.dp);
}

function inputProfitSet (evt) {
    var form;
    form = document.querySelector ('form#profit');
    form.days_signup_import.value   = (1*profits.projection.m12.days_signup_import).toFixed (form.days_signup_import.dataset.dp);
    form.days_import_entry.value    = (1*profits.projection.m12.days_import_entry).toFixed (form.days_import_entry.dataset.dp);
    form.abortive_pct.value         = (1*profits.projection.m12.chances_abortive_pct).toFixed (form.abortive_pct.dataset.dp);
    form.attritional_pct.value      = (1*profits.projection.m12.chances_attritional_pct).toFixed (form.attritional_pct.dataset.dp);
    form.cps.value                  = (1*profits.projection.m12.chances_per_supporter).toFixed (form.cps.dataset.dp);
    if (profits.history.length>0) {
        form.tickets.value          = (1*profits.history[profits.history.length-1].tickets).toFixed (form.tickets.dataset.dp);
        form.supporters.value       = (1*profits.projection.m12.supporters).toFixed (form.supporters.dataset.dp);
        form.supporters.dispatchEvent (new Event("input"));
    }
    form.days_signup_import.dataset.reset   = form.days_signup_import.value;
    form.days_import_entry.dataset.reset    = form.days_import_entry.value;
    form.abortive_pct.dataset.reset         = form.abortive_pct.value;
    form.attritional_pct.dataset.reset      = form.attritional_pct.value;
    form.cps.dataset.reset                  = form.cps.value;
    form.tickets.dataset.reset              = form.tickets.value;
    form.supporters.dataset.reset           = form.supporters.value;
}

function linkProfitBlob (data,contentType='text/json') {
    var ct,d='',i,h,hs;
    hs = document.querySelectorAll ('#profit ol[data-profit-headings] li');
    if (contentType=='text/csv') {
        for (h of hs) {
            if ('negatize' in h.dataset) {
                d += "− " + h.innerText + ",";
            }
            else if ('positize' in h.dataset) {
                d += "+ " + h.innerText + ",";
            }
            else {
                d += h.innerText + ",";
            }
        }
        d = d.substring(0,d.length-1) + "\n";
        for (i in data) {
            for (h of hs) {
                d += data[i][h.innerText] + ",";
            }
            d = d.substring(0,d.length-1) + "\n";
        }
        d = d.trim ();
    }
    else if (contentType=='text/html') {
        d += "<html>\n";
        d += "  <body>\n";
        d += "    <table style=\"border-style:none\">\n";
        d += "      <thead>\n";
        for (h of hs) {
            if ('negatize' in h.dataset) {
                d += "          <th style=\"text-align:left;white-space:nowrap\">− " + h.innerText + "</th>\n";
            }
            else if ('positize' in h.dataset) {
                d += "          <th style=\"text-align:left;white-space:nowrap\">+ " + h.innerText + "</th>\n";
            }
            else {
                d += "          <th style=\"text-align:left\">" + h.innerText + "</th>\n";
            }
        }
        d += "      </thead>\n";
        d += "      <tbody>\n";
        for (i in data) {
            d += "        <tr>\n";
            for (h of hs) {
                d += "          <td style=\"text-align:right\">" + data[i][h.innerText] + "</td>\n";
            }
            d = d.substring(0,d.length-1) + "\n";
            d += "        </tr>\n";
        }
        d += "      </tbody>\n";
        d += "    </table>\n";
        d += "  </body>\n";
        d += "</html>\n";
    }
    else {
        contentType = "text/json";
        d = JSON.stringify (data);
    }
    return new Blob ([d],{type:contentType});
}

function linkProfitHeadingsCcrCancellations (evt) {
    var c,h,hs,i,p,ps=[];
    hs = document.querySelector ('[data-profit-headings]');
    for (p in profits.history[0]) {
        ps.push (p);
    }
    for (i=0;i<ps.length;i++) {
        if (ps[i]=='ccr_cancels') {
            i++;
            break;
        }
    }
    for (i;i<ps.length;i++) {
        h = document.createElement ('li');
        h.setAttribute ('title',ps[i]);
        h.innerText = ps[i];
        hs.appendChild (h);
    }
}

function linkProfitHistory (evt) {
    var ct='text/json';
    // Link a blob of the right content type
    if (profits.history.length>0) {
        if (evt.currentTarget.classList.contains('csv')) {
            ct = 'text/csv';
        }
        else if (evt.currentTarget.classList.contains('html')) {
            ct = 'text/html';
        }
        evt.currentTarget.href = window.URL.createObjectURL (linkProfitBlob(profits.history,ct));
    }
    else {
        evt.preventDefault ();
    }
}

function linkProfitProjection (evt) {
    var abtv,anle,anlp,anls,attr,bal,chs,ct='text/json',data=[],entries;
    var form,i,j,ms,nxt,nxt1,nxt2,p,pr,ps,rev,row,tks,tmp;
    // Use profits.projection to derive projection data
    form = evt.currentTarget.closest ('form');
    anlp = profits.projection.m12.anl_post_pct / 100;
    anls = profits.projection.m12.anl_sms_pct / 100
    anle = 1 - (anlp + anls);
    // Starting conditions
    tks = 1 * form.tickets.value; // cumulative
    bal = 1 * profits.history[profits.history.length-1].balance; // cumulative
    chs = 1 * profits.history[profits.history.length-1].chances; // chances loaded previous loop
    data.push (...profits.history);
    /*
    var test=[],test2=[];
    */
    for (i in profits.projection.months) {
        if (i < 0) {
            continue;
        }
        /*
        var current = tks;
        */
        attr = tks * form.attritional_pct.value / 100;
        tks -= attr;
        tks += profits.projection.months[i].new_tickets;
        entries = profits.projection.months[i].draws * (tks + profits.projection.months[i].first_entries);
        /*
        test.push (
            {
                month_nr : profits.projection.months[i].month_nr,
                current_tickets : current,
                attrition : attr,
                new : profits.projection.months[i].new_tickets,
                draws : profits.projection.months[i].draws,
                entries : entries
            }
        );
        */
        abtv = chs * form.abortive_pct.value / 100;
        row = {
            type: profits.projection.months[i].type,
            month_nr: profits.projection.months[i].month_nr,
            month: profits.projection.months[i].month,
            supporters: 1 * form.supporters.value,
            days_signup_import: 1 * form.days_signup_import.value,
            chances: form.supporters.value * form.cps.value,
            abortive: abtv,
            attritional: attr,
            days_import_entry: 1 * form.days_import_entry.value,
            draws: 1 * profits.projection.months[i].draws,
            entries: entries,
            revenue: entries * form.dataset.price/100,
            payout: 1*profits.projection.months[i].payout + entries*profits.projection.months[i].payout_per_entry,
            loading: form.supporters.value * profits.projection.months[i].rates.loading / 100,
            anl_post: anlp * form.supporters.value * profits.projection.months[i].rates.anl_post / 100,
            anl_sms: anls * form.supporters.value * profits.projection.months[i].rates.anl_sms / 100,
            anl_email: anle * form.supporters.value * profits.projection.months[i].rates.anl_email / 100,
            winner_post: (1*profits.projection.months[i].winners + entries*profits.projection.months[i].winners_per_entry) * profits.projection.months[i].rates.winner_post/100,
            insure: form.supporters.value * form.cps.value * profits.projection.months[i].rates.insure / 100,
            ticket: form.supporters.value * form.cps.value * profits.projection.months[i].rates.ticket / 100,
            email: profits.projection.months[i].draws * profits.projection.months[i].rates.email / 100,
            admin: profits.projection.months[i].draws * profits.projection.months[i].rates.admin / 100,
            profit: 0,
            balance: 0,
            tickets: tks,
            ccr_cancels: ''
        }
        // Add CCR data
        for (j=0;j<profits.projection.ccr_cancels_per_signup.length;j++) {
            row['ccr'+String(j)] = profits.projection.ccr_cancels_per_signup[j] * row.chances;
        }
        // Profit
        row.profit = row.revenue;
        row.profit -= row.loading + row.anl_post + row.anl_sms + row.anl_email;
        row.profit -= row.winner_post + row.insure + row.ticket + row.email + row.admin;
        row.profit -= row.payout;
        // For the next loop
        chs = row.chances; // always after the zeroth loop
        bal += row.profit;
        // Complete this row
        row.balance = bal;
        // Add fractional entries/new tickets for non-abortive chances
        ms = form.days_import_entry.value * 12 / 365.25;
        nxt = 1*i + ms;
        nxt1 = parseInt (Math.floor(nxt));
        nxt2 = parseInt(Math.ceil(nxt));
        if (profits.projection.months[nxt1]) {
            tmp = parseFloat (profits.projection.months[nxt1].first_entries);
            profits.projection.months[nxt1].first_entries = tmp + (nxt2-nxt)*row.chances*(1-form.abortive_pct.value/100);
        }
        if (profits.projection.months[nxt2]) {
            tmp = parseFloat (profits.projection.months[nxt2].new_tickets);
            profits.projection.months[nxt2].new_tickets = tmp + row.chances*(1-form.abortive_pct.value/100);
        }
        /*
        test2.push (
            {
                month_nr : row.month_nr,
                m_now : i,
                m_nxt1 : nxt1,
                m_nxt2 : nxt2,
                e_first : (nxt2-nxt) * row.chances * (1-form.abortive_pct.value/100),
                e_ongoing : row.chances * (1-form.abortive_pct.value/100)
            }
        );
        */
        for (p in row) {
            if (['revenue','payout','loading','anl_post','anl_sms','anl_email','winner_post','insure','ticket','email','admin','profit','balance'].includes(p)) {
                row[p] = (1*row[p]).toFixed (2);
            }
            else if (['month_nr','draws','entries','supporters','chances','tickets'].includes(p)) {
                row[p] = (1*row[p]).toFixed (0);
            }
            else if (p=='ccr_cancels') {
                // Separator column
            }
            else if (!isNaN(row[p])) {
                row[p] = (1*row[p]).toFixed (1);
            }
        }
        data.push (row);
    }
    /*
    console.error (test);
    console.error (test2);
    */
    // Link a blob of the right content type
    if (evt.currentTarget.classList.contains('csv')) {
        ct = 'text/csv';
    }
    else if (evt.currentTarget.classList.contains('html')) {
        ct = 'text/html';
    }
    evt.currentTarget.href = window.URL.createObjectURL (linkProfitBlob(data,ct));
}

function mandateSelect (evt) {
    evt.preventDefault ();
    var form,query,xhttp;
    document.getElementById ('change-supporter').classList.remove ('active');
    query = './?search&t=m&r=' + evt.target.dataset.clientref;
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                mandateSelectResult (xhttp.responseText);
            }
            else {
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
    if ('mandate_blocked' in form) {
        form.block_mandate.disabled = false;
    }
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
            }
            else {
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
        return;
    }
console.log (results);
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
        if (e.textContent.indexOf('-')===0 || e.textContent.indexOf('−')===0) {
            e.classList.add ('negative');
        }
    }
}

function passwordResetAutoCancel ( ) {
    var p;
    p = document.querySelector ('form.login p:nth-of-type(1)');
    p.classList.add ('error');
    p.innerText = 'You have run out of time - please try again';
    clearInterval (interval);
    setTimeout (
        function ( ) {
            window.top.location.href = './';
        },
        10000
    );
}

function passwordResetTimerDecrement (box,bar,start=false) {
    var s,t,tm,ts,w;
    w = parseFloat (window.getComputedStyle(box).getPropertyValue('width'));
    t = parseFloat (box.dataset.seconds);
    s = parseFloat (bar.dataset.seconds);
    if (s>0) {
        if (!start) {
            s--;
        }
        bar.style.width = Math.round(w*s/t).toFixed(1) + 'px';
        bar.dataset.seconds = String (s);
        tm = String (Math.floor(s/60));
        ts = String (s%60);
        if (ts.length<2) {
            ts = '0' + ts;
        }
        bar.firstChild.innerText = tm + ':' + ts;
    }
    return s;
}

function passwordSuggestion (noRepeats=false) {
    var arr=[],chars,i,nr,nrs,pwd='';
    /*
    At the time of writing there seems no stable method for JS to lay hands
    on browser password suggestions from JS.
    Until that glorious day - TODO - here is a fairly strong pseudo-random
    password suggester.
    TODO is this random function nice/strong enough? Currently deployed with
    noRepeats = false
    */
    // A list of 70 unambiguous characters for the hard of seeing and/or thinking
    chars = '34679ACEFGHJKLMNPQRTWXYabcdefghjkmnpqrstwxy,./|<>?;#:@~[]{}-=!$%^&()_+';
    // Get more random numbers than we need (for noRepears = true)
    nrs = crypto.getRandomValues (new Uint8Array(30));
    for (i in nrs) {
        if (arr.length>=15) {
            // We have enough char index numbers
            break;
        }
        // Normalise and round the random number to make a char index number
        nr = Math.round (chars.length*nrs[i]/255);
        if (!noRepeats || !arr.includes(nr)) {
            // Add the chars index number
            arr.push (nr);
        }
    }
    for (i in arr) {
        pwd += chars.charAt (arr[i]);
    }
    return pwd;
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
            }
            else {
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
    /* obsolete I reckon
    mandate = document.getElementById ('bacs');
    supporter = document.getElementById ('supporter');
    if (mandate && mandate.contains(body)) {
        //fn = window.mandateSelect; // todo sort out
    }
    else if (supporter && supporter.contains(body)) {
        //fn = window.supporterSelect;
    }
    else {
        return;
    }
    */
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
        console.trace ();
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
        // meh. Friday night mess 
        status = 'inactive';
        if (results[i]['Status'] !== null) {
            lcstatus = results[i]['Status'].toLowerCase(); 
            if (lcstatus=='active' || lcstatus=='live') { // convert to paysuite style.
                status = 'active';
            }
        }
        delete results[i]['Status'];
        if (results[i]['BCR'] != null) {
            results[i]['Mandate'] += ' Last BCR: ' + results[i]['BCR'];
        }
        delete results[i]['BCR'];
        mclientref = results[i]['MandateClientRef'];
        cclientref = results[i]['CurrentClientRef'];
        if (results[i]['MandateClientRef'] == results[i]['CurrentClientRef']) {
            results[i]['MandateClientRef'] = '*';
        }
        else {
            results[i]['MandateClientRef'] = '';
        }
        delete results[i]['CurrentClientRef'];
        mfreq = results[i]['Freq'];
        delete results[i]['Freq'];

        cells = 0;
        row = document.createElement ('tr');
        if (status == 'inactive') {
            row.className = 'search-result-inactive';
        }
        for (key in results[i]) {
            cells++;
            cell = document.createElement ('td');
            if (key=='Supporter') {
                link = document.createElement ('a');
                link.textContent = results[i][key];
                link.setAttribute ('href','#');
                link.setAttribute ('data-clientref',cclientref);
                link.setAttribute ('data-status',status);
                link.addEventListener ('click',window.supporterSelect );
                cell.appendChild (link);
            }
            else if (key=='Mandate') {
                link = document.createElement ('a');
                link.textContent = results[i][key];
                if (status == 'active') {
                    link.setAttribute ('href','#');
                    link.setAttribute ('data-clientref',mclientref);
                    if (mfreq=='Single') {
                        link.addEventListener ('click',function(evt){message('This mandate was for a single payment','err')});
                    }
                    else {
                        link.addEventListener ('click',window.mandateSelect);
                    }
                }
                cell.appendChild (link);
            }
            else {
                cell.textContent = results[i][key];
            }
            row.appendChild (cell);
        }
        body.appendChild (row);
    }
    plural = results.length==1 ? '' : 's';
    foot.innerHTML = '<td colspan="'+cells+'">'+results.length+' result'+plural+'</td>';
}

function supporterSelect (evt) {
    evt.preventDefault ();
    var form,query,xhttp;
    document.getElementById ('change-mandate').classList.remove ('active'); // or wait until http request finished?
    query = './?search&t=s&r=' + evt.target.dataset.clientref;
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                supporterSelectResult (xhttp.responseText,evt.target.dataset.status);
            }
            else {
                updateView ('m',{ error : null, errorMessage : "Update request failed: server status " + this.status });
            }
        }
    };    
    xhttp.open ('GET',query,true);
    xhttp.send ();
}

function supporterSelectResult (responseText,status) {
    var body,cancel,cell,field,fields,fname,form,heading,i,input,label,msg,response,row,tips;
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

// TODO - re-enable / hide / in response to status and letter_batch_ref
    form.resend_anl.disabled = false;
    console.log("sSR");    console.log (response);    console.log (status);
    if (status=='active' && response.data[0].letter_batch_ref != null) {
console.log("show");
        form.resend_anl.disabled = false;
        form.resend_anl.hidden = false;
    } else {
console.log("hide");
        form.resend_anl.disabled = true;
        form.resend_anl.hidden = true;
    }
    if (status=='active') { 
        form.block_mandate.disabled = false;
        form.block_mandate.hidden = false;
    } else {
        form.block_mandate.disabled = true;
        form.block_mandate.hidden = true;
    }

    form.classList.remove ('changed');
    form.block_mandate.dataset.state = response.data[0].mandate_blocked;
    form.supporter_id.value = response.data[0].supporter_id;
    form.dataset.clientref = response.data[0].ClientRef;
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
    fields = form.querySelectorAll ('button,input,select');
    label = form.querySelector ('label:first-of-type');
    if ('mandate_blocked' in response.data[0] && 1*response.data[0].mandate_blocked) {
        closebutton = form.querySelector ('.form-close');
        for (field of fields) {
            if (field != closebutton) {
                field.disabled = true;
            }
        }
        form.classList.add ('blocked');
        form.update.disabled = true;
        if ('block_mandate' in form) {
            form.block_mandate.disabled = false;
            form.block_mandate.dataset.state = '1';
            form.block_mandate.textContent = 'Unblock mandate';
            label.textContent = 'Blocked';
        }
    }
    else {
        for (field of fields) {
            field.disabled = false;
        }
        form.classList.remove ('blocked');
        form.update.disabled = false;
        if ('block_mandate' in form) {
            form.block_mandate.disabled = false;
            form.block_mandate.dataset.state = '0';
            form.block_mandate.textContent = 'Block mandate';
            label.textContent = '';
        }
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
            }
            else {
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

function supporterUpdateBlock (form) {
    var query,xhttp;
    if (!confirm('Are you sure?')) {
        return;
    }
    query  = './?update&t=s&r=' + form.supporter_id.value;
    query += '&b=' + (1 - 1*form.block_mandate.dataset.state);
    query += '&c=' + form.dataset.clientref;
    xhttp = new XMLHttpRequest ();
    xhttp.onreadystatechange = function ( ) {
        if (this.readyState==4) {
            if (this.status==200) {
                supporterUpdateBlockResult (xhttp.responseText);
            }
            else {
                updateView ('m',{ error : null, errorMessage : "Supporter block request failed: server status " + this.status });
            }
        }
    };
    try {
        xhttp.open ('GET',query,true);
        xhttp.send ();
    }
    catch (e) {
        console.log (e.message);
    }
}

function supporterUpdateBlockResult (responseText) {
    var blocked;
    try {
        blocked = JSON.parse (responseText);
        blocked = blocked.blocked;
    }
    catch (e) {
        if (responseText.indexOf('<!doctype html>')!==false) {
            // Looks like we have logged out or session has expired
            window.top.location.href = './';
            return;
        }
        console.error ('Error: '+responseText);
        updateView ('s',{ error : null, errorMessage : "Supporter block request failed (unspecified error)" });
        return;
    }
    updateViewBlock ('s',blocked);
}

function supporterUpdateResult (responseText) {
    var results;
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
    var form = evt.currentTarget;
    form.classList.add ('changed');
    if ('block_mandate' in form) {
        form.block_mandate.disabled = true;
    }
    if ('resend_anl' in form) {
        form.resend_anl.disabled = true;
    }
}

function updateHandle (formId) {
    document.getElementById(formId).addEventListener ('input',window.updateChange);
}

function updateView (type,results) {
    var err,field,fields,form,however,img,p,section,txt;
    
    if (type=='s' || type=='a') {
        form = document.getElementById ('change-supporter');
        section = document.getElementById ('supporter-message');
    }
    else if (type=='m') {
        form = document.getElementById ('change-mandate');
        section = document.getElementById ('mandate-message');
    }
    if (results.ok || results.created) {
        if (type=='a') {
            field = form.querySelector ('#post-supporter-anl-resend');
            if (field) {
                field.disabled = true;
            }
        }
        else {
            form.classList.remove ('changed');
            fields = form.querySelectorAll ('button,input,select');
            for (field of fields) {
                field.disabled = true;
            }
            if ('block_mandate' in form) {
                form.block_mandate.disabled = false;
            }
            field = form.querySelector ('.form-close');
            if (field) {
                field.disabled = false;
            }
        }
    }
    txt = [];
    if (type=='a') {
        if (results.ok) {
            txt.push ('An ANL has been rescheduled and will be processed within 24 hours');
        }
    }
    else if (type=='s') {
        if (results.ok) {
            txt.push ('Supporter details have been updated successfully');
        }
    }
    else if (type=='m') {
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
    section.classList.add ('active');
}

function updateViewBlock (type,blocked) {
    var err,field,fields,form,however,img,label,p,section,txt;
    txt = [];
    section = document.querySelector ('section.update-message');
    if (type=='s') {
        form = document.getElementById ('change-supporter');
    }
    if (type=='m') {
        form = document.getElementById ('change-mandate');
    }
    label = form.querySelector ('label:first-of-type');
    for (i=0;form.elements[i];i++) {
        if (form.elements[i].tagName.toLowerCase()=='input') {
            if (blocked) {
                form.elements[i].disabled = true;
            }
            else {
                form.elements[i].disabled = false;
            }
        }
    }
    if (blocked) {
        form.classList.add ('blocked');
        form.update.disabled = true;
        form.block_mandate.dataset.state = '1';
        form.block_mandate.textContent = 'Unblock mandate';
        label.textContent = 'Blocked';
        txt.push ('Supporter is now blocked');
    }
    else {
        form.classList.remove ('blocked');
        form.update.disabled = false;
        form.block_mandate.dataset.state = '0';
        form.block_mandate.textContent = 'Block mandate';
        label.textContent = '';
        txt.push ('Supporter is now unblocked');
    }
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
    section.classList.add ('active');
}

