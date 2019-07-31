define(['jquery','jqueryui'], function($){
        
    var pselect = function (debug){
        this.debug = debug
        
        this.log('Creating object');
        
        this.elem = $("select[name='practid']");
        this.elem.selectmenu();
        
        
        var pselect = this;
        
        this.elem.on('selectmenuselect', function(e){
            pselect.log('select <EVENT>');
        });
    };
    
   pselect.prototype.log = function(msg){
        if (this.debug){
            console.log('[PRACSEL] ' + msg);
        }
    }; 
    
    pselect.prototype.get = function(){
         return this.elem.val();
     }
        
   pselect.prototype.first = function(){
        this.log('Select first');
        var first = this.elem.children('option:first').val();
        this.elem.val(first).change();
        this.elem.selectmenu("refresh").trigger("selectmenuselect");
   };
    
    pselect.prototype.populate = function(practices){
      
        this.elem.children().remove();
        
        for (p in practices ){
            pid = p[0];
            pname = practices[p[0]];
            opt = $('<option>', { value: pid, text : pname });
            this.elem.append(opt);
        };
        
        this.first();
        this.elem.selectmenu("refresh");

    };

   return pselect;
    
});