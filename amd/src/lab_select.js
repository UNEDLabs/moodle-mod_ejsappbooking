define(['jquery','jqueryui'], function($){
    
 /**
  * @constructor
  * @alias module:amd/src/lab_select
  */
  var lselect = function LabSelect(){
       console.log('Initializing lab select');
       this.elem = $('select[name=labid]').selectmenu();      
  };
    
    lselect.prototype.get_lab = function(){
        return this.elem.val();
    }
    
    lselect.prototype.first = function(){
        console.log('Selecting first lab');
        var first = this.elem.children('option:first').val();
        this.elem.val(first).change();
        this.elem.selectmenu("refresh").trigger("selectmenuchange");

    };
    
    /*
       labsel.onselect({ urlbase: controllerspath, course: course, lab_sel: labsel, prac_sel: pracsel, 
         time_picker: timepicker, notif_area: notifarea, booking_form: form });
     */
    lselect.prototype.onselect = function(data){
        
        this.elem.on('selectmenuchange', data, function(e){
            
            console.log('<EVENT> On lab select');
            url = e.data.urlbase+'/get_lab_info.php?'+'id='+e.data.course+'&labid='+e.data.lab_sel.get_lab();

            $.getJSON( url, function( data ) {
                console.log('GET '+ url);
                
                e.data.prac_sel.populate(data.practices);
                e.data.time_picker.update_interval({ time_picker: e.data.time_picker, slot_size: data["slot-size"]});
                e.data.time_picker.pick_default();
                e.data.notif_area.clear();
                
                if ( data.status > 0){  // default behaviour, send form
                    e.data.notif_area.display('.plant-active');
                    e.data.booking_form.enable();
                } else { // innactice, then disable
                    e.data.notif_area.display('.plant-inactive');
                    e.data.booking_form.disable();
                }

            });
        });
        
        return this;
};
    
    return lselect;
});