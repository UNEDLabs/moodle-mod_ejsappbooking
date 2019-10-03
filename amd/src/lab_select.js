define(['jquery','jqueryui'], function($){
    
 /**
  * @constructor
  * @alias module:amd/src/lab_select
  */
  var lselect = function (debug){
       this.debug = debug;
       this.log('Creating object');
      
       this.elem = $('select[name=labid]').selectmenu(); 
  };
    
    lselect.prototype.log = function(msg){
        if (this.debug){
            console.log('[LABSEL] ' + msg);
        }
    };
    
    lselect.prototype.get_lab = function(){
        return this.elem.val();
    }
    
    lselect.prototype.get_lab_name = function(){
        return this.elem.children("option:selected").text();
    }
    
    lselect.prototype.first = function(){
        this.log('Selecting first');
        var first = this.elem.children('option:first').val();
        this.elem.val(first).change();
        this.elem.selectmenu("refresh").trigger("selectmenuchange");
    };
    
    lselect.prototype.on_select_setup = function(data){
        
        var labsel = this;
        
        labsel.elem.on('selectmenuchange', data, function(e){
            
            labsel.log('select <EVENT>');
            url = e.data.urlbase+'/get_lab_info.php?'+'id='+e.data.course+'&labid='+e.data.lab_sel.get_lab();

            $.getJSON( url, function( data ) {
                labsel.log('GET '+ url);
                
                e.data.prac_sel.populate(data.practices);
                
                e.data.time_picker.set_slot_size(data["slot-size"]);
                e.data.time_picker.update_interval();
                e.data.time_picker.pick_default_interv();
                
                //e.data.time_picker.update_interval({ time_picker: e.data.time_picker, slot_size: data["slot-size"]});
                e.data.notif_area.clear();
                
                if ( data.status > 0){  // default behaviour, send form
                    labsel.log('Is active');
                    e.data.notif_area.display('.plant-active');
                    e.data.booking_form.enable();
                } else { // innactive, then disable
                    labsel.log('Is innactive');
                    e.data.notif_area.display('.plant-inactive');
                    e.data.booking_form.disable();
                }

            });
        });
        
    };
    
    
    return lselect;
});