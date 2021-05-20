
    // inheriting from data8.postcodeLookupButton

    if (typeof(blotto)=='undefined') {
        blotto = function () { };
    }

    blotto.styledPostcodeLookup = function (fields, options) {
        data8.postcodeLookupButton.apply (this,arguments);
    };

    blotto.styledPostcodeLookup.prototype = new data8.postcodeLookupButton ();

    // Override the showAddressList method to include a 'select address' option
    data8.postcodeLookupButton.prototype.showAddressList = function (addresses) {

        var addr,i,option,pcl,postcodeElement,selectAddressOption;

        // Clear any existing addresses.
        while (this.list.options.length>0) {
            this.list.options[this.list.options.length-1] = null;
        }

        // Create a SelectAddress option.
        selectAddressOption         = document.createElement ('option');
        selectAddressOption.text    = "Select Address";
        selectAddressOption.address = null;
        this.list.add (selectAddressOption);

        // BELOW THIS IS ALL THE DEFAULT CODE THAT SHOULD BE LEFT AS IT IS

        // Add the addresses to the list.
        for (addr=0;addr<addresses.length;addr++) {
            option                  = document.createElement('option');
            option.text             = this.getAddressText(addresses[addr]);
            option.address          = addresses[addr];

            try {
                this.list.add (option,this.list.options[null]);
            }
            catch (e) {
                this.list.add (option,null);
            }
        }

        this.list.multiple          = false;
        this.list.selectedIndex     = 0;

        // Save the function to apply the selected address.
        pcl = this;
        this.list.applySelectedAddress = function () {
            var address;
            if (pcl.list.selectedIndex>0) {
                //DL: if "select address" was selected then got first address in list
                // and others off by one
                address = addresses[pcl.list.selectedIndex-1];
                pcl.selectAddress (address);
            }
        };

        if (this.options.bootstrapUsed) {

            // Show the modal window
            jQuery(this.addressModal).modal ('show');

        }

        else {

            // Position the drop down.
            postcodeElement = null;

            for (i=0;i<this.fields.length;i++) {
                if (this.fields[i].field=='postcode') {
                    if (this.fields[i].jQuerySelector) {
                        postcodeElement = this.getElementByJquerySelector(this.fields[i].jQuerySelector);
                    }
                    else {
                        postcodeElement = this.getElement(this.fields[i].element);
                    }
                    break;
                }
            }

            if (!postcodeElement) {
                return;
            }

            var container,height,offset,pc,width;
            pc                      = jQuery (postcodeElement);
            width                   = pc.width ();
            height                  = pc.height ();
            offset                  = pc.offset ();
            container               = jQuery (this.dropdown);

            // Move the container next to the button
            container.insertAfter (this.button);
            container.css (
                {
                    top: (offset.top + height) + 'px',
                    left: offset.left + 'px'
                }
            );

            this.list.style.minWidth = width + 'px';
            container.show('fast');
            this.list.focus();

            // Custom code to remove/override hard-wired style
            container[0].setAttribute ('style','display:block');
            container[0].querySelector('select').style.backgroundColor = 'rgb(200,200,255)';
        }

    };


    // Set up the postcode lookup system for your form
    new blotto.styledPostcodeLookup (
        [
            { element: 'address_1', field: 'line1' },
            { element: 'address_2', field: 'line2' },
            { element: 'address_3', field: 'line3' },
            { element: 'town', field: 'town' },
            { element: 'county', field: 'county' },
            { element: 'postcode', field: 'postcode' }
        ],
        {
            ajaxKey: data8KeyPostcodeLookup,
            license: 'SmallUserFull'
        }
    ).show ();



