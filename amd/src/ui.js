define(['jquery', 'jqueryui'], function($) {
    return {
        init: function() {
            
		// alert("loading amd module");
        var today = new Date(); 
        var current = new Date(getSearchParam('selectDay'));
            
        $('div#datepicker').datepicker({	
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            gotoCurrent: true,
            minDate: today,
            defaultDate: current,
            numberOfMonths: [ 1, 1 ],
            onSelect: function(date){
                setSearchParam('selectDay', $(this).val());
               // $(this).defaultDate(new Date($(this).val()));
            }
        });
        
        if ( getSearchParam('selectDay') == "" ){
             setSearchParam('selectDay', $('div#datepicker').val());
        }
            
	    $('select').selectmenu();
	    $('button').button();            
            
        
	}

    }
});

function setSearchParam(param, newval) {
    var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
    var query = (window.location.search).replace(regex, "$1").replace(/&$/, '');
    var newsearch = (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
    window.location = window.location.pathname + newsearch;
}

function getSearchParam(param) {
    
    // param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regex = new RegExp( "[\\?&]"+param+"=([^&#]*)" );
    var results = regex.exec(window.location.search);
    
    if( results == null )
        return "";
    else
        return results[1];
}

