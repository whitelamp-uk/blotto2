

function closeHandle (evt) {
    evt.target.parentElement.parentElement.removeChild (evt.target.parentElement);
}

function inputCorrect (evt) {
    if (!evt.target.name) {
        return;
    }
    evt.target.value            = evt.target.value.trim ();
    evt.target.value            = evt.target.value.replace ('  ',' ');
    if (['sort_code','account_number'].includes(evt.target.name)) {
        evt.target.value        = evt.target.value.replace (/\D/g,'');
    }
    if (['sort_code'].includes(evt.target.name)) {
        var i = 0;
        var sc = '';
        if (evt.target.value.length>=2) {
            i                   = 2;
            sc                 += evt.target.value.substr (0,2);
            sc                 += '-';
        }
        if (evt.target.value.length>=4) {
            i                   = 4;
            sc                 += evt.target.value.substr (2,2);
            sc                 += '-';
        }
        sc                     += evt.target.value.substr (i);
        evt.target.value        = sc;
    }
    if (['dob'].includes(evt.target.name) && evt.target.getAttribute('type')=='text') {
        if (evt.target.value.indexOf('-')==-1 && evt.target.value.length==8) {
            var dob             = evt.target.value;
            evt.target.value    = dob.substr (0,4);
            evt.target.value   += '-' + dob.substr (4,2);
            evt.target.value   += '-' + dob.substr (6,2);
        }
        if (!evt.target.checkValidity()) {
            evt.target.value    = '';
        }
    }
    if (['email'].includes(evt.target.name)) {
        evt.target.value        = evt.target.value.replace (' ','');
    }
}

function setDobInput ( ) {
    var input, value;
    input = document.createElement ('input');
    value = 'a';
    input.setAttribute ('type', 'date');
    input.setAttribute ('value', value);
    if (input.value!==value) {
        // Date input is supported
        return;
    }
    input = document.getElementById ('dob');
    input.setAttribute ('type','text');
    input.setAttribute ('placeholder','YYYY-MM-DD');
    input.setAttribute ('pattern','[0-9]{4}-[0-9]{2}-[0-9]{2}');
}

function submitInhibit (evt) {
    evt.preventDefault ();
    evt.target.disabled = true;
    evt.target.textContent = 'Please wait...';
    evt.target.form.submit ();
}

async function postData (url='',data={}) {
//console.log (url);
//console.log (data);
    // Default options are marked with *
    var response;
    response = await fetch (
        url,
        {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'same-origin', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json'
                // 'Content-Type': 'application/x-www-form-urlencoded',
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body: JSON.stringify (data) // body data type must match "Content-Type" header
        }
    );
//var text = await response.text (); console.log (text);
    return response.json (); // parses JSON response into native JavaScript objects
}

function userMessage (msg) {
    alert (msg);
}

function verifyHandle (evt) {
    var field,nonce,post,type;
    type = evt.target.dataset.verifytype;
    field = document.querySelector ('form.signup [name="'+type+'"]');
    if (!field) {
        console.error ('<input name="'+type+'" /> not found');
        return;
    }
    nonce = document.querySelector ('form.signup [name="nonce_'+type+'"]');
    if (!nonce) {
        console.error ('<input name="nonce_'+type+'" /> not found');
        return;
    }
    post = {
        nonce: nonce.value
    }
    post[field.name] = field.value;
    postData  ('./tickets.php?verify',post)
      . then (
            response => {
//console.log (response);
                if (response.nonce) {
                    nonce.value = response.nonce;
                }
                if (response.e) {
                    if (response.e=='nonce') {
                        if (confirm('This page has expired. Reload it now?')) {
                            window.location.href = './tickets.php';
                            return;
                        }
                    }
                    // This should not happen really
                    userMessage ('Sorry that failed - please try again');
                }
                else if (type=='email') {
                    userMessage ('An email containing a verification code has been sent to '+field.value);
                }
                else if (type=='mobile') {
                    userMessage ('An SMS containing a verification code has been sent to '+field.value);
                }
            }
        );
}



(function () {
    var button,close,closes,input,inputs,verify,verifies;
    button = document.querySelector ('form.signup button[type="submit"]');
    if (button) {
        button.addEventListener ('click',submitInhibit);
    }
    closes = document.querySelectorAll ('[data-close]');
    if (closes.length > 0) {
        for (close of closes) {
            close.addEventListener ('click',closeHandle);
        }
    }
    inputs = document.querySelectorAll ('form.signup input, form.signup select');
    if (inputs.length > 0) {
        for (input of inputs) {
            input.addEventListener ('blur',inputCorrect);
        }
    }
    verifies = document.querySelectorAll ('form.signup [data-verifytype]');
    if (verifies.length > 0) {
        for (verify of verifies) {
            verify.addEventListener ('click',verifyHandle);
        }
    }
    if (window.self==window.top) {
        document.body.classList.add ('unframed');
    }
    setDobInput ();
})();

