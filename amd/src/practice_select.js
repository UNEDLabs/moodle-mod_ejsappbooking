define(['jquery','jqueryui'], function($){
        
    var pselect = function (){
        
        console.log('Initializing practice select');
        
        this.elem = $("select[name='practid']");
        this.elem.selectmenu();
        
        this.elem.on('selectmenuselect', function(e){
            console.log('<EVENT> On practice select');
        });
    };
    
    pselect.prototype.get = function(){
         return this.elem.val();
     }
        
   pselect.prototype.first = function(){
        console.log('Selecting first practice');
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