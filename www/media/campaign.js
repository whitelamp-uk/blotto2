(
    function ( ) {

        var fields;

        function campaignFormBuild (form,fields) {
            var el,element,elements,els,field,form,i,inputs,keys;
            keys = Object.keys (fields);
            for (i=0;keys[i];i++) {
                field = fields[keys[i]];
                if (field.type=='options') {
                    elements = document.body.querySelectorAll (
                        '.campaign-either-or [id="campaign-all-'+keys[i]+'"]'
                    );
                    for (element of elements) {
                        if (element.checked) {
                            element.click ();
                        }
                        element.nextElementSibling.textContent = field.label + ' =';
                        element.addEventListener ('input',campaignOptionsReset);
                        els = element.parentElement.parentElement.querySelectorAll (
                            '.campaign-either-or [id^="campaign-'+keys[i]+'-"]'
                        );
                        for (el of els) {
                            el.dataset.field = keys[i];
                            el.addEventListener ('input',campaignOptionAllReset);
                            if (!el.checked) {
                                el.click ();
                            }
                        }
                    }
                }
                element = document.createElement ('div');
            }
            inputs = form.querySelector ('section.inputs');
            for (i=0;keys[i];i++) {
                field = fields[keys[i]];
                if (field.type!='options') {
                    element = document.createElement ('div');
                    el = document.createElement ('label');
                    el.setAttribute ('for','campaign-'+keys[i]);
                    el.textContent = field.label;
                    element.appendChild (el);
                    el = document.createElement ('input');
                    el.setAttribute ('type','text');
                    el.id = 'campaign-'+keys[i];
                    element.appendChild (el);
                    inputs.appendChild (element);
                }
            }
        }
var pongtimes=0;
        function campaignOptionAllReset (evt) {
pongtimes += 1;
console.log ('pong '+pongtimes);
            var all,el,element,els,none;
            element = event.currentTarget.parentElement.parentElement.querySelector (
                '[id^="campaign-all-"]'
            );
            els = event.currentTarget.parentElement.parentElement.querySelectorAll (
                '[data-field="'+evt.currentTarget.dataset.field+'"]'
            );
            all = true;
            none = true;
            for (el of els) {
                if (el.checked) {
                    none = false;
                }
                else {
                    all = false;
                }
            }
console.log ('ALL = '+all);
            if ((none || all) && element.checked) {
                element.click ();
            }
            else if (!element.checked) {
                element.click ();
            }
        }
var pingtimes=0;
        function campaignOptionsReset (evt) {
pingtimes += 1;
console.log ('ping '+pingtimes);
            var element,elements;
            if (evt.currentTarget.checked) {
                evt.currentTarget.click ();
            }
            else {
                elements = evt.currentTarget.parentElement.parentElement.querySelectorAll ('input[type="checkbox"]');
                for (element of elements) {
                    if (element.id.indexOf('campaign-all-')!=0) {
                        if (!element.checked) {
                            element.click ();
                        }
                    }
                }
            }
        }

        fields = {
            "title" : {
                "label" : "Title",
                "type" : "options",
            },
            "town" : {
                "label" : "Town/City",
                "type" : "text",
            },
            "postcode" : {
                "label" : "Postcode",
                "type" : "text",
            },
            "dob" : {
                "label" : "Date of birth",
                "type" : "date",
            },
            "ccc" : {
                "label" : "CC code",
                "type" : "options",
            },
            "canvas_agent_ref" : {
                "label" : "CC agent ref",
                "type" : "code",
            },
            "mandate_provider" : {
                "label" : "Mandate provider",
                "type" : "code",
            },
            "letter_batch_ref" : {
                "table" : "ANLs",
                "label" : "ANLs batch",
                "type" : "code"
            },
            "created" : {
                "label" : "Created",
                "type" : "date",
            },
            "active" : {
                "label" : "Active",
                "type" : "options",
            },
            "cancelled" : {
                "label" : "Cancelled",
                "type" : "boolean-date",
            },
            "mandate_missing" : {
                "label" : "Mandate",
                "type" : "options",
            },
            "status" : {
                "label" : "Status",
                "type" : "options",
            },
            "fail_reason" : {
                "label" : "Fail reason",
                "type" : "code",
            },
            "chances" : {
                "label" : "Chances",
                "type" : "options",
            },
            "supporter_first_payment" : {
                "label" : "First payment on",
                "type" : "date",
            },
            "supporter_total_payments" : {
                "label" : "Number of payments",
                "type" : "integer",
            },
            "supporter_total_amount" : {
                "label" : "Total paid",
                "type" : "currency",
            },
            "supporter_total_plays" : {
                "label" : "Total plays",
                "type" : "integer",
            },
            "draw_closed" : {
                "label" : "Won in draw closing on",
                "type" : "date"
            },
            "winnings" : {
                "label" : "Won amount",
                "type" : "options"
            },
            "prize" : {
                "label" : "Won prize",
                "type" : "options"
            },
            "letter_batch_ref" : {
                "label" : "Winnings letter batch",
                "type" : "code"
            }
        }

        console.table (fields);

        campaignFormBuild (
            document.body.querySelector ('#campaign-form-1'),
            fields
        );

    }

) ();

