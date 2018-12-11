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
               // setSearchParam('selectDay', $(this).val());
                
               // $(this).defaultDate(new Date($(this).val()));
            }
        });
  /*      
        if ( getSearchParam('selectDay') == "" ){
             setSearchParam('selectDay', $('div#datepicker').val());
        }
*/
            
	    $('select').selectmenu();
            
        $('select[name=labid]').on('selectmenuchange', function() {
            id= getSearchParam('id');
            labid = $(this).val();
            url= '/mod/ejsappbooking/lab.php?'+'id='+id+'&labid='+labid+'action=info';
            
            
            $.getJSON( url, function( data ) {
                
                 $("select#foo").trigger('click');
                
                 $('#ms').html($("select[name='practid']").html());
                 
                 $("select[name='practid']").children().remove();
                
                 for (var p in data.practices ){
                   pid = p[0];
                   pname = data.practices[p[0]];
                   opt = $('<option>', { 
                       value: pid,
                       text : pname
                   });

                   $("select[name='practid']").append(opt);
                };

               $('select[name=practid] option:first').attr('selected','selected');
               $("select[name='practid']").selectmenu("refresh");
                
                if ( data.status == 0){ // innactic3
                    // display message
                    $('#lab-status').css('display','block');
                    
                    // disable booking button
                    $("button[name='bookingbutton']").click(function(){
                        alert('This plant is not active at that moment.Unable to book.')
                    });         
                    
                }else { //active
                    
                    // send form on click
                    
                }
                

            });
            
        });
            
	    $('button').button();  
            
        $('#timepicker button.btn_up').button( "option", "icon", "ui-icon-caret-1-n" );
        $('#timepicker button.btn_down').button( "option", "icon", "ui-icon-caret-1-s" );
            
        $('#timepicker #sep').height($('#timepicker').height());            
        $('#timepicker #mod').height($('#timepicker').height());                        
                        
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

