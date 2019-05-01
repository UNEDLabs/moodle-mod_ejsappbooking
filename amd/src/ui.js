define(['jquery', 'jqueryui'], function($) {
    return {
        init: function(controllerspath) {
		// alert("loading amd module")
            
        console.log("Initializing dropdown selects");
                
        $('select').selectmenu();
            
        // init lab selector
        $('select[name=labid]').on('selectmenuchange', { urlbase: controllerspath }, on_lab_select );
            
        console.log('Selecting first lab');
        firstlab = $('select[name=labid] option:first').val();
        
        $('select[name=labid]').val(firstlab)
            .selectmenu("refresh")
            .trigger("selectmenuchange");
            
        console.log('Initializing submit form');
        $('#bookingform').on('submit',  { urlbase: controllerspath }, on_submit_form);
            
/*
        console.log('Initializing datepicker');
        var today = new Date(); 
        var current = new Date(getSearchParam('selectDay'));
        
        $('div#datepicker').datepicker({
            altField: "#date",
            changeMonth: false,
            changeYear: false,
            dateFormat: 'yy-mm-dd',
            defaultDate: current,
            firstDay: 1,
            gotoCurrent: true,
            minDate: today,
            numberOfMonths: [ 1, 1 ],
            showOtherMonths: true,
            selectOtherMonths: true
        });
            
        $('#datepicker').on('change', { urlbase: controllerspath }, on_date_select);
                      
        console.log('Initializing timepicker');
        $('#timepicker table#hour td').on('click', function(){
            clear_selected_time();
            $(this).addClass('time-highlight time-current');
        });
        
        console.log('Selecting current day');            
        $('#datepicker .ui-datepicker-current-day').click();  
        
        console.log('Initializing mybookings table');
        update_mybookings_table(controllerspath);
*/
 
            url=controllerspath+"/get_mybookings.php?id="+getSearchParam('id')+'&labid='+$('select[name=labid]').val();
            console.log(url);

             $.getJSON({
                 url: url,
                 success: function(data){
                     
                    console.log(data['bookings-list']);

                    init_datepicker(controllerspath, data['bookings-list']);

                    // init_timepicker();
                     
                    console.log('Initializing timepicker');
                    $('#timepicker table#hour td').on('click', function(){
                        clear_selected_time();
                        $(this).addClass('time-highlight time-current');
                    });

                    update_mybookings_table(controllerspath, data['bookings-list']);
                     
                  },
                 error: function(xhr, desc, err){ //console.log(err);
                    console.log('Error getting bookings list.');
                }
             });             
            
            
        }
    };
});

function init_datepicker(controllerspath, bookings ){
    
    console.log('Initializing datepicker');
    
    var today = new Date(); 
    var current = new Date(getSearchParam('selectDay'));

    //created asociative array
    
    // var dates_times = new Array();
    var dates_times = [];
    
    if (( bookings != null ) && (bookings.length > 0)){
        
        for(var i = 0; i< bookings.length; i++){
            bk = bookings[i];
            d = bk['day'];
            t = bk['time'];
            // l = Object.keys.length;
            
            if ( dates_times[d] == null ){
                dates_times[d] =  t ;
            } else {
                dates_times[d] = dates_times[d] + " &#013;&#10; " + t ; 
            }
        }
    }

    $('div#datepicker').datepicker({	
        dateFormat: 'yy-mm-dd',
        changeMonth: false,
        changeYear: false,
        gotoCurrent: true,
        firstDay: 1,
        minDate: today,
        defaultDate: current,
        numberOfMonths: [ 1, 1 ],
        altField: "#date",
        showOtherMonths: true,
        selectOtherMonths: true,
        beforeShowDay: function(d){
            
            //var month=((d.getMonth()+1)>=10)? (d.getMonth()+1) : '0' + (d.getMonth()+1); 
            //var day = ((d.getDate())>=10)? (d.getDate()) : '0' + (d.getDate());
            //var date = d.getFullYear() + "-" + month + "-" + day ; 
            
            var date = $.datepicker.formatDate('yy-mm-dd', d);
            
            if ( dates_times.hasOwnProperty(date)){ // console.log('Highlight' + date);
                console.log('busy day '+ date);
                return [true, 'highlight-day',dates_times[date]];
            }else { // console.log('skip');
                return [true,'',''];
            }
       }

    }); 

    $('#datepicker').on('change',  { urlbase: controllerspath }, on_date_select);
    
    $('#datepicker .ui-datepicker-current-day').click(); // select current day  
}




function clear_selected_time(){
    $('#timepicker table#hour td').removeClass('time-highlight time-current');
}

function get_current_hour(){
    
    var t = $('#timepicker table#hour .time-current').text();
    if ( t == ''){ return null }
    
    t = t.substr(0, t.length - 2);
    period = t.substring(t.length - 2);
    
    t = parseInt(t);
    
    if ( period == 'PM'){
        t = ( t + 12 ) % 24 ;
    }
    
    if (t < 10) {
        t = '0' + t.toString();
    } else {
        t = t.toString();
    }

    return t;
}

function get_current_time(){
    return get_current_hour() + $('#timepicker table#interval .interval-current').text();
}

function on_lab_select(e) {
        id = getSearchParam('id');
        labid = $(this).val();
    
        url = e.data.urlbase+'/get_lab_info.php?'+'id='+id+'&labid='+labid;
    
        console.log(url);

        $.getJSON( url, function( data ) {
            
            console.log('Updating practice select');
            $("select[name='practid']").children().remove();

            for (var p in data.practices ){
               pid = p[0];
               pname = data.practices[p[0]];
               opt = $('<option>', { value: pid, text : pname });
               $("select[name='practid']").append(opt);
            };
            
            $('select[name=slot-size]').val(data['slot-size']);
            $('select[name=practid] option:first').attr('selected','selected');
            $("select[name='practid']").selectmenu("refresh");
            
            
            update_timepicker_interval(data['slot-size']);
            
            // update alerts visibility
            $('#bookingform div.alert').hide();

            if ( data.status == 0){ // practice inactive
                 
                $('#bookingform div.alert.inactive').show(); // display message

                $('#bookingform').off().on('submit', function(e){ // disable booking button
                    e.preventDefault();
                    alert('This plant is not active at that moment.Unable to book.'); // TOFIX: translate
                });
                
                return;
            }
            
            // default behaviour, send form 
            $('#bookingform').off().on('submit', { urlbase: e.data.urlbase }, on_submit_form);
        
        });
            
}

function update_timepicker_interval(slot_size){

    console.log('Updating time interval picker');
    
    // delete previous items
    $('#timepicker table#interval td').remove(); // .not(':first')
    
    // insert first items
    $('#timepicker table#interval tr').append('<td>:00</td>');
    
    // insert intervals adding slot_size to the previous one
    period = slot_size;
    
    while ( period < 60 ) {
        label = period;
        if ( period < 10 ) label = '0' + label;
        cell = '<td>:' + label + '</td>';
        $('#timepicker table#interval tr').append(cell);
        period += slot_size;
    } 
    n = $('#timepicker table#interval td').length;
    
    console.log( 'Updating table overflow (' + n +  ')');
    
    if ( n > 8 ){
        $('#timepicker table#interval').css('overflow-x', 'scroll');
    } else {
        $('#timepicker table#interval').css('overflow-x', 'hidden');
    }
}
         
function on_date_select(e){
    
    console.log('On date select');
    
    clear_selected_time();
    
    id= getSearchParam('id');
    labid=$('select[name=labid]').val();
    selectDay = $(this).val();

    url2 = e.data.urlbase+'/get_time_slots.php?'+'id='+id+'&labid='+labid+'&date='+selectDay;     
    console.log(url2);

    $.getJSON( url2, function( data ) { 
        
       $('#timepicker table#hour td').on('click',{ bslots: data['busy-slots']}, on_time_select );
        
    });
    
}

function on_time_select(e){
    
    // disable busy slots
     h = get_current_hour();
    
    console.log('On time select ('+h +')');
    
    $('#timepicker table#interval td').each( function( index, item ){
        
        time = h + $(this).text();
         
        if ( e.data.bslots.includes(time) ) { // busy, disable
            $(this).addClass('interval-busy');
            console.log('disabling '+time);
        }else {
            $(this).click(function(e){ // free, enable
                $('#timepicker table#interval td').removeClass('time-highlight interval-current');
                $(this).addClass('time-highlight interval-current');
            });
        }

     });
     
     // focus table#interval after selecting an hour
     //$('#timepicker table#interval td').first().click(); 
}

function on_submit_form(e){
    
    console.log('On submit form');
    
    e.preventDefault();
    
    if (time == null){
        console.log('error');
        alert('debe seleccionar una hora');
        return;
    }
    
    if ( $('#bookingform div.alert.error').is(":visible") ){
        alert($('#submit-error').html());     
        return;
    }

    labid=$('select[name="labid"]').val();
    practid=$('select[name="practid"]').val();
    date=$('#datepicker').val();
    time=get_current_time();

    url=$('#bookingform').attr('action')+"&labid="+labid+"&practid="+practid+"&date="+date+"&time="+time ;

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

function update_mybookings_table(controllerspath, bookings){
    
    console.log('Updating mybookings table ' . bookings);
    
    $('#mybookings tbody').html(''); 

    for (var i=0; i < bookings.length ; i++ ){
        bk = bookings[i];
        delete_url=controllerspath + "/delete_booking.php?id="+id+"&bookid="+bk['id'];

        line = '<tr>' +
                '<td>' + ' &nbsp; '+'</td>' + 
                '<td>' + bk['day'] + '</td>' +         
                '<td>' + bk['labname'] + '</td>' +
                '<td>' + bk['time'] + '</td>' +                        
                '<td class="text-center del_btn_cell"><a href="' + delete_url + '" class="del_btn">'+
                    '<span class="ui-icon ui-icon-trash" >&nbsp;</span>'+
                '</a></td>' +
            '</tr>';

        $('#mybookings tbody').append(line);
    }

    $('#mybookings tbody a').on('click', on_delete_mybookings_table_item );

    if ( bookings.length > 0 ){
        init_table_pagination();
    }
    
    update_table_visibility();
}

function init_table_pagination(){ // .disabled and .active .page-item
    
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

function on_delete_mybookings_table_item(e){
    
    console.log('Deleting booking');
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
}

function update_table_visibility(){
    
    if ( $('table#mybookings > tbody > tr').length > 0 ){
        $('table#mybookings').show();
        $('#pagination').show();
        $('#mybookings_notif').hide();
        console.log('Mybookings table contain items');
    }else{
        $('table#mybookings').hide();
        $('#pagination').hide();
        $('#mybookings_notif').show();
        
        console.log('Mybookings table is empty');
    }

}

/*


function update_mybookings_table(controllerspath){
    id=getSearchParam('id');
    
    url=controllerspath+"/get_mybookings.php?id="+id+'&labid='+$('select[name=labid]').val();
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

*/

/*
function update_table_visibility(){
    
    if ( $('table#mybookings > tbody > tr').length > 0 ){
        $('table#mybookings').show();
        $('#pagination').show();
        $('#mybookings_notif').hide();    
    } else {
        $('table#mybookings').hide();
        $('#pagination').hide();
        $('#mybookings_notif').show();
    }

}
*/

/*
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
*/

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
    
    if( results == null ){
        return "";
    } else {
        return results[1];
    }
}