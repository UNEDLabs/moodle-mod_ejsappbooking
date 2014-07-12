/**
 * Created by U872275 on 7/11/13.
 */

M.ejsappbooking = {};

// Ejemplo llamada a función
M.ejsappbooking.hello = function (Y) {
    alert('Hello World!');
};

YUI({lang: "es,en"}).use("node-base", "datatype-number", "event-base", "overlay", "event-mouseenter",  "calendar", "panel", "io-base", "json-parse", function (Y) {

    // Creamos un panel para pedir confirmación de la eliminación de la reserva
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

    // Comportamiento al pulsar cancelar
    dialog.onCancel = function (e) {
        e.preventDefault();
        this.hide();
        // the callback is not executed, and is
        // callback reference removed, so it won't persist
        this.callback = false;
    }

    // Comportamiento al pulsar ok
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

    // Acción al pulsar ok, instancia Mybookinsdelete a uno para que se vuelvan a mostrar las reservas del usuario
    var doSomething = function () {

        Y.one('#Mybookingsdelete').set("value", "1");
        var form = Y.one('#bookingform');
        form.submit();

    };

    // Capturamos el botón
    var btn = Y.one('.btn-show');

    // Si es distinto de null mostramos el mensaje, preguntamos por la confirmación y ejecutamos la acción
    if(btn != null) {
        btn.on('click', function (ev) {
            // set the content you want in the message
            Y.one('#dialog .message').setHTML(M.str.ejsappbooking.messageDelete + '?');

            // set the icon (or none) that appears next to the message.
            // the Class 'message' also needs to be maintained.
            Y.one('#dialog .message').set('className', 'message icon-bubble');
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

    // Variable utilizada para guardar la fecha actual seleccionada
    var fechaActual;

    // Array que almacena la llamada a JSON con las reservas del mes en el laboratorio para marcarlas en el calendario
    var reservas = new Array();

    // Cuando la fecha seleccionada esta disponible en la página
    Y.on("available", function (ev) {

        // Tomamos el valor de la fecha seleccionada
        var fecha = Y.one('#fechaActiva');
        fechaActual = Y.Date.parse(fecha.getHTML());
        var dateActual = "dateActual=" + Y.DataType.Date.format(fechaActual, { format: '%Y-%m-%d' });

        // LLamada a bookings.php que devuelve un objeto JSON con las reservas del laboratorio para el mes seleccionado
        Y.io('bookings.php', {
            data: ' ' + dateActual + ' ',
            dataType: 'json',
            on: {
                complete: function (id, response) {
                    if(response.status >= 200 && response.status < 300) {
                        //Y.log('Llamada JSON');

                        var data;

                        try {
                            //Y.log("RESPONSE: " + response.responseText);
                            data = Y.JSON.parse(response.responseText);

                        } catch(e) {
                            Y.log('Invalid JSON' + e);
                        }

                        // Recorremos la reservas y preparamos el mensaje emergente
                        for (var p in data) {

                            var mn_array = data[p].split(',');

                            var mdate = Y.Date.parse(mn_array[2]);

                            var d = Y.DataType.Date.format(mdate, { format: '%e' })

                            if(!reservas[d]) {
                                reservas[d] = d + "@" + "<tr><td>" + mn_array[1] + "</td><td>" + mn_array[3] + "</td></tr>";

                            }
                            else {
                                reservas[d] = reservas[d] + "<tr><td>" + mn_array[1] + "</td><td>" + mn_array[3] + "</td></tr>";
                            }
                        }

                        // Recorrer todos los dias del año para marcar si el dia tiene reservas
                        var reglas = {"all": {"all":{"all" : "booking"}}};

                        // Aplicamos al calendario una función que modifica la celda con reserva
                        calendar.set('customRenderer', { rules: reglas, filterFunction: miFuncionRender});
                        calendar.render();
                        Y.log('Fin JSON');
                    }
                    else {
                        Y.log('Fallo llamada JSON');
                    }

                }

            }

        });

        // Modificamos el calendario para que nos muestre la fecha actual seleccionada
        calendar.set("date", fechaActual);
        calendar.selectDates(fechaActual);
        calendar.render();

    }, '#fechaActiva');


    //Funcion de renderizado
    function miFuncionRender( fecha, nodo, reglas ){
        var s = Y.DataType.Date.format(fecha, { format: '%e' });

        // Si el dia esta en el array de reservas lo marca
        if(reservas[s]) {
            nodo.setStyles( { borderColor: '#88F', borderWidth: '5px'});
        }
    };

    // Establecemos las caracteristicas del calendario
    var calendar, settings = {
        contentBox: "#calendario",
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

    // Aplicamos las caracteristicas
    calendar = new Y.Calendar(settings);

    // Controlamos las acciones al pulsar sobre un dia del calendario
    calendar.on('dateClick', function (ev) {
        var fecha = ev.date;
        var nodo = ev.cell;

        // Establecemos la nueva fecha en la pagina
        Y.one('#fechaActiva').setHTML(YDate.format(fecha, { format: '%Y-%m-%d'}));
        fecha = Y.DataType.Date.format(fecha, { format: '%Y-%m-%d' });

        // Establecemos la nueva fecha como parametro de la solicitud
        Y.one('#selectdate').set("value", fecha);

        // Enviamos la nueva petición al servidor
        var form = Y.one('#bookingform');
        form.submit();
    });

    // Botones selección mes y año que controlan el calendario
    Y.on('click', function () {
        calendar.subtractYear();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#restarAno');
    Y.on('click', function () {
        calendar.subtractMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#restarMes');
    Y.on('click', function () {
        calendar.addYear();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#sumarAno');
    Y.on('click', function () {
        calendar.addMonth();
        var date = calendar.get('date');
        updateCalendar(date);
    }, '#sumarMes');

    // Funciones que controlan el comportamiento al pulsar en un dia del mes anterior o posterior al actual sobre el calendario
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

    // Función que actualiza la información del calendario y la página.
    function updateCalendar(fecha) {
        // Establece las nueva fecha
        Y.one('#fechaActiva').setHTML(YDate.format(fecha, { format: '%Y-%m-%d'}));
        fecha = Y.DataType.Date.format(fecha, { format: '%Y-%m-%d' });
        Y.one('#selectdate').set("value", fecha);

        // Envia los datos al servidor
        var form = Y.one('#bookingform');
        form.submit();

    };

    // Función que controla el cambio de estado del slot en la tabla de slots disponibles, al seleccionarlo
    Y.one('#tablabooking').on('click', function (e) {

        var clave = e.target.get('value');
        var source2 = Y.one('#bookimg' + clave);

        if (source2) {
            var source = source2.get("src");
            var n = source.search("seleccionada.png");

            if (n == -1) {
                var res = source.replace("disponible.png", "seleccionada.png");
                Y.one('#bookimg' + clave).set("src", res);

            }
            else {
                var res = source.replace("seleccionada.png", "disponible.png");
                Y.one('#bookimg' + clave).set("src", res);
            }
        }

    }, 'input[type=checkbox]');


    // Creamos un tooltip para mostrar las reservas del dia seleccionado
    var tooltip = new Y.Overlay({width: 250, visible: false, zIndex:1000});

    // Función que se ejecuta al entrar el ratón sobre la celda de un dia del calendario
    function enter(ev) {
        // Nodo DOM actual
        var node = ev.currentTarget;
        // Calculamos la posición donde debe mostrarse el tooltip
        tooltip.align(node, [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BC]);

        // Establecemos el contenido del tooltip
        for (var p in reservas) {

            var mn_array = reservas[p].split('@');

            // Se muestra al usuario si existen reservas en ese dia
            if(mn_array[0].trim() == node.getHTML()) {
                tooltip.set('headerContent','<div style="background-color:blue;font-weight:bolder;color:white;padding:10px;border: solid 1px black;">' + M.str.ejsappbooking.book_message + '</div>');
                tooltip.set('bodyContent', '<div style="background-color:white;padding: 10px;border: solid 1px black;"><table>' + mn_array[1] + '</table></div>');
                //tooltip.set('footerContent', 'Pie');
                tooltip.show();
            }
        }

    }

    // Función que se ejecuta al dejar el foco de un celda del calendario
    function leave(ev) {
        tooltip.hide();
    }

    // Establecemos las funciones a las celdas del calendario
    Y.delegate('mouseenter', enter, '#calendario', 'td');
    Y.delegate('mouseleave', leave, '#calendario', 'td');

    // Creamos el tooltip inicia que permanece oculto
    tooltip.render();

    // Pintamos el calendario inicial que se actualiza al estar disponible fechaActiva en la página.
    calendar.render();

});

