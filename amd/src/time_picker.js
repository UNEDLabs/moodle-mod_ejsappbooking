define(['jquery','jqueryui'], function($){

var tpicker = function timePicker(debug){
    this.debug = debug;
    
    this.log('Creating object');
    
    this.elem = $('#timepicker');
    
    this.today = true;
    this.busy_slots = null;
    this.slot_size = -1;
    
    this.hcells().on('click', { tpicker: this }, this.on_hour_click );
    this.pick_default_hour();
};
  
tpicker.prototype.log = function(msg){
    if (this.debug){
        console.log('[TIMEPICKER] ' + msg);
    }
};
    
tpicker.prototype.hcells = function(){
    return this.elem.find('table#hour td.hour'); 
};

tpicker.prototype.icells = function(){
    return this.elem.find('table#interval td'); 
};  
    
tpicker.prototype.pick_default_hour = function(){
    this.hcells().first().click();
};
    
tpicker.prototype.is_interval_picker_init = function(){
    return ( this.icells().length > 0 );
};

tpicker.prototype.pick_default_interv = function(){
    this.icells().first().click().fadeIn('fast');
};
    
tpicker.prototype.get_current_hour = function(){
    var item = this.get_current_hour_item();
    
    if (  item.length == 0 ) {
        return null;
    }
    
    var h = (this.get_current_hour_item()).text();
    var hnum = this.convert_12to24(h);
    
    h = hnum.toString();
    
    if (hnum < 10) {
        h = '0' + h;
    } 
    
    return h ;
};
    
tpicker.prototype.get_current_hour_item = function(){
    return this.hcells().filter('.hour-current');
};

tpicker.prototype.get_real_hour = function(){
   var now = new Date();
   return now.getHours();
};
    
tpicker.prototype.is_current_hour_outdated = function (){
   return ( this.get_current_hour() < this.get_real_hour());
};

tpicker.prototype.convert_12to24 = function(h){
    if ( h == '') {
        return null;
    }

    var period = h.substring(h.length - 2);
    h = h.substr(0, h.length - 2);
    var hnum = parseInt(h);

    if ( period == 'PM') {
        hnum = hnum + 12  ;
    }

    return hnum;
};

tpicker.prototype.get_current_interv = function(){
    
    var item = this.get_current_interv_item();
    
    if ( item.length == 0 ) {
        return null;
    } else {
        return this.get_current_interv_item().text(); 
    }
};   

tpicker.prototype.get_current_interv_item = function(){
    return this.icells().filter('td.interv-current');
};

tpicker.prototype.get_current_time = function(){
    var h = this.get_current_hour();
    var i = this.get_current_interv();
   
    return h + i;
};

tpicker.prototype.set_today = function(is_today){
    this.log( "today= " + is_today );
    this.today = is_today;  
};

tpicker.prototype.set_slot_size = function(slot_size){
    this.log('slot-size='+ slot_size);
    this.slot_size = slot_size;
};
    
tpicker.prototype.is_today = function(){
    return this.today;
};

tpicker.prototype.is_current_hour_select = function (){
    
    return (this.get_current_hour() == this.get_real_hour());
};

tpicker.prototype.clear_past_hours = function(){
    this.hcells().removeClass('hour-past disabled').on('click', { tpicker: this }, this.on_hour_click);
};
    
tpicker.prototype.clear_past_interv = function(){
    this.icells().removeClass('interv-past');    
    this.icells().not('.interv-busy').removeClass('disabled').on('click', { tpicker: this }, this.on_interv_click );
};

tpicker.prototype.clear_interv = function(){
    this.icells().removeClass('interv-past interv-busy disabled');    
    this.icells().on('click', { tpicker: this }, this.on_interv_click );
};
    
tpicker.prototype.clear_busy_interv = function(){
    this.busy_slots = null;
    this.icells().removeClass('interv-busy');
    this.icells().not('.interv-past').removeClass('disabled').on('click', { tpicker: this }, this.on_interv_click );
};
    
tpicker.prototype.clear_past = function(){
    this.clear_past_hours();
    this.clear_past_interv();
};

tpicker.prototype.clear = function(){
    this.unselect_hour();
    this.unselect_interval();
};

tpicker.prototype.select_hour = function(item){  
    this.log('hour='+item.text());
    item.addClass('time-highlight hour-current');
};
    
tpicker.prototype.unselect_hour = function(){
    this.log('hour={}');
    this.hcells().removeClass('time-highlight hour-current');
};
    
tpicker.prototype.set_past_hour = function(item){
    item.addClass('hour-past disabled');
    item.off();
};

tpicker.prototype.select_interval = function(item){
    this.log('interv='+item.text());
    item.addClass('time-highlight interv-current');
};

tpicker.prototype.unselect_interval = function(){
    this.log('interv={}');
    this.icells().removeClass('time-highlight interv-current');
};
    
tpicker.prototype.update_interval = function(){
    this.log('Updating interv picker ('+this.slot_size+')');
    
    var slot_size = this.slot_size;
    var tpickr = this;
   // var n = data.time_picker.icells.length;
    
    var interv_pickr=this.elem.find('table#interval');
  
    // delete previous items
    interv_pickr.find('td').remove(); // .not(':first')

    // insert first item
    interv_pickr.find('tr').append('<td>:00</td>');

    // insert further intervals adding slot_size to the previous one
    var period = slot_size;
    while ( period < 60 ) {
        var label = period;
        if ( period < 10 ) label = '0' + label;
        var cell = '<td>:' + label + '</td>';
        interv_pickr.find('tr').append(cell);
        period += slot_size;
    } 
    
    // setup behaviour
    tpickr.icells().on('click', { tpicker: this }, this.on_interv_click );
 
    // scroll bar for small slot size
    if ( this.icells().length > 8 ) {
        interv_pickr.css('overflow-x', 'scroll');
    } else {
        interv_pickr.css('overflow-x', 'hidden');
    }
    
    tpickr.disable_busy_interv();
    tpickr.disable_past_interv();
    tpickr.next_free_interv();
};
    
tpicker.prototype.on_hour_click = function(e){
    e.preventDefault();
    
    var tpicker = e.data.tpicker;

    tpicker.log('hour click <EVENT>');

    tpicker.unselect_hour();
    tpicker.select_hour($(this));
    
    tpicker.clear_interv();
    
    if ( tpicker.is_today() && (tpicker.get_current_hour() == tpicker.get_real_hour()) ){
            tpicker.disable_past_interv();
    }

    tpicker.disable_busy_interv();
    tpicker.next_free_interv();
};
    
tpicker.prototype.on_interv_click = function(e){
    var tpicker = e.data.tpicker;
        
    tpicker.log('interval click <EVENT>');
    
    e.preventDefault();      
    
    tpicker.unselect_interval();
    tpicker.select_interval($(this));
};
 
tpicker.prototype.disable_past_hours = function (){
    var tpicker = this;
    var hours = this.hcells();
    var now = new Date();
    var h = now.getHours();

    if (! this.is_today()){
        tpicker.log('NOT disabling past hours');
        this.clear_past_hours();
        return;
    }

    tpicker.log('Disabling past hours:' + h);

    hours.each( function( index ){
        var h2 = tpicker.convert_12to24($(this).text());
        
        if ( h2 < h ){
            tpicker.set_past_hour($(this));
        } else { //( h2 == h ) => current time, stop checking past
            return false;
        }
        
    });
};

tpicker.prototype.next_free_hour = function(){
    
    var tpicker = this;
    var current = this.get_current_hour_item();
    var pos = this.hcells().index(current);
    
    tpicker.log('next free hour');
    
    if ( pos < 0 ) {
        tpicker.log('No hour selected');
        current = this.hcells().first();
        pos = 0;
    } else {
       this.unselect_hour();
    }
    
    var next = this.hcells().filter(':not(.disabled)').first();
    
    if ( next.length > 0 ) { this.select_hour(next); }
    
};
    
tpicker.prototype.set_busy = function(busy_slots){
    this.busy_slots = busy_slots;
};
    
tpicker.prototype.add_busy = function(time){
    if (this.busy_slots == null) {
        this.busy_slots = [];
    }
    this.busy_slots.push(time);
    this.busy_slots.sort();
};

tpicker.prototype.del_busy = function(time){
    if (this.busy_slots == null) {
        return;
    }
    var pos = this.busy_slots.indexOf(time);
    
    if ( pos > 0 ){
        this.busy_slots.splice(pos, 1);
    }
};
    
tpicker.prototype.set_past_interv = function (item){
    item.addClass('interval-busy disabled');
    item.off();
};

tpicker.prototype.set_busy_interv = function (item){
    item.addClass('interval-busy disabled');
    item.off();
};

     
tpicker.prototype.disable_busy_interv = function( ){
    var time_picker = this;
    
    if ((time_picker.busy_slots == null) || (time_picker.busy_slots.length == 0)){
        time_picker.log('No busy slots found');
        return; 
    } else {
        time_picker.log('Disabling busy slots');
    }
    
    this.elem.find('table#interval td').each( function( index, item ){

        time = time_picker.get_current_hour() + $(this).text();
        
        if (time_picker.busy_slots.includes(time)) { // busy, disable
            time_picker.log('Disabling ' + time);
            time_picker.set_busy_interv($(this));
        } 
        /*
        else {
            $(this).removeClass('interval-busy');
            $(this).on();
        }
        */

    });
};

tpicker.prototype.update_busy_interv = function(){
    this.clear_busy_interv();
    this.disable_busy_interv();
    this.next_free_interv();
};

tpicker.prototype.disable_past_interv = function(){
    var tpicker = this;
    var intervs = this.icells();
    var now = new Date();
    var h = now.getHours();
    var i = parseInt(now.getMinutes());
    
    var h1 = this.get_current_hour();
    //var i1 = this.get_current_interv();
    
    if ( h1 != h ) { 
        tpicker.log('No past intervals to remove ' + h1 +" "+ h);
        tpicker.clear_past_interv();
        return;
    }
    
    tpicker.log('Disabling previous intervals: 0-' + i );
    
    intervs.each(function(){

        if ( $(this).next() != null ){
            end = parseInt(($(this).next().text()).substring(1));
            if (isNaN(end)) {
                end = 60;
            }
        } else {
            end = 60;
        }

        if (end === 60) {
            $(this).addClass('interv');
            $(this).on();
        } else if (  end <= i  ){
            $(this).addClass('interv-past disabled');
            $(this).off();
        } else { // current time, stop checking past
            return false;
        }
        
    });
};    

tpicker.prototype.next_free_interv = function(scroll) {
    scroll = typeof scroll !== 'undefined' ? scroll : true;
    var tpicker = this;
    var current = this.get_current_interv_item();
    var pos = this.icells().index(current);
    
    tpicker.log('next free interv');
    tpicker.unselect_interval();
    
    if ( ! this.is_interval_picker_init()){
        tpicker.log('Interval picker not found');
        return;
    }

    if ( pos < 0 || pos == this.icells().length - 1) {
        if (pos < 0) tpicker.log('No interval selected');
        current = this.icells().first();
        pos = 0;
    }
    
    if (current.hasClass('disabled')) {
        this.unselect_interval();
    }
    
    var next = this.icells().filter(':not(.disabled)').first();
    
    if ( next.length > 0) {
        this.select_interval(next);
        if (scroll) this.scrollTo(next);
    }
};
    
tpicker.prototype.scrollTo = function(item){
    this.log('Scrolling to ' + item.text());
    item.get(0).scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
};
    
return tpicker;
});