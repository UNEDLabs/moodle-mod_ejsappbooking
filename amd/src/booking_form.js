define(['jquery','jqueryui'], function($){

    var form = function () {
        this.elem = $('#bookingform');
        this.disabled = false;
    };

    form.prototype.disable = function(){
        this.elem.disabled = true;
    };
    
    form.prototype.enable = function (){
        this.elem.disabled = false;
    };
    
    form.prototype.is_disabled = function(){
        return this.elem.disabled;
    };
    
    
    form.prototype.onsubmit = function(data){
        
        var booking_form = this;
        
        this.elem.on('submit', data , function (e){
            
            console.log('<EVENT> Submit form');
            e.preventDefault();      

            var base_url = e.data.urlbase + booking_form.elem.attr('action');
            var labid = e.data.lab_sel.get_lab();
            var practid = e.data.prac_sel.get();
            var date = e.data.date_picker.get();
            var time = e.data.time_picker.get_current_time();
            
            if (booking_form.is_disabled()){
                console.log('Form is disabled');
                alert('This plant is not active at that moment.Unable to book.');
                return;
            } else {
                console.log('Form is enabled');
            }
            
            if ( ! ( labid || practid || date || time )) {
                e.data.notif_area.display('.submit-missing-field');
                console.log('ERROR: Missing field on submiting form');
                return;
            }

            var submit_url = booking_form.elem.attr('action') +"&labid="+labid+"&practid="+practid + "&date="+date+"&time="+time;
            
            console.log(submit_url);

            $.getJSON({
                method: 'POST',
                url:  submit_url,
                dataType: "json",
                contentType: "application/json",
                success: function(data){
                    console.log('POST '+submit_url);
                    console.log(data);
                    
                    /*
                    if (data.exitCode >= 0 ){
                        // TOFIX: mark as busy and choose skip to next slot
                        //cur = $('#timepicker ul#time-slots li.selected');
                        //cur.removeClass('slot-free').addClass('slot-busy');
                        //e.data.timepicker.children('#next_slot').click(); 

                        //$("#bookingform div#notif").attr('class','alert alert-success');
                        // add booking to table | update full table => GET
                        // update_mybookings_table(e.data.urlbase);
                        // (e.data.mybookings_table).populate(e.data.urlbase);

                    } else {
                        (e.data.notif_area).display('#notif', 'alert-danger');
                    }*/
                    
                    if (data.exitCode >= 0){
                        (e.data.notif_area).display('.plant-inactive');
                        //notif_area.display_msg(data.exitMsg, 'alert-success');   
                    } else { 
                        e.data.notif_area.display('.submit-missing-field');
                        //notif_area.display_msg(data.exitMsg, 'alert-danger');      
                    }
                },
                error: function(xhr, desc, err){
                    console.log(err);
                    //$("#bookingform div#notif").html();
                    (e.data.notif_area).set_msg(err);
                    (e.data.notif_area).display_msg(err, 'alert-danger');
                    //$("#bookingform div#notif").attr('class','alert alert-danger');
                },
                complete: function(data){
                    (e.data.notif_area).clear();
                    (e.data.notif_area).display('#notif');
                }
            });

        });
     };     
    
   return form;
         
});