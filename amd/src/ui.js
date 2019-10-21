define(['jquery', 'jqueryui','amd/src/booking_form.js','amd/src/mybookings_table.js'], 
       function($, ui, booking_form, mybookings_table) {
            
    var course_id = getSearchParam('id');
    var debug = false;
    
    require.config({ urlArgs: "bust=" + (new Date()).getTime()});
    
    return {
        init: function(controllerspath) {

            //console.log("Loading UI");
            
            var form = new booking_form({controllerspath: controllerspath,course_id: course_id, debug: debug });
            var mybookings = new mybookings_table({controllerspath: controllerspath, course_id: course_id, debug: debug,
                datepicker: form.datepicker, timepicker: form.timepicker });
            
            form.attach(mybookings);
    
            bookings_list_url = controllerspath+"/get_mybookings.php?id="+course_id;
            
            $.getJSON({
                 url: bookings_list_url,
                 success: function(data){
                    
                    //console.log('GET ' + bookings_list_url);
                     
                    var a = Array.from(data['bookings-list']);
                     
                    form.datepicker.set_bookings_by_date(a);
                
                    form.datepicker.refresh();
                     
                    if (data['bookings-list'].length > 0){ 
                        //console.log('#' + data['bookings-list'].length + ' items found');
                        mybookings.populate({ bookings: data['bookings-list'] });
                    } else {
                        //console.log('No bookings to show');
                    }
                  },
                 error: function(xhr, desc, err){ //console.log(err);
                    // console.log('Error getting bookings list.');
                }
             });
            
        } // end amd.init
    }; // end return
});

function to_date_map(bookings) {
    
    var dates_times = [];  //create asociative array
    
    if (( bookings != null ) && (bookings.length > 0)){
        
        for(var i = 0; i< bookings.length; i++){
            bk = bookings[i];
            d = bk['day'];
            t = bk['time'];
            
            if ( dates_times[d] == null ){
                dates_times[d] = new Array();
            }
            /*
            if ( dates_times[d] == null ){
                dates_times[d] =  t ;
                
            } else {
                dates_times[d] = dates_times[d] + " &#013;&#10; " + t ; 
            }
            
            */
            
            dates_times[d].push(t);
        }
    }
    
    return dates_times;
};

function setSearchParam(param, newval) {
    var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
    var query = (window.location.search).replace(regex, "$1").replace(/&$/, '');
    var newsearch = (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
    window.location = window.location.pathname + newsearch;
};

function getSearchParam(param) {
    
    // param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regex = new RegExp( "[\\?&]"+param+"=([^&#]*)" );
    var results = regex.exec(window.location.search);
    
    if( results == null ){
        return "";
    } else {
        return results[1];
    }
};