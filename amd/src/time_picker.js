define(['jquery','jqueryui'], function($){

var tpicker = function timePicker(){
    
    console.log('Initializing timepicker');
    
    this.elem = $('#timepicker');
    
    this.hcells = function (){
        return this.elem.find('table#hour td.hour'); 
    };
    
    this.icells = function (){
        return this.elem.find('table#interval td'); 
    };
    this.today = true;
    this.busy_slots = null;
    
};

tpicker.prototype.get_current_hour = function(){
    var item = this.get_current_hour_item();
    if ( ! item ) return null;
    
    var h = (this.get_current_hour_item()).text();
    var hnum = this.convert_12to24(h);
    
    h = hnum.toString();
    
    if (hnum < 10) {
        h = '0' + h;
    } 
    
    console.log('Current hour is ' + h);
    
    return h ;
};
    
tpicker.prototype.get_current_hour_item = function (){
    return this.hcells().filter('.hour-current');
}

tpicker.prototype.get_real_hour = function(){
   var now = new Date();
   return now.getHours();
}    
    
tpicker.prototype.convert_12to24 = function(h){
    if ( h == ''){ return null }

    period = h.substring(h.length - 2);
    h = h.substr(0, h.length - 2);
    hnum = parseInt(h);

    if ( period == 'PM'){
        hnum = hnum + 12  ;
    }

    return hnum;
};

tpicker.prototype.get_current_interv = function(){
    return this.get_current_interv_item().text();
};   

tpicker.prototype.get_current_interv_item = function(){
    return this.icells().filter('td.interv-current');
};

tpicker.prototype.get_current_time = function(){
    var h = this.get_current_hour();
    var i = this.get_current_interv();
   
    return h + i;
};

tpicker.prototype.set_today = function (is_today){
    this.today = is_today;  
};
    
tpicker.prototype.is_today = function(){
    return this.today;
};

tpicker.prototype.pick_default = function(){
    this.hcells().eq(0).click().fadeIn('fast');
    this.icells().first().click().fadeIn('fast');
};

tpicker.prototype.clear_past_hours = function(){
    this.hcells().removeClass('hour-past disabled');
};
    
tpicker.prototype.clear_past_interv = function(){
    
    this.icells().removeClass('interv-past');
    
     this.icells().each(function(){
        if ( ! $(this).hasClass('interv-busy')){
            $(this).removeClass('disabled');
            $(this).on();
        };
    });
};
    
tpicker.prototype.clear_past = function(){
    this.clear_past_hours();
    this.clear_past_interv();
}

tpicker.prototype.clear = function(){
    this.unselect_hour();
    this.unselect_interval();
};

tpicker.prototype.select_hour = function(item){   
    item.addClass('time-highlight hour-current');
};
    
tpicker.prototype.unselect_hour = function(){
    this.hcells().removeClass('time-highlight hour-current');
};

tpicker.prototype.select_interval = function(item){
    item.addClass('time-highlight interv-current');
};

tpicker.prototype.unselect_interval = function(){
    this.icells().removeClass('time-highlight interv-current');
};
    
tpicker.prototype.update_interval = function(data){
    
    var slot_size = data.slot_size;
    var n = data.time_picker.icells.length;
    
    console.log('Updating time-interval picker ('+slot_size+')');
    
    var interv_pickr=this.elem.find('table#interval');
  
    // delete previous items
    interv_pickr.find('td').remove(); // .not(':first')

    // insert first item
    interv_pickr.find('tr').append('<td>:00</td>');

    // insert further intervals adding slot_size to the previous one
    period = slot_size;
    while ( period < 60 ) {
        label = period;
        if ( period < 10 ) label = '0' + label;
        cell = '<td>:' + label + '</td>';
        interv_pickr.find('tr').append(cell);
        period += slot_size;
    } 
    
    // setup behaviour
    this.onintervalclick();
 
    // scroll bar for small slot size
    if ( this.icells().length > 8 ){
        interv_pickr.css('overflow-x', 'scroll');
    } else {
        interv_pickr.css('overflow-x', 'hidden');
    }
    
};
    
tpicker.prototype.onhourclick = function(){
    var hcells = this.hcells();
    var tpicker = this;
    
    hcells.on('click', function(e){
        console.log('<EVENT> On hour select');
        tpicker.unselect_hour();
        //hcells.removeClass('time-highlight hour-current');
        
        tpicker.select_hour($(this));
        //$(this).addClass('time-highlight hour-current');
        
        if ( tpicker.get_current_hour() == tpicker.get_real_hour() ){
            tpicker.update_past_interv();
        }
        
        tpicker.update_busy_interv();
        tpicker.next_free_interv();
    });
    
};
    
tpicker.prototype.onintervalclick = function(){
    var tpicker = this;
    var icells = this.icells();
    
    icells.on('click', function(e){
        console.log('<EVENT> On interval select' );
        tpicker.unselect_interval();
        tpicker.select_interval($(this));
        //icells.removeClass('time-highlight interv-current');
        //$(this).addClass('time-highlight interv-current');
        
    });
};

tpicker.prototype.update_past_hours = function (){

    var tpicker = this;
    var hours = this.hcells();
    var now = new Date();
    var h = now.getHours();

    if (! this.is_today()){
        console.log('No previous hours to remove');
        this.clear_past_hours();
        return;
    }
    
    console.log('Disabling previous hours:' + h);
    
    hours.each( function( index ){
        var h2 = tpicker.convert_12to24($(this).text());
        
        if ( h2 < h ){
            console.log('Disabling ' + h2 +'h');
            $(this).addClass('hour-past disabled');
            $(this).off();
        } else { //( h2 == h ) => current time, stop checking past
            return false;
        };
        
    });
    
    this.next_free_hour();
};

tpicker.prototype.next_free_hour = function(){
    var current = this.get_current_hour_item();
    var pos = this.hcells().index(current);
    var total = this.hcells().length;
    
    console.log('Seeking next free hour #' + pos + "/" + total);
    
    if (current.hasClass('disabled')){
        console.log('Unselecting #' + pos);
        this.unselect_hour();
    }
    
    var next = this.hcells().filter(':not(.disabled)').first();
    
    if ( next ) {
        console.log('Selecting ' + pos);
        this.select_hour(next);
    }
}    
    
tpicker.prototype.next_free_interv = function(){
  
    var current = this.get_current_interv_item();
    var pos = this.icells().index(current);
    var total = this.icells().length;
    
    console.log('Seeking next free interv #' + pos + "/" + total);
    
    if (current.hasClass('disabled')){
        console.log('Unselecting #' + pos);
        this.unselect_interval();
    };
    
    var next = this.icells().filter(':not(.disabled)').first();
    
    if ( next ) {
        console.log('Selecting ' + pos);
        this.select_interval(next);
        this.scrollTo(next);
    }
    
};
    
tpicker.prototype.scrollTo = function (item){
    
    // https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollIntoView
    
    console.log('Scrolling to ' + item.text());
    item.get(0).scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
}
    
tpicker.prototype.set_busy = function(busy_slots){
    this.busy_slots = busy_slots;
};
     
tpicker.prototype.update_busy_interv = function( ){
    
    var time_picker = this;
    
    if ( ( time_picker.busy_slots == null ) || timepicker_picker.busy_slots.length == 0 ){ 
        console.log('No busy slots found');
        return; 
    }; 
    console.log('Disabling busy slots');
    
    this.elem.find('table#interval td').each( function( index, item ){

        time = time_picker.get_current_hour() + $(this).text();
        
        if ( time_picker.busy_slots.includes(time) ) { // busy, disable
            $(this).addClass('interval-busy disabled');
            $(this).off();
            console.log('Disabling ' + time);
        } else {
            $(this).removeClass('interval-busy');
            $(this).on();
        }

    });
    
    this.next_free_interv();
};

tpicker.prototype.update_past_interv = function (){
    
    var tpicker = this;
    var intervs = this.icells();
    var now = new Date();
    var h = now.getHours();
    var i = parseInt(now.getMinutes());
    
    var h1 = this.get_current_hour();
    //var i1 = this.get_current_interv();
    
    if ( h1 != h ) { 
        console.log('No past intervals to remove' + h);
        this.clear_past_interv();
        return;
    };
    
    console.log('Disabling previous intervals: 0-' + i );
    
    intervs.each(function(){
        
        var i2 = parseInt(($(this).text()).substring(1));
        
        console.log( i2 + ' ' + i);
        
        if ( i2 < i ){
            console.log('Disabling ' + i2 +'min');
            $(this).addClass('interv-past disabled');
            $(this).off();
        } else { // current time, stop checking past
            return false;
        };
        
    });
    
    this.next_free_interv();
};    
    
return tpicker;
    
});