define(['jquery','jqueryui'], function($){

     var dpicker = function Datepicker(current_date, bookings){
         this.elem = $('div#datepicker');
         
         console.log('Initializing datepicker');

         var today = new Date(); 

         this.elem.datepicker({	
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            gotoCurrent: true,
            firstDay: 1,
            minDate: today,
            defaultDate: current_date,
            numberOfMonths: [ 1, 1 ],
            altField: "#date",
            showOtherMonths: true,
            selectOtherMonths: true,
            beforeShowDay: function(d){
                var date = $.datepicker.formatDate('yy-mm-dd', d);
                console.log('Marking ' + date );
                if ( bookings.hasOwnProperty(date)){ // busy day
                    return [true, 'highlight-day', bookings[date]];
                } else { // skip
                    return [true,'',''];
                }
           }
         });
                 
     }
     
     dpicker.prototype.get = function(){
         return this.elem.val();
     }
     
     dpicker.prototype.set_default = function(){
            $('.ui-datepicker-today').click();
     }

     dpicker.prototype.mark_booked = function(controllerspath, dates_times){
        
        console.log('Marking booked on datepicker');
         
        this.elem.datepicker({
            beforeShowDay: function(d){
                var date = $.datepicker.formatDate('yy-mm-dd', d);
                console.log('Marking ' + date );
                console.log(date_times);
                if ( dates_times.hasOwnProperty(date)){ // busy day
                    return [true, 'highlight-day',dates_times[date]];
                } else { // skip
                    return [true,'',''];
                }
           }
        });  
    };
    
    dpicker.prototype.onchange = function(data){
        
        var dpicker = this;
        
        this.elem.on('change', data, function (e){
            console.log('<EVENT> On date select '+$(this).val());
            
           // Interesa esto? no si estamos buscando fecha para una hora concreta
           // e.data.timepicker.pick_default();
            
            e.data.timepicker.set_today(dpicker.get_real_day() == $(this).val());
       
            busy_slots_url = e.data.urlbase+'/get_time_slots.php?'+
                'id='+e.data.course_id+'&labid='+e.data.lab_id+'&date='+$(this).val();  

            $.getJSON(busy_slots_url, function( data ){
                console.log( 'GET ' + busy_slots_url + " " + data);
                
                if ( e.data.timepicker.is_today() ){
                    console.log('Today');
                    e.data.timepicker.update_past_hours();
                    
                    if ( e.data.timepicker.get_current_hour() == e.data.timepicker.get_real_hour()){    
                        e.data.timepicker.update_past_interv();
                    }
                } else {
                    e.data.timepicker.clear_past();
                }
                
                if ( data['busy-slots'].length  > 0) {
                    e.data.timepicker.set_busy(data['busy-slots']);
                    e.data.timepicker.update_busy_interv();
                }else {
                    console.log('No busy slots this day');
                }
            });

     });
   };

   dpicker.prototype.get_real_day = function(){
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth();
        (++m < 10)? m = "0" + m : m;
        var d = now.getDate();
        (d < 10)? d = "0" + d : d;
     
         return y + "-" + m + "-" + d; 
   };
     
    return dpicker;
});