define(['jquery','jqueryui'], function($){

    var table = function myBookingsTable(data){
        this.debug = data.debug;
        this.delete_base_url = data.controllerspath + "/delete_booking.php?id="+data.course_id;
        this.elem = $('table#mybookings');
        
        this.datepicker = data.datepicker;
        this.timepicker = data.timepicker;
        
        this.log('Creating object');
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
            
            var line = this.create_row(bk['day'], bk['labname'], bk['time'], bk['id']);
            this.append_item(line);
            
        }

        this.update_visibility();
        
        if ( data.bookings.length > 0 ){
            this.paginate(); // TOOO
        }

     };
    
    table.prototype.append_item = function (row){
         this.elem.find('tbody').append(row);
    }
    
    table.prototype.create_row = function(day,labname,starttime,bookid) {
        var line = $('<tr>' +
            '<td>' + ' &nbsp; '+'</td>' + 
            '<td>' + day + '</td>' +         
            '<td>' + labname + '</td>' +
            '<td>' + starttime + '</td>' +                        
            '<td class="text-center del_btn_cell" id="'+bookid+'">'+
               '<a class="del_btn"><span class="ui-icon ui-icon-trash" >&nbsp;</span></a></td>' +
        '</tr>');
        
        line.find('a').on('click', { mybookings: this, timepicker: this.timepicker, datepicker: this.datepicker, bookid: bookid }, this.on_delete_item );
        
        return line;
    }
    
    table.prototype.update = function(day,labname,starttime,bookid){
        var line = this.create_row(day,labname,starttime,bookid);
        
        this.add_sorted(day, starttime, line);
        
        this.update_visibility();
    }
    
    table.prototype.add_sorted = function(day, starttime, line){
        
        // #, day, labname, startime, booking;
        
        //var item = this.elem.find('tbody').children().first();
        
        var date1 = new Date(day + " " + starttime);
        
        if ( this.elem.find('tbody').children().length == 0){
            this.elem.find('tbody').html(line);
            return;
        }
        
        this.elem.find('tbody > tr').each( function(){
          
           var d2 =  $(this).find('td:nth-child(2)').text();
           var t2 = $(this).find('td:nth-child(4)').text();
            
           var date2 = new Date( d2 + " " + t2);
            if ( date1 < date2 ) {
                 $(this).before(line);
                 return false;
            } else if ( $(this).is(':last-child')){
                 $(this).after(line);
            }
            
        });
        
    }
    
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
    
    /*
    
    table.prototype.on_delete_item_setup = function (data){
        
        data['mybookings'] =  this ;
        
        this.elem.find('tbody a').on('click', data , this.on_delete_item );
    };
    
    */
    table.prototype.on_delete_item = function(e){
        
        var mybookings = e.data.mybookings;
        var dpicker = e.data.datepicker;
        var tpicker = e.data.timepicker;
        
        mybookings.log('delete <EVENT>');
        
        e.preventDefault();

        var msg = $('#del-confirm').html();
        if ( ! confirm(msg) ){  return; }
    
        var delete_url = e.data.mybookings.delete_base_url + "&bookid=" + e.data.bookid;
        
        var btn = $(this);
        
        $.getJSON( delete_url, function( data ) { //success
            mybookings.log('GET ' + delete_url);
            var row = btn.closest("tr");
            
            var day = row.find('td:nth-child(2)').text();
            var time = row.find('td:nth-child(4)').text();

            row.remove();
            
            dpicker.delete_booking(day,time); 
            dpicker.refresh();
            
            if ( day == dpicker.get() ){
                tpicker.del_busy(time);
                tpicker.update_busy_interv();
            }
            
            mybookings.update_visibility();
            mybookings.paginate();
        });

    };
      
    return table;

});
    