define(['jquery', 'jqueryui', 'amd/src/lab_select.js', 'amd/src/practice_select.js', 'amd/src/date_picker.js',
        'amd/src/time_picker.js','amd/src/notif_area.js','amd/src/booking_form.js','amd/src/mybookings_table.js'], 
       function($, ui, lab_select, practice_select, date_picker, time_picker, notif_area, booking_form, mybookings_table) {
            
    var selDate = new Date(getSearchParam('selectDay'));
    var course_id = getSearchParam('id');
    
    var labsel = new lab_select();
    var pracsel = new practice_select();
    var datepicker;
    var timepicker = new time_picker();
    var notifarea = new notif_area();
    var form = new booking_form();
    var mybookings = new mybookings_table();
      
   return {
        init: function(controllerspath) {

            console.log("Loading UI");
            
            bookings_list_url = controllerspath+"/get_mybookings.php?id="+course_id+'&labid='+labsel.get_lab();
            
            $.getJSON({
                 url: bookings_list_url,
                 success: function(data){
                    bookings = Array.from(data['bookings-list']); 
                    
                    console.log('GET ' + bookings_list_url);
                    console.log('#' + bookings.length + ' items found');
                     
                   // datepicker.mark_booked(controllerspath, to_date_map(bookings));
                     
                    datepicker = new date_picker(selDate, to_date_map(bookings));
                     
                    labsel.onselect({ urlbase: controllerspath, course: course_id, lab_sel: labsel, prac_sel: pracsel, 
                        time_picker: timepicker, notif_area: notifarea, booking_form: form });
                    datepicker.onchange({ urlbase: controllerspath, course_id: course_id, lab_id: labsel.get_lab(), 
                        timepicker: timepicker});      
                    timepicker.onhourclick();
           
                    form.onsubmit({ urlbase: controllerspath, lab_sel: labsel, prac_sel: pracsel, 
                        time_picker: timepicker, date_picker: datepicker, notif_area: notif_area});   
            
                    labsel.first(); //pracsel.first();
            
                    datepicker.set_default();
                    
                    if (bookings.length > 0){ 
                        mybookings.populate({ controllerspath: controllerspath, course_id: course_id, 
                            bookings: data['bookings-list']});
                    } else {
                        console.log('No bookings to show');
                    }
                  },
                 error: function(xhr, desc, err){ //console.log(err);
                    console.log('Error getting bookings list.');
                }
             });
            
        } // end amd.init
    }; // end return
});

function on_date_select(e){

    console.log('On date select');

    (e.data.timepicker).clear();

    id= getSearchParam('id');
    labid=$('select[name=labid]').val();
    selectDay = $(this).val();
    busy_slots_url = e.data.urlbase+'/get_time_slots.php?'+'id='+id+'&labid='+labid+'&date='+selectDay;  

    $.getJSON( busy_slots_url, function( data ) { 

       console.log( 'GET ' + busy_slots_url); 

       (e.data.timepicker).children('table#hour td').on('click',{ bslots: data['busy-slots']}, 
            (e.data.timepicker).on_time_select );
    }); 
}


function on_submit_form(e){
    
    e.preventDefault();

    if ( $('#bookingform div.alert.error').is(":visible") ){
        alert($('#submit-error').html());     
        return;
    }

    labid=$('select[name="labid"]').val();
    practid=$('select[name="practid"]').val();

    date=$('#datepicker').val(); // no needed
    time=$('#timepicker ul#time-slots li.selected').html(); // delete

    // timestamp = $('#datepicker').val() + ' ' + time;
    // timestamp = timestamp.toUTCString();
    // timestamp = encodeURIComponent(timestamp);
    console.log(date + " " + time);

    url=$('#bookingform').attr('action')+"&labid="+labid+"&practid="+practid+
        "&date="+date+"&time="+time ;

    console.log(url);

    $.getJSON({
        method: 'POST',
        url:  url,
        dataType: "json",
        contentType: "application/json",
        success: function(data){
            console.log(data);
            if (data.exitCode >= 0 ){
                // mark as busy and choose skip to next slot
                cur = $('#timepicker ul#time-slots li.selected');
                cur.removeClass('slot-free').addClass('slot-busy');                    
                $('#timepicker #next_slot').click();

                $("#bookingform div#notif").attr('class','alert alert-success');
                update_mybookings_table(e.data.urlbase);
            } else {
                $("#bookingform div#notif").attr('class','alert alert-danger');
            }
                $("#bookingform div#notif").html(e.data.exitMsg);
        },
        error: function(xhr, desc, err){ // console.log(err); 
            
            $("#bookingform div#notif").html(err);
            $("#bookingform div#notif").attr('class','alert alert-danger');
        },
        complete: function (data){
            $("#bookingform div.alert").hide();
            $("#bookingform div#notif").show();   
        }
    });

}
    

function to_date_map(bookings) {
    
    var dates_times = [];  //create asociative array
    
    if (( bookings != null ) && (bookings.length > 0)){
        
        for(var i = 0; i< bookings.length; i++){
            bk = bookings[i];
            d = bk['day'];
            t = bk['time'];
            
            if ( dates_times[d] == null ){
                dates_times[d] =  t ;
            } else {
                dates_times[d] = dates_times[d] + " &#013;&#10; " + t ; 
            }
        }
    }
    
    return dates_times;
}

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
    
    if( results == null ){
        return "";
    } else {
        return results[1];
    }
}