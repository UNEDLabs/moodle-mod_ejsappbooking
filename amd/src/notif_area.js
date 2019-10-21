define(['jquery','jqueryui'], function($){

    var notif = function(debug){
        this.debug = debug;
        this.elem = $('div#notif-area');
        
        this.log('Creating object');
    };
    
    notif.prototype.log = function(msg){
        if (this.debug){
            console.log('[NOTIF] ' + msg);
        }
    };
  
    notif.prototype.clear = function(){
        this.elem.children('div.alert').hide();
    };
    
    notif.prototype.display = function(selector){    
        this.log('Displaying alert '+selector);
        this.elem.children('div.alert'+selector).show();  
    };

    notif.prototype.display_msg = function(text, alert_class){
        this.elem.find('div#notif').html(text); 
        this.elem.find('div#notif').attr('class','alert '+alert_class);
        this.display('#notif');
    };
    
    notif.prototype.get_error_msg = function(){
       return $('#submit-error').html();
    };

    notif.prototype.has_errors = function(){
        return $(this).children('div.alert.error').is(":visible");
    };
    
    /* esto debería estar en el módulo form */
    
    notif.prototype.is_form_disabled = function(){
        return $(this).children('div.alert.inactive').is(":visible");
    };
    
    return notif;
    
});