define(['jquery', 'jqueryui'], function($) {
    return {
        init: function(controllerspath) {
		// alert("loading amd module")
            
        // init lab selector    
        $('select').selectmenu();
            
        $('select[name=labid]').on('selectmenuchange', { urlbase: controllerspath }, on_lab_select );
            
        // select first lab
        firstlab = $('select[name=labid] option:first').val();
        
        $('select[name=labid]').val(firstlab)
            .selectmenu("refresh")
            .trigger("selectmenuchange");                
            
        // init datepicker 
        var today = new Date(); 
        var current = new Date(getSearchParam('selectDay'));
        
        $('div#datepicker').datepicker({	
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            gotoCurrent: true,
            firstDay: 1,
            minDate: today,
            defaultDate: current,
            numberOfMonths: [ 1, 1 ],
            altField: "#date"
        });
            
        $('#datepicker').on('change',  { urlbase: controllerspath }, on_day_select);
        
        // select current day
        
        init_timepicker();        
            
        $('#datepicker .ui-datepicker-current-day').click();    
            
        // init_submit_btn(controllerspath);
            
        $('#bookingform').on('submit',  { urlbase: controllerspath }, on_submit_form);
            
        update_mybookings_table(controllerspath);
            
        }
      }
});

function on_submit_form(e){
    
    e.preventDefault();

    if ( $('#bookingform div.alert.error').is(":visible") ){
        alert($('#submit-error').html());     
        return;
    }

    labid=$('select[name="labid"]').val();
    practid=$('select[name="practid"]').val();

    date=$('#datepicker').val(); // no needed
    time=$('#timepicker ul#time-slots li.selected').html(); // delete

    // timestamp = $('#datepicker').val() + ' ' + time;
    // timestamp = timestamp.toUTCString();
    // timestamp = encodeURIComponent(timestamp);
    console.log(date + " " + time);

    url=$('#bookingform').attr('action')+"&labid="+labid+"&practid="+practid+
        "&date="+date+"&time="+time ;

    console.log(url);

    $.getJSON({
        method: 'POST',
        url:  url,
        dataType: "json",
        contentType: "application/json",
        success: function(data){
            console.log(data);
            if (data.exitCode >= 0 ){
                // mark as busy and choose skip to next slot
                cur = $('#timepicker ul#time-slots li.selected');
                cur.removeClass('slot-free').addClass('slot-busy');                    
                $('#timepicker #next_slot').click();

                $("#bookingform div#notif").attr('class','alert alert-success');
                update_mybookings_table(e.data.urlbase);
            } else {
                $("#bookingform div#notif").attr('class','alert alert-danger');
            }
                $("#bookingform div#notif").html(data.exitMsg);
        },
        error: function(xhr, desc, err){
            console.log(err);
            // display err
            $("#bookingform div#notif").html(err);
            $("#bookingform div#notif").attr('class','alert alert-danger');
        },
        complete: function (data){
            $("#bookingform div.alert").hide();
            $("#bookingform div#notif").show();   
        }
    });

}

function update_mybookings_table(controllerspath){
    id=getSearchParam('id');
    
    url=controllerspath+"/get_bookings.php?id="+id+'&labid='+$('select[name=labid]').val();
    console.log(url);
    
     $.getJSON({
          url:  url,
          success: function(data){
                
                $('#mybookings tbody').html(''); 
                
                for (var i=0; i < data['bookings-list'].length ; i++ ){
                    bk = data['bookings-list'][i];
                    url2=controllerspath+"/delete_booking.php?id="+id+"&bookid="+bk['id'];
                    
                    //dt = new Date(bk['timestamp']);
                    //opt1 = { year: '2-digit', month: '2-digit', day: '2-digit'};
                    //day = new Intl.DateTimeFormat( opt1 ).format(dt);
                    day = bk['day'];
                    
                    //day = dt.getFullYear()+"-"+
                    //     (( dt.getMonth() + 1 ).toString()).padStart(2, '0')+"-"+
                    //     (dt.getDate().toString()).padStart(2, '0');
                    
                    //starttime= dt.getHours().padStart(2, '0')+":"+dt.getMinutes().padStart(2, '0');
                    //opt2 = { hour: '2-digit', minute: '2-digit' };
                    //starttime = new Intl.DateTimeFormat('en-US', opt2).format(dt);
                    starttime = bk['time'];
                    //now.setMinutes(now.getMinutes() + 30);

                    line = '<tr>' +
                        '<td>' + ' &nbsp; '+'</td>' + // ( i + 1 ) 
                        '<td>' + day + '</td>' +         
                        '<td>' + bk['labname'] + '</td>' +
                        '<td>' + starttime + '</td>' +                        
                        '<td class="text-center del_btn_cell"><a href="' + url2 + '" class="del_btn">'+
                            '<span class="ui-icon ui-icon-trash" >&nbsp;</span>'+
                        '</a></td>' +
                    '</tr>';

                    $('#mybookings tbody').append(line);
                    
                }
              
                 $('#mybookings tbody a').click(function(e){
                        e.preventDefault();
                     
                        msg = $('#del-confirm').html();
                     
                        if ( ! confirm(msg) ){
                            return;
                        }
         
                        url = $(this).attr('href');
                        row = $(this).closest("tr");
                     
                        $.getJSON( url, function( data ) { 
                            console.log(url);
                            row.remove();
                            update_table_visibility();
                            init_table_pagination();
                        });
                 });
              
                if ( data['bookings-list'].length > 0 ){
                    init_table_pagination();
                }
              
                update_table_visibility();
              
            },
            error: function(xhr, desc, err){
                console.log(err);       
            }
        });
}

function update_table_visibility(){
    
    //alert($('#mybookings tbody tr').length );
    
    if ( $('table#mybookings > tbody > tr').length > 0 ){
        $('table#mybookings').show();
        $('#pagination').show();
        $('#mybookings_notif').hide();    
    }else{
        $('table#mybookings').hide();
        $('#pagination').hide();
        $('#mybookings_notif').show();
    }

}

function init_table_pagination(){
     // .disabled and .active .page-item
    
    // Remove previous
    while ( $('ul.pagination li').length > 2 ){
        $('ul.pagination li').first().next().remove();
    }
    
    // Add pages
    count = Math.ceil( $('#mybookings tbody tr').length / 10 );
    
    for (i = 1; i <= count; i++) {
        item = $('<li class="page-item"><a class="page-link">'+i+'</a></li>');
        $("ul.pagination li.page-item").last().prev().after(item);
    } 
    
    $('.pagination .page-item').click(function(e){
        e.preventDefault();
    
        items = $('ul.pagination li').length;
        old = $('.pagination .page-item.active');
        
        if ( $(this).index() == 0 ){ // First button (Previous)
            if ( old.index() == 1 ) {
               cur = $('ul.pagination li.page-item').last().prev();
            } else {
                cur = old.prev();
            }
        }else if ($(this).index() == items - 1 ){ // Last button (Next)
            if ( old.index() == items - 2 ){
                cur = $('ul.pagination li.page-item').first().next();
            } else {
                cur = old.next();
            }
        } else  { // Middle button (Direct)
           cur = $(this);
        }
        
        old.removeClass('active');
        cur.addClass('active');
        
        page = $('ul.pagination li.page-item.active').index() ;
        
        $('#mybookings tbody tr').hide();
        
        total = $('#mybookings tbody tr').length;
        
        first = ( page - 1 )*10 + 1;
        last = page*10;
        
        if ( $(this).index() == items - 2 ){
            last = total; 
        }
        
        for(i=first; i <= last; i++){
            $('#mybookings tbody tr:nth-child('+i+')').show();
        }
        
    });
    
    $("ul.pagination li.page-item:first-child").next().click(); // Select first page
    
}

function init_timepicker(){

        $('#timepicker #prev_slot').click(function(e){
            e.preventDefault();
            
            cur = $('#timepicker ul#time-slots li.selected');

            if (cur.index() > 0 ){ // not first

                cur.removeClass('selected').addClass('deselected');

                cur.prev().removeClass('deselected').addClass('selected');
            }
            // first -> last
            
            update_visible_time();
        });            

        $('#timepicker #next_slot').click(function(e){
            e.preventDefault();
            
            cur = $('#timepicker ul#time-slots li.selected');

            if (cur.index() < cur.siblings().length ){ // not last
                cur.removeClass('selected').addClass('deselected');
                cur.next().removeClass('deselected').addClass('selected');
            }
            //last -> first
            
            update_visible_time();
        });

        $('#timepicker button').on('mousedown', function(e){
             timeOut = setInterval(function(){ 
                   $(e.target).trigger("click");
                }, 200);
        });
    
        $('#timepicker button').on('mouseup', function() {
            if (timeOut){
                clearInterval(timeOut);
            }
        });
    
        $('#timepicker #next_hour').click(function(e){
            e.preventDefault();

            item = $('#timepicker ul#time-slots li.selected');
            last = item.parent().siblings().last();
            next = item.next();

            chour = item.html().split(":")[0];   
            cmin = item.html().split(":")[1];   
            
            nhour = next.html().split(":")[0];                    
            nmin = next.html().split(":")[1];   
            
            while (( next !== last ) && (( chour == nhour ) || (cmin != nmin ) )){
                
                next = next.next();
                
                if ( next !== last ){
                    nhour = next.html().split(":")[0];                    
                    nmin = next.html().split(":")[1];     
                }
            } 
            
            if ( next ){                
                item.removeClass('selected').addClass('deselected');
                next.removeClass('deselected').addClass('selected');
            }

            // first -> last
            
            update_visible_time();
        });  
    
            $('#timepicker #prev_hour').click(function(e){
                e.preventDefault();
                
                item = $('#timepicker ul#time-slots li.selected');
                prev = item.prev();

                // item.html undefined

                chour = item.html().split(":")[0];   
                cmin = item.html().split(":")[1];   
                first = false;

                if ( prev.length == 0 ){
                    first = true;
                }else {
                    phour = prev.html().split(":")[0];                    
                    pmin = prev.html().split(":")[1];        
                }

                while ( (! first ) && (( chour == phour ) || (cmin != pmin )) ){

                    prev = prev.prev();

                    if (prev.length == 0){
                        first = true;
                    } else {
                        phour = prev.html().split(":")[0];                    
                        pmin = prev.html().split(":")[1];                                   
                    }

                    //console.log(chour+":" + cmin+" "+phour+":"+pmin);

                } 

                if ( prev ){                
                    item.removeClass('selected').addClass('deselected');
                    prev.removeClass('deselected').addClass('selected');
                }

            // first -> last
            update_visible_time();
        });             

}

function on_lab_select(e) {
        id = getSearchParam('id');
        labid = $(this).val();
    
        url = e.data.urlbase+'/get_lab_info.php?'+'id='+id+'&labid='+labid;
    
        console.log(url);

        $.getJSON( url, function( data ) {

             $("select[name='practid']").children().remove();

             for (var p in data.practices ){
               pid = p[0];
               pname = data.practices[p[0]];
               opt = $('<option>', { 
                   value: pid,
                   text : pname
               });

               $("select[name='practid']").append(opt);
            };
            
           $('select[name=slot-size]').val(data['slot-size']);

           $('select[name=practid] option:first').attr('selected','selected');
           $("select[name='practid']").selectmenu("refresh");
            
            $('#bookingform div.alert').hide();

            if ( data.status == 0){ // innactice
                // display message
                $('#bookingform div.alert.inactive').show();

                // disable booking button
                $('#bookingform').off().on('submit', function(e){
                    e.preventDefault();
                    alert('This plant is not active at that moment.Unable to book.'); // TOFIX: translate
                });
            } else { // default behaviour, send form 
                
                    // display message
                $('#bookingform div.alert.inactive').hide();

                // re-enable booking button
                $('#bookingform').off().on('submit', { urlbase: e.data.urlbase }, on_submit_form);
                
            }
        
        });
            
}

function update_visible_time(){
    
    starttime = $('#timepicker li.selected').html();
   
    // update displayed time

     h = starttime.split(":")[0];
     m = starttime.split(":")[1];
        
    $('#timepicker label#h').html(h);
    $('#timepicker label#m').html(m);
    
    // update status 
    
    cur = $('#timepicker ul#time-slots li.selected');
    
    $('#bookingform div.alert').hide();
    
    if (cur.hasClass("slot-free")){
        $('#bookingform div.alert.slot-free').show();
    } else if (cur.hasClass("slot-busy")){
        $('#bookingform div.alert.slot-busy').show();
    } else if (cur.hasClass("slot-past")){
        $('#bookingform div.alert.slot-past').show();
    }
    
}
            
function on_day_select(e){

    id= getSearchParam('id');
    labid=$('select[name=labid]').val();
    selectDay = $(this).val();

    var options = { weekday: 'short',  month: 'short', day: 'numeric', year: 'numeric' };
    var date  = new Date(selectDay);
        date.setHours(0);
        date.setMinutes(0);
    var timestamp = date.toUTCString();
        timestamp = encodeURIComponent(timestamp);
    
    $("#current-date").html(date.toLocaleString("en-US", options));

    url2 = e.data.urlbase+'/get_time_slots.php?'+'id='+id+'&labid='+labid+
        '&date='+selectDay+ // non needed
        '&timestamp='+timestamp;
        
    console.log(url2);

    $.getJSON( url2, function( data ) { 
        
        var now = new Date();
        
        slot_list = $('<ul id="time-slots">');
        first_free= true;
        
        for (var i=0; i < data['time-slots'].length ; i++ ){

            ts = data['time-slots'][i]['timestamp'];
            status = data['time-slots'][i]['status'];
            
            dt = new Date(ts);
            end_time = new Date(dt);
                end_time.setMinutes(dt.getMinutes() + 30);
            now = new Date();
            
            if ( end_time < now ) { status = "past"; }
            
            time = (( dt.getHours()).toString()).padStart(2, '0')+":"+
                   (dt.getMinutes().toString()).padStart(2, '0');
                    
            item = $('<li>'+time+'</li>').addClass('slot deselected');  
            
            if (status == "past"){ // past
                item.addClass('slot-past');
            } else if (status == "busy"){ // busy
                item.addClass('slot-busy');
            } else {
                item.addClass('slot-free');

                if (first_free){ 
                    item.removeClass('deselected').addClass('selected');
                    first_free=false;
                }
            }
            slot_list.append(item);
        }
        
       $('#timepicker #slots ul').remove(); // remove previous list

       $('#timepicker #slots').append(slot_list);

        update_visible_time();

    });
}
            
function setSearchParam(param, newval) {
    var regex = new RegExp("([?;&])" + param + "[^&;]*[;&]?");
    var query = (window.location.search).replace(regex, "$1").replace(/&$/, '');
    var newsearch = (query.length > 2 ? query + "&" : "?") + (newval ? param + "=" + newval : '');
    window.location = window.location.pathname + newsearch;
}

function getSearchParam(param) {
    
    // param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regex = new RegExp( "[\\?&]"+param+"=([^&#]*)" );
    var results = regex.exec(window.location.search);
    
    if( results == null )
        return "";
    else
        return results[1];
}

