

(function () {

    new data8.postcodeLookupButton (
        [
            { element: 'address_1',  field: 'line1'     },
            { element: 'address_2',  field: 'line2'     },
            { element: 'address_3',  field: 'line3'     },
            { element: 'town',       field: 'town'      },
            { element: 'county',     field: 'county'    },
            { element: 'postcode',   field: 'postcode'  }
        ],
        {
            // Change this to your own API Key
            ajaxKey: data8KeyPostcodeLookup,      
            // Change this to your Postcode Lookup license type
            license: 'SmallUserFull'
        }
    ).show ();

})();

