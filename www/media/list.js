

function dateActivate (button=null) {
    var b,buttona,buttonm,buttons,buttonw,date,last,scrolled;
    if (!button) {
        last = sessionStorage.getItem ('data-date-last-clicked');
        if (date=sessionStorage.getItem('data-date-month')) {
            if (buttonm=document.querySelector('#list button[data-interval="month"][data-date="'+date+'"]')) {
                buttonm.classList.add ('active');
            }
            if (date==last) {
                scrolled = buttonm;
            }
        }
        if (date=sessionStorage.getItem('data-date-week')) {
            if (buttonw=document.querySelector('#list button[data-interval="week"][data-date="'+date+'"]')) {
                buttonw.classList.add ('active');
            }
            if (date==last) {
                scrolled = buttonw;
            }
        }
        if (scrolled) {
            scrolled.scrollIntoView (
                {
                    behavior : 'smooth',
                    block    : 'center'
                }
            );
        }
        return;
    }
    buttons = document.querySelectorAll ('#list button[data-interval="'+button.dataset.interval+'"][data-date]');
    for (b of buttons) {
        if (b==button) {
            continue;
        }
        b.classList.remove ('active');
    }
    button.classList.add ('active');
    sessionStorage.setItem('data-date-'+button.dataset.interval,button.dataset.date);
    sessionStorage.setItem('data-date-last-clicked',button.dataset.date);
}

function dateGo (button) {
    var element,elz,grp,url;
    element                     = button.parentElement.parentElement;
    element                     = element.querySelector ('[data-date]');
    if (element) {
        dateActivate (element);
    }
    url                         = button.dataset.url;
    if (button.dataset.go=='view') {
        window.top.menuActivate ('adminer');
    }
    else {
        grp                     = sessionStorage.getItem ('data-group-by');
        url                    += "&grp=" + grp;
        elz                     = sessionStorage.getItem ('excel-leading-zero');
        if (elz) {
            url                += "&elz=1";
        }
        // No unload wait icon - this is a download
        window.unloadSuppress   = true;
    }
    window.location.href        = url;
}

function elzSet (input=null) {
    var current, element;
    if (input) {
        if (input.checked) {
            sessionStorage.setItem ('excel-leading-zero','1');
            input.nextElementSibling.classList.add ('blink');
            return;
        }
        sessionStorage.setItem ('excel-leading-zero','');
        input.nextElementSibling.classList.remove ('blink');
        return;
    }
    current = sessionStorage.getItem ('excel-leading-zero');
    element = document.querySelector ('[name="excel_leading_zero"]');
    if (!element) {
        return;
    }
    if (current) {
        element.checked = true;
        element.nextElementSibling.classList.add ('blink');
        return;
    }
    element.checked = false;
    element.nextElementSibling.classList.remove ('blink');
}

function groupSet (input=null) {
    var current, element;
    if (input) {
        sessionStorage.setItem ('data-group-by',input.value);
        return;
    }
    current = sessionStorage.getItem ('data-group-by');
    if (current=="1") {
        element = document.querySelector ('[name="group_by_ticket_number"][value="1"]');
    }
    else {
        element = document.querySelector ('[name="group_by_ticket_number"][value="0"]');
    }
    if (element) {
        element.click ();
    }
}

