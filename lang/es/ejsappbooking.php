<?php
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
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// EJSApp booking system has been developed by:
// - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
// - Luis de la Torre: ldelatorre@dia.uned.es
// - Ruben Heradio: rheradio@issi.uned.es
//
// at the Computer Science and Automatic Control, Spanish Open University
// (UNED), Madrid, Spain.

/**
 * English strings for ejsappbooking
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Sistema de reservas EJSApp';
$string['modulenameplural'] = 'Sistemas de reservas EJSApp';
$string['modulename_help'] = 'El m&oacute;dulo de recurso EJSAppBooking permite a los usuarios de Moodle reservas franjas de tiempo para experimentaci&oacute;n real y remota usando los applets creados con Easy Java Simulations (EJS) y subidos a los cursos de Moodle mediante el m&oacute;dulo de actividad EJSApp.

Este recurso a&ntilde;ade una aplicaci&oacute;n Java que muestra una lista de los laboratorios remotos disponibles para el cuser y le permite seleccionar una reserva para cualquier d&iacute;a y hora deseados.

El sistema de reservas consiste en dos partes: el cliente de reservas y el servidor de reservas. Mientras que la aplicaci&oacute;n del cliente de reservas se a&ntilde;ade con este m&oacute;dulo, el servidor de reservas necesita estar en ejecuci&oacute;n en el servidor que aloja el portal de Moodle. Puedes encontrar esta aplicaci&oacute;n en tu carpeta /mod/ejsappbooking/applets/BookingServer/';
$string['view_error'] = 'No se pudo abrir la aplicaci&oacute;n del sistema de reservas.';
$string['ejsappbookingname'] = 'Nombre del sistema de reservas EJSApp';
$string['ejsappbookingname_help'] = 'Nombre a mostrar para el sistema de reservas EJSApp en tu curso de Moodle.';
$string['ejsappbooking'] = 'EJSAppBooking';
$string['pluginadministration'] = 'Administraci&oacute;n de EJSAppBooking';
$string['pluginname'] = 'EJSAppBooking';

$string['manage_access_but'] = 'Gestionar el acceso de usuarios';

// Strings in select_rem_lab.php.
$string['selectRemLab_pageTitle'] = 'Selecci&oacute;n de laboratorio remoto';
$string['rem_lab_selection'] = 'Seleccione un laboratorio remoto';
$string['select_users_but'] = 'Fijar permisos de usuarios para este laboratorio';
$string['no_rem_labs'] = 'No hay laboratorios remotos en este curso';

// Strings in select_users.php.
$string['bookingRights_pageTitle'] = 'Permisos de reserva';
$string['users_selection'] = 'Seleccione los usuarios a los que dar&aacute; permisos de reserva en el laboratorio remoto seleccionado';
$string['accept_users_but'] = 'Aceptar';
$string['save_changes'] = 'Guardar cambios';
$string['booking_rights'] = 'Permiso de reserva';

// Strings in send_messages.php.
$string['allow_remlabaccess'] = 'Ha recibido permisos para realizar reservas en un nuevo laboratorio remoto: ';
$string['sending_message'] = 'Enviando mensajes de aviso';

$string['update_remlab_table'] = 'Actualizar tabla remlab de ejsapp booking';
$string['update_users_table'] = 'Actualizar tabla users de ejsapp booking';

$string['already_enabled'] = 'Ya tiene un sistema de reservas en este curso.';

$string['newreservation'] = 'Realizar una reserva';
$string['deleteBooking'] = 'Informaci&oacute;n de la reserva eliminada';
$string['mybookings'] = 'Mis reservas';
$string['plant'] = 'Planta';
$string['availability'] = 'Disponibilidad';
$string['bookinginfo'] = 'Informaci&oacute;n sobre la reserva';
$string['totalslots'] = 'Alcanzado el m&aacute;ximo de reservas permitidas en este laboratorio';
$string['weeklyslots'] = 'Alcanzado el mM&aacute;ximo de reservas semanales permitidas';
$string['dailyslots'] = 'Alcanzado el m&aacute;ximo de reservas por dia';
$string['bookingexits'] = 'La reserva ya existe';
$string['selectdate'] = 'Debe seleccionar una fecha y hora disponible en el calendario';
$string['delete'] = 'Borrar';
$string['selectbooking'] = 'Si desea anular alguna reserva selecci&oacute;nela y pulse';
$string['nobooking'] = 'No existen reservas';
$string['date'] = 'Fecha';
$string['hour'] = 'Hora';
$string['action'] = 'Acción';
$string['send_message'] = 'Enviado mensaje de aviso de la acci&oacute;n anterior';
$string['book'] = 'Reservar';
$string['book_message'] = 'Reserva';
$string['messageDelete'] = '¿Est&aacute; seguro de que desea eliminar esta reserva?';
$string['cancel'] = 'Cancelar';
$string['active_plant'] = 'Planta disponible';
$string['inactive_plant'] = 'Planta no disponible';
$string['iyear'] = 'A&ntilde;o';
$string['imonth'] = 'Mes';
$string['availability_booking'] = 'N&uacute;mero de reservas disponibles';
$string['rem_prac_selection'] = 'Seleccione una practica';
$string['day_selection'] = 'Seleccione una fecha';
$string['time_selection'] = 'Seleccione una hora de inicio el';
$string['messageOccupied'] = 'El slot esta ocupado';
$string['no_labs_rem'] = 'No tiene permisos para reservar o no existe ningún laboratorio remoto configurado';
$string['slot-free'] = 'Este hueco está disponible';
$string['slot-past'] = 'Este hueco ya no está disponible';
$string['slot-busy'] = 'Este hueco está ocupado';
$string['plant-inactive'] = 'Esta planta no está disponible en estos momentos';
$string['submit-error'] = 'Por favor, compruebe los mensajes de error antes de completar su reserva.';
$string['submit-success'] = 'Su reserva se ha guardado con éxito';
$string['submit-error-exists'] = 'Esta reserva ya existe en la base de datos';
$string['delete-confirmation'] = '¿Está seguro de que quiere eliminar este elemento?';
    
// Capabilities.
$string['ejsappbooking:addinstance'] = 'Añadir un nuevo sistema de reservas';
$string['ejsappbooking:view'] = 'Ver el sistema de reservas';
$string['ejsappbooking:managerights'] = 'Gestionar permisos de reservas de los usuarios';

//Privacy
$string['privacy:metadata'] = 'La actividad EJSApp Booking solo almacena informacion acerca de las reservas hechas por usuarios para las actividades de laboratorios remotos.';