define(['jquery','jqueryui'], function($){

    var table = function myBookingsTable(debug){
        this.debug = debug;
        this.log('Creating object');
        this.elem = $('table#mybookings');
    };
    
    table.prototype.log = function(msg){
        if (this.debug){
            console.log('[TABLE] ' + msg);
        }
    };
    
    table.prototype.populate = function( data ){
        
        this.log('Clearing');
        this.elem.children('tbody').html(''); 
    
        this.log('Populating');

        for (var i=0; i < data.bookings.length ; i++ ){
            bk = data.bookings[i];
            //delete_url=data.controllerspath + "/delete_booking.php?id="+data.course_id+"&bookid="+bk['id'];    
            line = '<tr>' +
                        '<td>' + ' &nbsp; '+'</td>' + 
                        '<td>' + bk['day'] + '</td>' +         
                        '<td>' + bk['labname'] + '</td>' +
                        '<td>' + bk['time'] + '</td>' +                        
                        '<td class="text-center del_btn_cell" id="'+bk['id']+'">'+
                           '<a class="del_btn"><span class="ui-icon ui-icon-trash" >&nbsp;</span></a></td>' +
                    '</tr>';

             this.elem.find('tbody').append(line);
        }

        this.update_visibility();
        
        if ( data.bookings.length > 0 ){
            this.paginate(); // TOOO
        }

     };
    
    table.prototype.update_visibility = function(){
        
        var size = this.elem.find('tbody > tr').length;
                     
        this.log('Updating visibility ('+size+')');

        if ( size > 0 ){
            this.log('contain items');
            this.elem.show();
            $('#pagination').show();
            $('#mybookings_notif').hide();
        } else {
            this.log('empty');
            this.elem.hide();
            $('#pagination').hide();
            $('#mybookings_notif').show();
        }
        
    };
    
    table.prototype.paginate = function(){ // .disabled and .active .page-item

        this.log('Updating pagination');

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

        // Update links
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

            // Update visibility
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

        // Select first page
        $("ul.pagination li.page-item:first-child").next().click(); 

    };
    
    table.prototype.on_delete_item_setup = function (data){
        
        data['mybookings_table'] =  this ;
        
        this.elem.find('tbody a').on('click', data , this.on_delete_item );
    }
 
    table.prototype.on_delete_item = function(e){
        console.log('delete <EVENT>');
        e.preventDefault();

        var msg = $('#del-confirm').html();
        if ( ! confirm(msg) ){  return; }

        //var delete_url = $(this).attr('href');
        var booking_id=$(this).parent().attr('id');
        var delete_url=e.data.controllerspath + "/delete_booking.php?id="+e.data.course_id+"&bookid="+booking_id;  
        
        var btn = $(this);
        var bookings_table = this;
        
        $.getJSON( delete_url, function( data ) { //success
            bookings_talbe.log('GET ' + delete_url);
            btn.closest("tr").remove();
            
            e.data.mybookings_table.update_visibility();
            e.data.mybookings_table.paginate();
        });

    };
      
    return table;

});
    