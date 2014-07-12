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
// The GNU General Public License is available on <http://www.gnu.org/licenses/>
//
// EJSApp booking system has been developed by:
//  - Francisco José Calvillo Muñoz: ji92camuf@gmail.com
//  - Luis de la Torre: ldelatorre@dia.uned.es
//	- Ruben Heradio: rheradio@issi.uned.es
//
//  at the Computer Science and Automatic Control, Spanish Open University
//  (UNED), Madrid, Spain


/**
 * Prints a particular instance of ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

$debug = false;
$deletebutton = false;
$message = false;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // ejsappbooking instance ID - it should be named as the first character of the module
$labid = optional_param('labid', 0, PARAM_INT); // Laboratorio seleccionado
$practid = optional_param('practid', 0, PARAM_INT); // Practica seleccionada
$booking = optional_param_array('booking', array(), PARAM_INT); // Reservas seleccionadas para grabar o borrar
$bookingbutton = optional_param('bookingbutton', 0, PARAM_RAW); // Controla la funcionalidad de grabar reservas
$Mybookingsbutton = optional_param('Mybookingsbutton', 0, PARAM_RAW); // Controla la funcionalidad de mostrar las reservas del usuario
$Mybookingsdelete = optional_param('Mybookingsdelete', 0, PARAM_RAW); // Controla la funcionalidad de borrar las reservas seleccionadas del usuario
$selectDay = optional_param('selectDay', 0, PARAM_RAW); // Dia seleccionado
$page = optional_param('page', 0, PARAM_INT); // which page to show

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $ejsappbooking->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ejsappbooking', $ejsappbooking->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

add_to_log($course->id, 'ejsappbooking', 'view', "view.php?id={$cm->id}", $ejsappbooking->name, $cm->id);

/// Print the page header
$PAGE->set_title(format_string($ejsappbooking->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url('/mod/ejsappbooking/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'ejsappbooking')));
$PAGE->requires->string_for_js('messageDelete', 'ejsappbooking');
$PAGE->requires->string_for_js('book_message', 'ejsappbooking');
$PAGE->requires->string_for_js('cancel', 'ejsappbooking');
$PAGE->requires->js('/mod/ejsappbooking/module.js');

// Output starts here
echo $OUTPUT->header();

if ($ejsappbooking->intro) { // If some text was written, show the intro
    echo $OUTPUT->box(format_module_intro('ejsappbooking', $ejsappbooking, $cm->id), 'generalbox mod_introbox', 'ejsappbookingintro');
}

// Obtener los laboratorios remotos autorizados para el usuario
$rem_labs = $DB->get_records_sql("SELECT DISTINCT (a.id), a.name FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} b ON a.id = b.ejsappid WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1", array($USER->id, $course->id));

if(!$rem_labs) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('no_labs_rem', 'ejsappbooking'));
}
else {

$i = 1;
$multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
foreach ($rem_labs as $rem_lab) {
    $lab_name[$rem_lab->id] = $multilang->filter($rem_lab->name);
    if ($i == 1 && $labid == 0) {
        $labid = $rem_lab->id;
    }
    $i++;
}


// Verificar si es la primera vez que se entra o el valor del parametro selectDay, también se instancian unas variables
// utilizados para deshabilitar algunos slot en la tabla de reservas disponibles
$anterior = false;
$hoy = true;

if (!$selectDay) {
    $fecha = new DateTime("now");
    $today = new DateTime("now");
} else {
    $today = new DateTime("now");
    $fecha = DateTime::createFromFormat("Y-m-d", $selectDay);

    if ($today->format("Y-m-d") == $fecha->format("Y-m-d")) {
        //$hoy = true;
        //echo 'DIA ACTUAL: ' . $fecha->format('Y-m-d') . ' ' . $today->format("Y-m-d");
    } else if ($today->format("Y-m-d") < $fecha->format("Y-m-d")) {
        $anterior = false;
        $hoy = false;
    } else {
        $anterior = true;
        $hoy = false;
    }

}

// Comienza la creación de la página web

$baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('makereservation', 'ejsappbooking'));
$iconurl = $CFG->wwwroot . '/mod/ejsappbooking/pix/seleccionada.png';

// Se comprueba si el laboratorio esta activo
$conf_labs = $DB->get_record('ejsapp_remlab_conf', array('ejsappid' => $labid));

if($conf_labs->active)
    $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_success_44x44.png';
else
    $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_error_44x44.png';

// Se construye la parte de la interfaz donde aparecen los datos del usuario y el control del calendario

echo html_writer::start_tag('div', array('id' => 'container', 'align' => 'center'));
$user_picture = $OUTPUT->user_picture($USER, array('size' => 100, 'courseid'=>$course->id));
$user_fullname = $OUTPUT->container('<p align="center">' . fullname($USER, has_capability('moodle/site:viewfullnames', $context)). '</strong></p>', 'username');
$date_active = $OUTPUT->container('<p align="center"> <strong> <span id="fechaActiva">' . $fecha->format("Y-m-d") . '</span> </strong></p>');
$plant_name = $OUTPUT->container('<p align="center">'. get_string('plantActive','ejsappbooking').'</p>');
$plant_state = $OUTPUT->container('<p align="center"><img src="' . $plantico. '" width="44px" height="44px"></p>');
$calendar = html_writer::start_tag('div', array('id' => 'calendario', 'align' => 'center'));
$calendar .= html_writer::end_tag('div')  . '<br>';
$selectDate = html_writer::start_tag('div', array('id' => 'control', 'align' => 'center'));
$selectDate .= '<button id="restarAno">&lt;' . get_string('iyear','ejsappbooking') . '</button>';
$selectDate .= '<button id="restarMes">&lt;' . get_string('imonth','ejsappbooking') . '</button>';
$selectDate .= '<button id="sumarMes">' . get_string('imonth','ejsappbooking') . '&gt;</button>';
$selectDate .= '<button id="sumarAno">' . get_string('iyear','ejsappbooking') . '&gt;</button>';
$selectDate .= html_writer::end_tag('div') . '<br>';
$table = new html_table();
$table->attributes['class'] = 'userinfobox';
$row = new html_table_row();
$row->cells[0] = new html_table_cell();
$row->cells[0]->attributes['class'] = 'left';
$row->cells[0]->text = $user_picture .''. $user_fullname .''. $date_active .''. $plant_name .''. $plant_state . '';
$row->cells[1] = new html_table_cell();
$row->cells[1]->attributes['class'] = 'center';
$row->cells[1]->text = $calendar .''. $selectDate;
$row->cells[2] = new html_table_cell();
$row->cells[2]->attributes['class'] = 'right';
$row->cells[2]->text = '';
$table->data[0] = $row;
echo html_writer::table($table);

// Se estable el formulario de selección de laboratorios y practicas

$out = html_writer::start_tag('form', array('id' => 'bookingform', 'method' => 'get', 'action' => $baseurl));
$out .= html_writer::start_tag('div', array('id' => 'controls', 'align' => 'center'));
$out .= get_string('rem_lab_selection', 'ejsappbooking');
$out .= '<select name="labid" data-previousindex="0" onchange="this.form.submit()"> ';
$labactual = '';
$i = 1;
foreach ($rem_labs as $rem_lab) {
    $lab_name[$rem_lab->id] = $multilang->filter($rem_lab->name);
    if ($i == 1 && $labid == 0) {
        $labid = $rem_lab->id;
    }
    $out .= '<option value="' . $rem_lab->id . '"';

    if ($labid == $rem_lab->id) {
        $out .= 'selected="selected"';
        $labactual = $lab_name[$rem_lab->id];
    }
    $out .= '>' . $lab_name[$rem_lab->id] . '</option>';
    $i++;
}
$out .= '</select>';
$rem_practices = $DB->get_records_sql("SELECT id, ejsappid, practiceid, practiceintro FROM {ejsapp_expsyst2pract} WHERE ejsappid = ? ", array($labid));
$i = 1;
$multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
foreach ($rem_practices as $practice_lab) {
    $lab_name[$practice_lab->id] = $multilang->filter($practice_lab->practiceintro);
    if ($i == 1 && $practid == 0) {
        $practid = $practice_lab->practiceid;
    }
    $i++;
}
$practActual = '';
$out .= '<br>';
$out .= get_string('rem_prac_selection', 'ejsappbooking');
$out .= '<select name="practid" data-previousindex="0" onchange="this.form.submit()"> ';
$i = 1;
foreach ($rem_practices as $practice_lab) {
    $lab_name[$practice_lab->practiceid] = $multilang->filter($practice_lab->practiceintro);
    if ($i == 1 && $practid == 0) {
        $practid = $practice_lab->practiceid;
    }
    $out .= '<option value="' . $practice_lab->practiceid . '"';

    if ($practid == $practice_lab->practiceid) {
        $out .= 'selected="selected"';
        $practActual = $lab_name[$practice_lab->practiceid];
    }
    $out .= '>' . $lab_name[$practice_lab->practiceid] . '</option>';
    $i++;
}
$out .= '</select>';
$out .= html_writer::end_tag('div') . '<br>';
$out .= html_writer::start_tag('div', array('id' => 'contents', 'align' => 'center'));
$bookingtable = new html_table();
$bookingtable->attributes['class'] = 'controls';
$bookingtable->attributes['border'] = '1';
$bookingtable->id = 'tablabooking';
$bookingtable->cellspacing = 0;
$bookingtable->align[1] = 'center';
$bookingtable->head = array(' ', get_string('availability', 'ejsappbooking'), $fecha->format('d-m-Y'), ' ');


// Empieza la logica del programa

// Se comprueba si se ha pulsado el botón de reservas
if ($bookingbutton) {

    // Si existen reservas en la petición. Se controla las restricciones del laboratorio, controlando las peticiones existentes y reserva en curso
    if ($booking) {

        $bookingtable->head = array(get_string('plant', 'ejsappbooking'), get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'));

        $i = 0;
        $message = "";
        $today = new DateTime("now");
        //$user_access = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE DATE_FORMAT(starttime, '%Y-%m-%d') >= ? AND username = ? AND ejsappid = ? ORDER BY starttime ASC", array($today->format('Y-m-d'), $USER->username, $labid));
        // Numero de reservas en el laboratorio
        $user_access = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE username = ? AND ejsappid = ? ORDER BY starttime ASC", array($USER->username, $labid));
        // Reservas en la petición
        $pre_booking = count($booking);
        // Reservas en la BD
        $numero = count($user_access);
        $mensaje = false;
        // Se establece el guardado de la reserva antes de la comprobación de las restricciones
        $save = 1;

        // Comienzo codigo comprobacion restricciones laboratorio
        $total = $pre_booking + $numero;
        $dia = $fecha->format('w');

        if ($dia == 0) {
            $lunes = 6;
            $domingo = 0;
        } else {
            $lunes = $dia - 1;
            $domingo = 7 - $dia;
        }

        $dlunes = strtotime('-' . $lunes . 'day', strtotime($fecha->format('Y-m-d')));
        $ddomingo = strtotime('+' . $domingo . 'day', strtotime($fecha->format('Y-m-d')));

        $dlunes = date('Y-m-d', $dlunes);
        $ddomingo = date('Y-m-d', $ddomingo);

        if ($dlunes < $today->format('Y-m-d')) {
            $dlunes = $today->format('Y-m-d');
        }

        $user_access_week = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE DATE_FORMAT(starttime, '%Y-%m-%d') >= ? AND DATE_FORMAT(starttime, '%Y-%m-%d') <= ? AND username = ? AND ejsappid = ? ORDER BY starttime ASC", array($dlunes, $ddomingo, $USER->username, $labid));

        $numero2 = count($user_access_week);
        $total2 = $pre_booking + $numero2;

        // Se comprueban las restricciones totales, semanales y diarias.
        if ($total > $conf_labs->totalslots) {
            $save = 0;
            $number = $conf_labs->totalslots - $numero;
            $mensaje = get_string('totalslots', 'ejsappbooking'). ': ' . $conf_labs->totalslots;
            if($number > 0)
                $mensaje .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
        } else if ($total2 > $conf_labs->weeklyslots) {
            $save = 0;
            $mensaje = get_string('weeklyslots', 'ejsappbooking') . '. ';
            $number = $conf_labs->weeklyslots - $numero2;
            if($number > 0)
                $mensaje .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
        } else {
            $i = $pre_booking;

            if (count($user_access) == 0) {
                if ($i > $conf_labs->dailyslots) {
                    $save = 0;
                    $mensaje = get_string('dailyslots', 'ejsappbooking')  . ': ' . $conf_labs->dailyslots ;
                }
            } else {
                foreach ($user_access as $access) {

                    $convert = date("Y-m-d", strtotime($access->starttime));

                    if ($selectDay == $convert)
                        $i++;

                    if ($i > $conf_labs->dailyslots) {
                        $mensaje = get_string('dailyslots', 'ejsappbooking') . ': ' . $conf_labs->dailyslots;
                        $number = $conf_labs->dailyslots - $i;

                        if($number < 0)
                            $number = 0;

                        $mensaje .= ' ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
                        $save = 0;
                        break;
                    }
                }
            }
        }

        // Si existe alguna restricción, muestra un mensaje
        if ($mensaje) {
            $out .= '<p align="center"><strong>' . $mensaje . '</strong></p><br>';
        } else {
            // En caso contrario se graba la reserva
            if ($save) {
                $i = 0;
                //Preparamos el mensaje informando sobre la reserva
                $messagebody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

                // Recorremos cada reserva
                foreach ($booking as $book) {

                    $event = new stdClass();
                    $event->name = get_string('book_message', 'ejsappbooking') . ' '. $labactual . '. ' . $practActual;
                    $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
                    $event->groupid = 0;
                    $event->courseid = 0;
                    $event->userid = $USER->id;
                    $event->timeduration = 3540;
                    $event->eventtype = 'user';

                    $date = $fecha->format("Y-m-d") . ' ' . $book . ':00:00';

                    $bk = new stdClass();
                    $bk->username = $USER->username;
                    $bk->ejsappid = $labid;
                    $bk->practiceid = $practid;
                    $bk->starttime = date("Y-m-d H:00:00", strtotime($date));
                    $bk->endtime = date("Y-m-d H:59:59", strtotime($date));
                    $bk->valid = 1;

                    $initTime = new DateTime($bk->starttime);
                    $finishTime = new DateTime($bk->endtime);


                    // Comprobamos si la reserva existe
                    if ($DB->record_exists('ejsappbooking_remlab_access', array('starttime' => $bk->starttime, 'ejsappid' => $labid, 'practiceid' => $practid))) {

                        //Informamos que el slot esta ocupado
                        $bookingtable->data[] = new html_table_row();

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';
                        $bookingcell->text = get_string('messageOccupied', 'ejsappbooking');
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';
                        $bookingcell->text = $initTime->format("Y-m-d");
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';

                        $timeActual = $initTime->format('H:00:00') . '-' . $finishTime->format('H:59:59');
                        $bookingcell->text = $timeActual;
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                    } else {

                        // El slot no esta ocupado, se guarda en la BD
                        $identificador = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);

                        $bookingtable->data[] = new html_table_row();

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';
                        $bookingcell->text = $labactual . '. ' . $practActual;
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';
                        $bookingcell->text = $initTime->format("Y-m-d");
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                        $bookingcell = new html_table_cell();
                        $bookingcell->attributes['class'] = 'center';

                        $timeActual = $initTime->format('H:00:00') . '-' . $finishTime->format('H:59:59');
                        $bookingcell->text = $timeActual;
                        $bookingtable->data[$i]->cells[] = $bookingcell;

                        $event->timestart = make_timestamp($initTime->format('Y'), $initTime->format('m'), $initTime->format('d'), $initTime->format('H'));

                        // Completamos el mensaje con la información de la reserva
                        $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' . $labactual . '. ' . $practActual . '<br>';
                        $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("Y-m-d") . '<br>';
                        $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:00:00') . '-' . $finishTime->format('H:59:59') . '<br><br>';

                        // Completamos el evento con la información de la reserva
                        $event->description = $event->description . get_string('plant', 'ejsappbooking') . ': ' . $labactual . '. ' . $practActual . '<br>';
                        $event->description = $event->description . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("Y-m-d") . '<br>';
                        $event->description = $event->description . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:00:00') . '-' . $finishTime->format('H:59:59');

                        // Creamos el evento en el calendario
                        calendar_event::create($event);
                    }
                    $i++;
                }

                // Informamos si la mensajeria esta activada
                if (empty($CFG->messaging)) {
                    $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
                }

                //Formateamos y enviamos el mensaje mediante el usuario Admin
                $format = FORMAT_HTML;
                $usuario = $DB->get_record('user', array('id' => 2));
                @message_post_message($usuario, $USER, $messagebody, $format);

                // Escribimos la tabla con la información de la reserva
                $out .= '<p align="center"> <strong>' . get_string('bookinginfo', 'ejsappbooking') . '</strong></p>';
                $out .= html_writer::table($bookingtable);
                $out .= '<p align="center"> <strong>' . get_string('sending_message', 'ejsappbooking') . '</strong></p>';
           }
        }

    } else {
        // No recibimos como parametro ninguna fecha, mostramos un error
        $out .= '<p align="center"><strong>' . get_string('selectdate', 'ejsappbooking') . '</strong></p>';
    }

// Logica del programa donde se muestran las reservas del usuario y controla su eliminación
} else if ($Mybookingsbutton || $Mybookingsdelete) {

    $deletebutton = true;
    $bookingtable->head = array(' ', ' ', get_string('plant', 'ejsappbooking'), get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'));
    $username = $USER->username;

    // El programa ha recibido una petición para borrar la reserva
    if ($Mybookingsdelete) {

        // Preparamos el mensaje para informar
        $messagebody = get_string('deleteBooking', 'ejsappbooking') . '<br><br>';

        // Recorremos las reservas seleccionadas y las eliminamos
        foreach ($booking as $book) {

            $record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $book));
            $labs = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
            $prac = $DB->get_record('ejsapp_expsyst2pract', array('practiceid' => $record->practiceid, 'ejsappid'=>$record->ejsappid));
            $initTime = new DateTime($record->starttime);
            $error = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $book));
            $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' . $labs->name . '<br>';
            $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("d-m-y") . '<br>';
            $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:00:00') . '-' . $initTime->format('H:59:59') . '<br><br>';
            $time = make_timestamp($initTime->format('Y'), $initTime->format('m'), $initTime->format('d'), $initTime->format('H'));
            $event = $DB->get_record_sql('SELECT * FROM {event} WHERE userid = ? AND name = ? AND timestart = ?', array($USER->id, get_string('book_message', 'ejsappbooking') . ' '. $labs->name . '. ' . $prac->practiceintro, $time));

            // Eliminamos el evento del calendario
            if ($event) {
                $event = calendar_event::load($event->id);
                $event->delete($deleterepeated = false);
            }
        }

        // Informamos si la mensajeria esta activada
        if (empty($CFG->messaging)) {
            $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
        }

        // Formateamos y enviamos el mensaje como Admin
        $format = FORMAT_HTML;
        $usuario = $DB->get_record('user', array('id' => 2));
        @message_post_message($usuario, $USER, $messagebody, $format);
        // Informamos por pantalla
        $message = '<p align="center"> <strong>' . get_string('send_message', 'ejsappbooking') . '</strong></p>';
    }

    // Paginamos y presentamos las reservas del usuario
    $today = new DateTime("now");
    $events = $DB->get_records_sql("SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id WHERE a.username = ? AND DATE_FORMAT(a.starttime, '%Y-%m-%d') >= ?  ORDER BY a.starttime ASC", array($username, $today->format('Y-m-d')));
    $result = count($events);
    $reservasPorPagina = 12;
    $paginasTotales = ceil($result / $reservasPorPagina);
    $paginaActual = 0;

    if (isset($page) || empty($page)) {
        $paginaActual = $page;
    }

    if ($paginaActual < 1) {
        $paginaActual = 1;
    } else if ($paginaActual > $paginasTotales) {
        $paginaActual = $paginasTotales;
    }

    $reservaInicial = ($paginaActual - 1) * $reservasPorPagina;

    $sql = 'SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id  INNER JOIN {ejsapp_expsyst2pract} c ON a.practiceid = c.practiceid  WHERE a.ejsappid = c.ejsappid AND a.username = "' . $username . '" AND DATE_FORMAT(starttime, "%Y-%m-%d") >= "'. $today->format('Y-m-d') .'"ORDER BY a.starttime ASC LIMIT ' . $reservaInicial . ', ' . $reservasPorPagina . '';
    $events2 = $DB->get_records_sql($sql);
    $result2 = count($events2);
    $fecha = new DateTime("now");

    // Existen reservas
    if ($result != 0) {

        $i = 0;
        foreach ($events2 as $event) {

            $nombre = 'booking[' . $i . ']';
            $valor = $event->id;

            $today = new DateTime("now");
            $time = new DateTime($event->starttime);
            $visible = array(null);

            if ($today->format("Y-m-d") < $time->format("Y-m-d")) {
                $url = 'disponible.png';
            } else {
                $url = 'ocupada.png';
                if ($today->format("Y-m-d") == $time->format("Y-m-d"))
                    $visible = array('disabled' => 'disable');
            }

            $bookingtable->data[] = new html_table_row();

            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            if ($paginaActual == 1)
                $numpage = ($i + 1);
            else
                $numpage = (($paginaActual - 1) * $reservasPorPagina) + ($i + 1);

            $bookingcell->text = ' ' . $numpage . ' ';
            $bookingtable->data[$i]->cells[] = $bookingcell;

            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            $bookingcell->text = html_writer::checkbox($nombre, $valor, false, null, $visible);
            $bookingtable->data[$i]->cells[] = $bookingcell;

            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            $bookingcell->text = $event->name . '. ' . $event->practiceintro;
            $bookingtable->data[$i]->cells[] = $bookingcell;

            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            $bookingcell->text = $time->format("Y-m-d");
            $bookingtable->data[$i]->cells[] = $bookingcell;

            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            $time2 = new DateTime($event->endtime);
            $timeActual = $time->format("H:i") . '-' . $time2->format("H:i");
            $bookingcell->text = $timeActual;
            $bookingtable->data[$i]->cells[] = $bookingcell;

            $i++;
        }

        $out .= '<p align="center"> <strong>' . get_string('mybookings', 'ejsappbooking') . '</strong></p>';
        $out .= html_writer::table($bookingtable);

        if ($result > $reservasPorPagina) {

            $out .= '<div class="paginacion">';

            // mostramos la paginación
            for ($i = 1; $i <= $paginasTotales; $i++) {

                if ($i == $paginaActual) {
                    $out .= '&nbsp;&nbsp;&nbsp;<span class="pagina actual">' . $i . '</span>';
                }
                // sólo vamos a mostrar los enlaces de la primer página,
                // las dos siguientes, las dos anteriores
                // y la última
                else if ($i == 1 || $i == $paginasTotales || ($i >= $paginaActual - 2 && $i <= $paginaActual + 2)) {
                    $out .= '&nbsp;&nbsp;&nbsp;<a href="' . $baseurl->out() . '&amp;Mybookingsbutton=1&amp;page=' . $i . '" class="pagina">' . $i . '</a>';
                }
            }
            $out .= '</div>';
        }
        $out .= '<input id="Mybookingsdelete" name="Mybookingsdelete" value="0" type="hidden">';

    } else {
        // Mostramos un mensaje indicando que no existen reservas
        $deletebutton = false;
        $out .= '<p><strong>' . get_string('nobooking', 'ejsappbooking') . '</strong></p>';
    }
    // Fin mostrar reservas y su eliminación

// Se genera la tabla con las reservas en un laboratorio remoto en el dia seleccionado.
} else {

    $events = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE DATE_FORMAT(starttime, '%Y-%m-%d') = ? AND ejsappid = ? AND $practid = ? ORDER BY starttime ASC", array($fecha->format('Y-m-d'), $labid, $practid));

    for ($i = 0; $i < 6; $i++) {

        $bookingtable->data[] = new html_table_row();


        for ($j = 0; $j < 4; $j++) {

            $index = ($j * 6) + $i;
            $initTime = new DateTime('2000-01-01');
            $finishTime = new DateTime('2000-01-01');
            $initTime->add(new DateInterval('PT' . $index . 'H'));
            $finishTime->add(new DateInterval('PT' . ($index + 1) . 'H'));

            $nombre = 'booking[' . $index . ']';
            $valor = $index;
            $etiqueta = $initTime->format('H:i') . ' - ' . $finishTime->format('H:i');
            $visible = array(null);
            $chequeado = false;
            $url = 'disponible.png';

            // Horas no validas
            $actual = new DateTime("now");
            if ($anterior) {
                $visible = array('disabled' => 'disable');
                $chequeado = false;
                $url = 'nodisponible.png';
            } else if (($initTime->format('H') < $actual->format('H')) && $hoy) {
                $visible = array('disabled' => 'disable');
                $chequeado = false;
                $url = 'nodisponible.png';
            }

            // Comprobar si la hora esta reservada
            foreach ($events as $event) {
                $date = $event->starttime;
                if ($initTime->format('H:i') == date("H:i", strtotime($date))) {
                    $chequeado = true;
                    $visible = array('disabled' => 'disable');
                    $url = 'ocupada.png';
                }
            }
            $bookingcell = new html_table_cell();
            $bookingcell->attributes['class'] = 'center';
            $bookingcell->text = '<img id=bookimg' . $valor . ' src="' . $CFG->wwwroot . '/mod/ejsappbooking/pix/' . $url . '" width="12px" height="12px">&nbsp;' . html_writer::checkbox($nombre, $valor, $chequeado, $etiqueta, $visible);
            $bookingtable->data[$i]->cells[] = $bookingcell;
        }
    }
    $out .= html_writer::table($bookingtable);
    $out .= '<p align="center"><button name="bookingbutton" value="1" type="submit">' . get_string('book', 'ejsappbooking') . '</button></p>';
}

$out .= html_writer::end_tag('div');

// Parametros ocultos enviados en el formulario
$out .= '<input id="id" name="id" value=' . $id . ' type="hidden">';
$out .= '<input id="selectdate" name="selectDay" value="' . $selectDay . '" type="hidden">';

// Boton muestra mis reservas
$out .= '<p align="center"><button name="Mybookingsbutton" value="1" type="submit">' . get_string('mybookings', 'ejsappbooking') . '</button></p>';
$out .= html_writer::end_tag('form');
// Fin formulario

// Si se trata de la opcíon de borrar, mostramos el botón que ejecuta y confirma la acción.
if ($deletebutton) {
    $out .= '<p align="center"><strong>' . get_string('selectbooking', 'ejsappbooking') . '</strong>&nbsp;';
    $out .= '<button class="yui3-button btn-show">' . get_string('delete', 'ejsappbooking') . '</button></p>';
    if($message)
        $out .= $message;
}

// Creamos y presentamos la salida: slot disponibles, reserva realizada, eliminacion de reservas, reservas del usuario en la página.
$table = new html_table();
$table->attributes['class'] = 'userinfobox';
$row = new html_table_row();
$row->cells[0] = new html_table_cell();
$row->cells[0]->attributes['class'] = 'center';
$row->cells[0]->text = $out;
$table->data[0] = $row;
echo html_writer::table($table);

// Check wether the user has teacher or admin privileges. If so, let him grant his students access to make bookings for the remote labs in the course
if (has_capability('moodle/course:viewhiddensections', $context, $USER->id, true)) {
    $set_permissions = $CFG->wwwroot . '/mod/ejsappbooking/set_permissions.php';
    echo $OUTPUT->heading('<form action="' . $set_permissions . '" method="get"><input type="hidden" name="id" value="' . $cm->id . '"><input type="hidden" name="courseid" value="' . $course->id . '"><input type="hidden" name="contextid" value="' . $context->id . '"><input type=submit id="manage_access" value="' . get_string('manage_access_but', 'ejsappbooking') . '"></form>');
}

echo html_writer::end_tag('div');

}

echo $OUTPUT->box_end();

// Finish the page
echo $OUTPUT->footer();