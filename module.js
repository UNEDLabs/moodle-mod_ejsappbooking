
// This file is part of the Moodle module "EJSApp booking system"
//
// EJSApp booking system is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// EJSApp booking system is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License is available on <http://www.gnu.org/licenses/>
//
// EJSApp booking system has been developed by:
//  - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain

/**
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Example
M.ejsappbooking = {};
M.ejsappbooking.hello = function (Y) {
    alert('Hello World!');
};

YUI.GlobalConfig =  { lang: '' + Y.one("html").get("lang") + ''};
// Start
YUI().use("node-base", "datatype-number", "event-base", "overlay", "event-mouseenter",  "calendar", "panel", "io-base", "json-parse", function (Y) {

    // confirm reservation
    var dialog = new Y.Panel({

        contentBox: Y.Node.create('<div id="dialog" />'),
        bodyContent: '<div class="message icon-warn">' + M.str.ejsappbooking.messageDelete + '</div>',
        width: 410,
        zIndex: 6,
        centered: true,
        modal: false, // modal behavior
        render: '.example',
        visible: false, // make visible explicitly with .show()
        buttons: {
            footer: [
                {
                    name: 'cancel',
                    label: M.str.ejsappbooking.cancel,
                    action: 'onCancel'
                },

                {
                    name: 'proceed',
                    label: 'OK',
                    action: 'onOK'
                }
            ]
        }
    })

    // press cancel
    dialog.onCancel = function (e) {
        e.preventDefault();
        this.hide();
        // the callback is not executed, and is
        // callback reference removed, so it won't persist
        this.callback = false;
    }

    // press ok
    dialog.onOK = function (e) {
        e.preventDefault();
        this.hide();
        // code that executes the user confirmed action goes here
        if (this.callback) {
            this.callback();
        }
        // callback reference removed, so it won't persist
        this.callback = false;
    }

    //  re-display the user reservation
    var doSomething = function () {

        Y.one('#Mybookingsdelete').set("value", "1");
        var form = Y.one('#bookingform');
        form.submit();

    };

    var btn = Y.one('.btn-show');

    if(btn != null) {
        btn.on('click', function (ev) {
            // set the content you want in the message
            Y.one('#dialog .message').setHTML(M.str.ejsappbooking.messageDelete);

            // set the icon (or none) that appears next to the message.
            // the Class 'message' also needs to be maintained.
            Y.one('#dialog .message').set('className', 'message icon-none');
            /* classnames and images provided in the CSS are:
             .icon-bubble
             .icon-error
             .icon-info
             .icon-question
             .icon-warn
             .icon-success
             .icon-none
             */

            // set the callback to reference a function
            dialog.callback = doSomething;

            dialog.show();
        });
    }

    // selected date
    var currentDate;

    // My bookings array
    var myBookings = new Array();

    // selected date available
    Y.on("available", function (ev) {

        var fecha = Y.one('#ActiveDate');
        currentDate = Y.Date.parse(fecha.getHTML());
        var dateActual = "dateActual=" + Y.DataType.Date.format(currentDate, { format: '%Y-%m-%d' });

        //  My bookings JSON call
        Y.io('bookings.php', {
            data: ' ' + dateActual + ' ',
            dataType: 'json',
            on: {
                complete: function (id, response) {
                    if(response.status >= 200 && response.status < 300) {

                        var data;

                        try {

                            data = Y.JSON.parse(response.responseText);

                        } catch(e) {
                            Y.log('Invalid JSON' + e);
                        }

                        // My bookings popup message
                        for (var p in data) {

                            var mn_array = data[p].split(',');

                            var mdate = Y.Date.parse(mn_array[2]);

                            var d = Y.DataType.Date.format(mdate, { format: '%e' })

                            if(!myBookings[d]) {
                                myBookings[d] = d + "@" + "<tr><td>" + mn_array[1] + "</td><td>" + mn_array[3] + "</td></tr>";

                            }
                            else {
                                myBookings[d] = myBookings[d] + "<tr><td>" + mn_array[1] + "</td><td>" + mn_array[3] + "</td></tr>";
                            }
                        }

                        // Config calendar
                        var rule = {"all": {"all":{"all" : "booking"}}};

                        // Cell custom render
                        calendar.set('customRenderer', { rules: rule, filterFunction: miFuncionRender});
                        calendar.render();
                        Y.log('Fin JSON');
                    }
                    else {
                        Y.log('Fallo llamada JSON');
                    }

                }

            }

        });

        // to change to new selected day
        calendar.set("date", currentDate);
        calendar.selectDates(currentDate);
        calendar.render();

    }, '#ActiveDate');


    // rendering function
    function miFuncionRender( fecha, nodo, reglas ){
        var s = Y.DataType.Date.format(fecha, { format: '%e' });

        // mark the days in the calendar
        if(myBookings[s]) {
            nodo.setStyles( { borderColor: '#88F', borderWidth: '5px'});
        }
    };

    // calendar settings
    var calendar, settings = {
        contentBox: "#calendar",
        //headerRenderer: "%d %B %Y",
        headerRenderer: "%B %Y",
        //height: '232px',
        //width: '232px',
        showPreMonth: true,
        showNextMonth: true,
        selectedDay: true,
        minimumDate: new Date()
    };

    var YDate = Y.DataType.Date;

    // Apply settings
    calendar = new Y.Calendar(settings);

    // calendar actions
    calendar.on('dateClick', function (ev) {
        var fecha = ev.date;
        var nodo = ev.cell;

        // new Date
        Y.one('#ActiveDate').setHTML(YDate.format(fecha, { format: '%Y-%m-%d'}));
        fecha = Y.DataType.Date.format(fecha, { format: '%Y-%m-%d' });

        // form hidden parameter
        Y.one('#selectdate').set("value", fecha);

        // submit the form
        var form = Y.one('#bookingform');
        form.submit();
    });

    // Month and year selection buttons that control the calendar
    Y.on('click', function () {
        calendar.subtractYear();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#subyear');
    Y.on('click', function () {
        calendar.subtractMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#submonth');
    Y.on('click', function () {
        calendar.addYear();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#addyear');
    Y.on('click', function () {
        calendar.addMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#addmonth');

    //
    calendar.on('prevMonthClick', function () {
        calendar.subtractMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    });
    calendar.on('nextMonthClick', function () {
        calendar.addMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    });

    // Functions that control the behavior of the press in a day before or after the current month on the calendar
    function updateCalendar(fecha) {
        // Sets new date
        Y.one('#ActiveDate').setHTML(YDate.format(fecha, { format: '%Y-%m-%d'}));
        fecha = Y.DataType.Date.format(fecha, { format: '%Y-%m-%d' });
        Y.one('#selectdate').set("value", fecha);

        // Send data to the server
        var form = Y.one('#bookingform');
        form.submit();

    };

    // Function that controls the state change slot in the table slots available at select
    Y.one('#tablabooking').on('click', function (e) {

        var clave = e.target.get('value');
        var source2 = Y.one('#bookimg' + clave);

        if (source2) {
            var source = source2.get("src");
            var n = source.search("selected.png");

            if (n == -1) {
                var res = source.replace("available.png", "selected.png");
                Y.one('#bookimg' + clave).set("src", res);

            }
            else {
                var res = source.replace("selected.png", "available.png");
                Y.one('#bookimg' + clave).set("src", res);
            }
        }

    }, 'input[type=checkbox]');


    // create a tooltip to display the bookings
    var tooltip = new Y.Overlay({width: 250, visible: false, zIndex:1000});

    // Function that is run when the mouse over the cell of a calendar day
    function enter(ev) {
        // current node
        var node = ev.currentTarget;
        // calculate the position where the tooltip should be displayed
        tooltip.align(node, [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BC]);

        // establish the content of the tooltip
        for (var p in myBookings) {

            var mn_array = myBookings[p].split('@');

            // show my bookings
            if(mn_array[0].trim() == node.getHTML()) {
                tooltip.set('headerContent','<div style="background-color:blue;font-weight:bolder;color:white;padding:10px;border: solid 1px black;">' + M.str.ejsappbooking.book_message + '</div>');
                tooltip.set('bodyContent', '<div style="background-color:white;padding: 10px;border: solid 1px black;"><table>' + mn_array[1] + '</table></div>');
                //tooltip.set('footerContent', 'Pie');
                tooltip.show();
            }
        }

    }

    // Function running to leave the focus of a calendar cell
    function leave(ev) {
        tooltip.hide();
    }

    // establish the functions calendar cells
    Y.delegate('mouseenter', enter, '#calendar', 'td');
    Y.delegate('mouseleave', leave, '#calendar', 'td');

    // Create the initial tooltip that remains hidden
    tooltip.render();

    // Create the initial calendar
    calendar.render();

});

