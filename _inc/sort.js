jQuery(function ($) {

var cur = [  'EUR', 'USD', 'GBP', 'CHF', 'JPY', 'AUD', 'CAD', 'CNY',
             'DKK', 'SEK', 'NOK', 'CZK', 'PLN', 'HUF', 'RUB'         ];
 
//auto detection only works if ALL cells of a column comply with the criterion !!!
//fields must contain an ISO currency code or space to be detected as formatted-numbers
//since all table fields with ISO codes always contain a number this works.
//in addition German number fields should be detected as formatted-num regardless of
//whether they contain an ISO code or not
$.fn.dataTable.ext.type.detect.unshift( function ( data ) {
    if (typeof data !== 'undefined') {
        if (data !== null) {
            var i=0;
            while ( cur[i] ) {
                if ( data.search( cur[i] ) > -1 )   {
                    return 'formatted-num';
                }
                i++;
            }
            if ( data === '') {
                return 'formatted-num';
            }               
            if ( 've' == 've') {
                if ( ! moment(data, 'L', true).isValid() ) {
                    if ( isNaN(data) ) {
                        data = data.replace( /[\.]/g, "" );
                        data = data.replace( /[\,]/g, "." );
                        if ( ! isNaN(data) ) {
                            return 'formatted-num';
                        }
                    }
                }
            }                   
        }
    }
    return null;
} );

/*jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "formatted-num-pre": function ( a ) {
        a = (a === "-" || a === "") ? 0 : a.replace( /[^\d\-\.]/g, "" );
        return parseFloat( a );
    },
 
    "formatted-num-asc": function ( a, b ) {
        return a - b;
    },
 
    "formatted-num-desc": function ( a, b ) {
        return b - a;
    }
} );*/
 
//sorting of formatted numbers in English and German format JLMA Without test
$.extend( $.fn.dataTable.ext.type.order, {
    "formatted-num-pre": function ( a ) {
        if ( 've' == 've') {
            a = a.replace( /[\.]/g, "" );
            a = a.replace( /[\,]/g, "." );
        } else {
            a = a.replace( /[\,]/g, "" );
        }
        a = a.replace( /[^\d.-]/g, "" );
        a = parseFloat(a);
        if ( ! isNaN(a) ) {
            return a;
        } else {
//14 digit negative number to make sure empty cells always stay at the bottom / top
            return -99999999999999;
        }
    },
    "formatted-num-asc": function ( a, b ) {
            return a - b;
    },
    "formatted-num-desc": function ( a, b ) {
            return b - a;
    }
} );

});