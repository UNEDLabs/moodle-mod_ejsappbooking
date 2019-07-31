define(['jquery', 'jqueryui', 'amd/src/lab_select.js', 'amd/src/practice_select.js', 'amd/src/date_picker.js', 'amd/src/time_picker.js','amd/src/notif_area.js'], function($, ui, lab_select, practice_select, date_picker, time_picker, notif_area){

    var form = function (data) {
        // controllerspath, course_id, debug
        this.debug = data.debug;
        
        this.log('Creating object');
        
        this.elem = $('#bookingform');
        this.disabled = false;
        
        this.labsel = new lab_select(this.debug);
        this.pracsel = new practice_select(this.debug);
        this.datepicker = new date_picker(this.debug);
        this.timepicker = new time_picker(this.debug);
        this.notifarea = new notif_area(this.debug);
        
        this.labsel.on_select_setup({ urlbase: data.controllerspath, course: data.course_id, lab_sel: this.labsel, prac_sel: this.pracsel, 
                time_picker: this.timepicker, notif_area: this.notifarea, booking_form: this });
            
        this.datepicker.on_date_change_setup({ urlbase: data.controllerspath, course_id: data.course_id, lab_id: this.labsel.get_lab(), 
                timepicker: this.timepicker});      
            
        this.on_submit_setup({ urlbase: data.controllerspath, lab_sel: this.labsel, prac_sel: this.pracsel, 
                time_picker: this.timepicker, date_picker: this.datepicker, notif_area: this.notifarea});  
            
        this.labsel.first(); //pracsel.first();
            
        this.datepicker.set_today();
    };
    
    form.prototype.log = function(msg){
        if (this.debug){
            console.log('[FORM] ' + msg);
        }
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
    
    form.prototype.on_submit_setup = function(data){
        
        var booking_form = this;
        
        this.elem.on('submit', data , function (e){
            
            this.log('<EVENT> on submit');
            e.preventDefault();      

            var base_url = e.data.urlbase + booking_form.elem.attr('action');
            var labid = e.data.lab_sel.get_lab();
            var practid = e.data.prac_sel.get();
            var date = e.data.date_picker.get();
            var time = e.data.time_picker.get_current_time();
            
            if (booking_form.is_disabled()){
                this.log('Disabled');
                alert('This plant is not active at that moment.Unable to book.');
                return;
            } else {
                this.log('Enabled');
            }
            
            var tpicker = e.data.time_picker;
            var notif_area = e.data.notif_area;
            
            if ( ! ( labid || practid || date || time )) {
                notif_area.display('.submit-missing-field');
                this.log('ERROR: Missing field on submiting form');
                return;
            }
            
            var submit_url = booking_form.elem.attr('action') +"&labid="+labid+"&practid="+practid + "&date="+date+"&time="+time;

            $.getJSON({
                method: 'POST',
                url: submit_url,
                dataType: "json",
                contentType: "application/json",
                success: function(data){
                    this.log('POST '+submit_url);
                    
                    notif_area.clear();
                    
                    if (data.exitCode >= 0){
                        notif_area.display_msg(data.exitMsg, 'alert-success');
                        tpicker.next_free_interv();
                        
                        // TODO: update mybookings table
                        
                    } else { 
                        notif_area.display_msg(data.exitMsg, 'alert-danger');      
                    }
                },
                error: function(xhr, desc, err){
                    this.log(err);
                    //$("#bookingform div#notif").html();
                    notif_area.display_msg(err, 'alert-danger');
                },
                complete: function(data){
                    //e.data.notif_area.clear();
                    //e.data.notif_area.display('#notif');
                }
            });

        });
     };     
    
   return form;
         
});