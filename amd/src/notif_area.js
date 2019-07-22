define(['jquery','jqueryui'], function($){

    var notif = function notificationArea(){
        console.log('Initializing notification area');
        this.elem = $('div#notif-area');
    };
  
    notif.prototype.clear = function(){
        this.elem.children('div.alert').hide();
    };
    
    /* TOREMOVE */
    notif.prototype.set_msg = function(text){
       this.elem.children('div#notif').html(text);  
    };
    
    notif.prototype.foo = function(){
        return 'foo';
    };
    
    notif.prototype.display = function(selector){    
        console.log('Displaying alert '+selector);
        this.elem.children('div.alert'+selector).show();  
    };

    notif.prototype.display_msg = function(text, alert_class){
        this.elem.find('div#notif').html(text); 
        this.elem.find('div#notif').attr('class','alert '+alert_class);
        this.display('#notif');
    };
    
    notif.prototype.get_error_msg = function(){
       return $('#submit-error').html()
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