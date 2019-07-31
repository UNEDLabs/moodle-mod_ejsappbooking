define(['jquery','jqueryui'], function($){

     var dpicker = function Datepicker(debug){
         this.debug = debug;
         this.elem = $('div#datepicker');
         
         this.log('Creating object');

         var today = new Date(); 

         this.elem.datepicker({	
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            gotoCurrent: true,
            firstDay: 1,
            minDate: today,
            numberOfMonths: [ 1, 1 ],
            altField: "#date",
            showOtherMonths: true,
            selectOtherMonths: true
         });
                 
     };
     
     dpicker.prototype.log = function(msg){
        if (this.debug){
            console.log('[DATEPICKER] ' + msg);
        }
    }; 
     
     dpicker.prototype.get = function(){
         return this.elem.val();
     }
     
     dpicker.prototype.get_today = function(){
         return $('.ui-datepicker-today');
     }
    
     dpicker.prototype.set_today = function(){
            this.get_today().click();
     }
     
   dpicker.prototype.get_real_day = function(){
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth();
        (++m < 10)? m = "0" + m : m;
        var d = now.getDate();
        (d < 10)? d = "0" + d : d;
     
         return y + "-" + m + "-" + d; 
   };

     dpicker.prototype.mark_booked = function(bookings){
        var dpicker = this;
         
        dpicker.log('Marking booked');
         
        this.elem.datepicker( "option", "beforeShowDay", function(d){
                var date = $.datepicker.formatDate('yy-mm-dd', d);
                if ( bookings.hasOwnProperty(date)){ // busy day 
                    dpicker.log('Marking ' + date );
                    return [true, 'highlight-day', bookings[date]];
                } else { // skip
                    return [true,'',''];
                }
        });
         
    };
    
    dpicker.prototype.on_date_change_setup = function(data){
        
        var dpicker = this;
        
        this.elem.on('change', data, function (e){
            dpicker.log('select '+$(this).val() + " <EVENT>");
            
           // Interesa esto? no si estamos buscando fecha para una hora concreta
           // e.data.timepicker.pick_default();
            
            var tpickr = e.data.timepicker;
            tpickr.set_today(dpicker.get_real_day() == $(this).val());

            if ( tpickr.is_today() ){
                tpickr.disable_past_hours();
                tpickr.next_free_hour();
                
                if ( tpickr.is_interval_picker_init() && (tpickr.get_current_hour() == e.data.timepicker.get_real_hour())) {    
                    tpickr.disable_past_interv();
                    tpickr.next_free_interv();
                } else {
                    tpickr.clear_past_interv();
                }
                
            } else {
                tpickr.clear_past();
            }
                     
            busy_slots_url = e.data.urlbase+'/get_time_slots.php?'+
                'id='+e.data.course_id+'&labid='+e.data.lab_id+'&date='+$(this).val();
            
            tpickr.clear_busy_interv();

            $.getJSON(busy_slots_url, function( data ){
               dpicker.log( 'GET ' + busy_slots_url + " " + data);
                
                if (data['busy-slots'].length  == 0 ){
                   dpicker.log('No busy slots this day');
                } else if ( tpickr.is_interval_picker_init()) {
                    tpickr.set_busy(data['busy-slots']);
                    tpickr.disable_busy_interv();
                    tpickr.next_free_interv();
                }   
                
                // si no quedan interv libres pasar a la siguiente hora.

            });
            

     });
   };

     
    return dpicker;
});