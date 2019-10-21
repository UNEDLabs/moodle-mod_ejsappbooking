define(['jquery','jqueryui'], function($){

     var dpicker = function Datepicker(debug){
         this.debug = debug;
         this.elem = $('div#datepicker');
         this.bookings = [];
         
         this.log('Creating object');

         var today = new Date(); 
         var dpicker = this;

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
            selectOtherMonths: true,
            beforeShowDay: function(d){

                var date = $.datepicker.formatDate('yy-mm-dd', d);

                if ( dpicker.bookings.hasOwnProperty(date)){ // busy day
                    var tooltip = dpicker.bookings[date].join("&#013;"); 
                    dpicker.log('Marking '+date);
                    return [true,'highlight-day',tooltip];
                } else { // skip
                    $(this).removeClass('highlight-day');
                    return [true,'',''];
                }
 
            }
         });
                 
     };
     
     dpicker.prototype.log = function(msg){
        if (this.debug){
            console.log('[DATEPICKER] ' + msg);
        }
    }; 
     
     dpicker.prototype.get = function(){
         return this.elem.val();
     };
     
     dpicker.prototype.get_today = function(){
         return $('.ui-datepicker-today');
     };
    
     dpicker.prototype.set_today = function(){
            this.get_today().click();
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
    
   dpicker.prototype.set_bookings_by_date = function(bookings){
       
        if (( bookings == null ) || (bookings.length == 0)){
            return false;
        }

        var bk, d, t, n;
        for(var i = 0; i< bookings.length; i++){
            bk = bookings[i];
            d = bk['day'];
            t = bk['time'];
            n = bk['labname'];
            
            this.add_booking(d,t, n);
        }

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
    
    dpicker.prototype.update_marked_bookings = function(){
        var dpicker = this;
         
        dpicker.log('Marking booked');
         
        this.elem.datepicker( "option", "beforeShowDay", function(d){
                var date = $.datepicker.formatDate('yy-mm-dd', d);

                if ( dpicker.bookings.hasOwnProperty(date)){ // busy day
                    
                    var tooltip = dpicker.bookings[date].join("&#013;"); 
                    dpicker.log('Marking '+date);
                    return [true,'highlight-day',tooltip];
                } else { // skip
                    return [true,'',''];
                }
        });

    };
    
    dpicker.prototype.add_booking = function(day, time, labname){
        var dpicker = this;

        if ( dpicker.bookings[day] == null ){
            dpicker.bookings[day] = [];
        }

        dpicker.bookings[day].push(time + " " + labname);
        
    };
    
    dpicker.prototype.delete_booking = function(day,time){
        var dpicker = this;
        
        if ( dpicker.bookings[day] ){
            //var pos = dpicker.bookings[day].indexOf(time);
            
            var pos = -1 ;
            for (var i=0; i< dpicker.bookings[day].length; i++){
                item = dpicker.bookings[day][i];
                if ( item.substr(0,5) == time ){
                    pos = i;
                    break;
                }
            }
            
            if ( pos >= 0){
                dpicker.bookings[day].splice(pos, 1);
            }
            
            if ( dpicker.bookings[day].length == 0 ){
                delete dpicker.bookings[day];
            }
            
        }
    };
    
    dpicker.prototype.refresh = function(){
        
        var dpicker = this;
  
        dpicker.elem.datepicker("refresh");
    };
    
    dpicker.prototype.on_date_change_setup = function(data){
        
        var dpicker = this;
        
        this.elem.on('change', data, function (e){
            dpicker.log('select '+$(this).val() + " <EVENT>");
            
            var tpickr = e.data.timepicker;
            tpickr.set_today(dpicker.get_real_day() == $(this).val());

            if ( tpickr.is_today() ){
                tpickr.disable_past_hours();
                tpickr.next_free_hour();
                
                if ( tpickr.is_interval_picker_init() && tpickr.is_current_hour_select() ){ 
                    tpickr.disable_past_interv();
                    tpickr.next_free_interv();
                } else {
                    tpickr.clear_past_interv();
                }
                
            } else {
                tpickr.clear_past();
            }
                     
            var busy_slots_url = e.data.urlbase+'/get_booked_slots.php?'+
                'id='+e.data.course_id+'&labid='+e.data.lab_id+'&date='+$(this).val();
            
            tpickr.clear_busy_interv();

            $.getJSON(busy_slots_url, function( data ){
               dpicker.log( 'GET ' + busy_slots_url);
                
                if (data['busy-slots'].length  == 0 ){
                   dpicker.log('No busy slots this day');
                } else if ( tpickr.is_interval_picker_init()) {
                    tpickr.set_busy(data['busy-slots']);
                    tpickr.disable_busy_interv();
                    tpickr.next_free_interv();
                }

            });
            

     });
   };

     
    return dpicker;
});