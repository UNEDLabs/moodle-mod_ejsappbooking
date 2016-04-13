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
//  - Francisco José Calvillo Muñoz: fcalvillo9@alumno.uned.es
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

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');

$deletebutton = false;
$message = false;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // ejsappbooking instance ID - it should be named as the first character of the module
$labid = optional_param('labid', 0, PARAM_INT); // selected laboratory
$practid = optional_param('practid', 0, PARAM_INT); // selected practice
$booking = optional_param_array('booking', array(), PARAM_INT); // bookings selected to save or delete
$bookingbutton = optional_param('bookingbutton', 0, PARAM_RAW); // controls record functionality reserves
$Mybookingsbutton = optional_param('Mybookingsbutton', 0, PARAM_RAW); // Controls the functionality of showing the user reservation
$Mybookingsdelete = optional_param('Mybookingsdelete', 0, PARAM_RAW); // Controls functionality to delete the selected user reservation
$selectDay = optional_param('selectDay', 0, PARAM_RAW); // selected day
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

if ($CFG->version < 2013111899) { //Moodle 2.6 or inferior
    add_to_log($course->id, 'ejsappbooking', 'view', "view.php?id={$cm->id}", $ejsappbooking->name, $cm->id);
} else {
    $event = \mod_ejsappbooking\event\course_module_viewed::create(array(
        'objectid' => $ejsappbooking->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('ejsappbooking', $ejsappbooking);
    $event->trigger();
}

/// Print the page header
$PAGE->set_title(format_string($ejsappbooking->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url('/mod/ejsappbooking/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'ejsappbooking'));
$PAGE->requires->string_for_js('messageDelete', 'ejsappbooking');
$PAGE->requires->string_for_js('book_message', 'ejsappbooking');
$PAGE->requires->string_for_js('cancel', 'ejsappbooking');
$PAGE->requires->js('/mod/ejsappbooking/module.js');

// Output starts here
echo $OUTPUT->header();

if ($ejsappbooking->intro) { // If some text was written, show the intro
    echo $OUTPUT->box(format_module_intro('ejsappbooking', $ejsappbooking, $cm->id), 'generalbox mod_introbox', 'ejsappbookingintro');
}

// Get the remote laboratories in which the user is authorized to make bookings
$rem_labs = $DB->get_records_sql("SELECT DISTINCT (a.id), a.name FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} b ON a.id = b.ejsappid WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1", array($USER->id, $course->id));


if(!$rem_labs) { // No labs
    echo $OUTPUT->heading(get_string('no_labs_rem', 'ejsappbooking'));
} else { // At least one remote lab

    // Obtain the name of the remote lab considering the language filter
    $i = 1;
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
    foreach ($rem_labs as $rem_lab) {
        $lab_name[$rem_lab->id] = $multilang->filter($rem_lab->name);
        if ($i == 1 && $labid == 0) {
            $labid = $rem_lab->id;
        }
        $i++;
    }

    $today = new DateTime("now");

    // Check the selected day
    if (!$selectDay) {
        $sDate = $today;
        $previous_day = false;
        $now = true;
    } else {
        $sDate = DateTime::createFromFormat("Y-m-d", $selectDay);
        // used to clear days in the calendar
        if ($today->format("Y-m-d") == $sDate->format("Y-m-d")) {
            $previous_day = false;
            $now = true;
        } else if ($today->format("Y-m-d") < $sDate->format("Y-m-d")) {
            $previous_day = false;
            $now = false;
        } else {
            $previous_day = true;
            $now = false;
        }
    }

    // Star building the website
    $baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('makereservation', 'ejsappbooking'));
    $iconurl = $CFG->wwwroot . '/mod/ejsappbooking/pix/selected.png';

    // Check the configuration of the lab (whether it is active or not)
    $practiceintro = $DB->get_field('remlab_manager_expsyst2pract', 'practiceintro', array('ejsappid' => $labid));
    $conf_labs = $DB->get_record('remlab_manager_conf', array('practiceintro' => $practiceintro));
    if($conf_labs->active) {
        $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_success_44x44.png';
        $plant_state_info_string = get_string('active_plant','ejsappbooking');
    } else {
        $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_error_44x44.png';
        $plant_state_info_string = get_string('inactive_plant','ejsappbooking');
    }

    //  User data and control the calendar
    echo html_writer::start_tag('div', array('id' => 'container', 'align' => 'center'));
    $user_picture = $OUTPUT->user_picture($USER, array('size' => 100, 'courseid'=>$course->id));
    $user_fullname = $OUTPUT->container('<p align="center">' . fullname($USER, has_capability('moodle/site:viewfullnames', $context)). '</strong></p>', 'username');
    $date_active = $OUTPUT->container('<p align="center"> <strong> <span id="ActiveDate">' . $sDate->format("Y-m-d") . '</span> </strong></p>');
    $plant_name = $OUTPUT->container('<p align="center">'. $plant_state_info_string .'</p>');
    $plant_state = $OUTPUT->container('<p align="center"><img src="' . $plantico. '" width="44px" height="44px"></p>');
    $calendar = html_writer::start_tag('div', array('id' => 'calendar', 'align' => 'center'));
    $calendar .= html_writer::end_tag('div')  . '<br>';
    $selectDate = html_writer::start_tag('div', array('id' => 'control', 'align' => 'center'));
    $selectDate .= '<button id="subyear">&lt;' . get_string('iyear','ejsappbooking') . '</button>';
    $selectDate .= '<button id="submonth">&lt;' . get_string('imonth','ejsappbooking') . '</button>';
    $selectDate .= '<button id="addmonth">' . get_string('imonth','ejsappbooking') . '&gt;</button>';
    $selectDate .= '<button id="addyear">' . get_string('iyear','ejsappbooking') . '&gt;</button>';
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

    // Select labs
    $out = html_writer::start_tag('form', array('id' => 'bookingform', 'method' => 'get', 'action' => $baseurl));
    $out .= html_writer::start_tag('div', array('id' => 'controls', 'align' => 'center'));
    $out .= get_string('rem_lab_selection', 'ejsappbooking');
    $out .= '&nbsp;&nbsp;<select name="labid" data-previousindex="0" onchange="this.form.submit()"> ';
    $currentLab = '';
    $i = 1;
    foreach ($rem_labs as $rem_lab) {
        $lab_name[$rem_lab->id] = $multilang->filter($rem_lab->name);
        if ($i == 1 && $labid == 0) {
            $labid = $rem_lab->id;
        }
        $out .= '<option value="' . $rem_lab->id . '"';

        if ($labid == $rem_lab->id) {
            $out .= 'selected="selected"';
            $currentLab = $lab_name[$rem_lab->id];
        }
        $out .= '>' . $lab_name[$rem_lab->id] . '</option>';
        $i++;
    }
    $out .= '</select>';

    // Select practices
    $rem_practices = $DB->get_records_sql("SELECT id, ejsappid, practiceid, practiceintro FROM {remlab_manager_expsyst2pract} WHERE ejsappid = ? ", array($labid));
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
    $out .= '&nbsp;&nbsp;<select name="practid" data-previousindex="0" onchange="this.form.submit()"> ';
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
    $bookingtable->align[1] = 'center';

    // <Program logic>

    // Reservation functionality
    if ($bookingbutton) {

        // Checks if there are reservations on request
        if ($booking) {

            $bookingtable->head = array(get_string('plant', 'ejsappbooking'), get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'));

            $i = 0;
            //$message = "";

            // user´s bookings at the DB
            $user_access = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE username = ? AND ejsappid = ? ORDER BY starttime ASC", array($USER->username, $labid));
            $userBooks = count($user_access);

            // bookings at the request
            $pre_booking = count($booking);

            $message = false;
            // Set save
            $save = 1;
            $total = $pre_booking + $userBooks;
            $day = $sDate->format('w');

            if ($day == 0) {
                $monday = 6;
                $sunday = 0;
            } else {
                $monday = $day - 1;
                $sunday = 7 - $day;
            }

            $dmonday = strtotime('-' . $monday . 'day', strtotime($sDate->format('Y-m-d')));
            $dsunday = strtotime('+' . $sunday . 'day', strtotime($sDate->format('Y-m-d')));
            $dmonday = date('Y-m-d', $dmonday);
            $dsunday = date('Y-m-d', $dsunday);

            if ($dmonday < $today->format('Y-m-d')) {
                $dmonday = $today->format('Y-m-d');
            }

            // user´s bookings of the week
            $user_access_week = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE DATE_FORMAT(starttime, '%Y-%m-%d') >= ? AND DATE_FORMAT(starttime, '%Y-%m-%d') <= ? AND username = ? AND ejsappid = ? ORDER BY starttime ASC", array($dmonday, $dsunday, $USER->username, $labid));
            $weekBooks = count($user_access_week);

            $total2 = $pre_booking + $weekBooks;

            // check restrictions
            if ($total > $conf_labs->totalslots) {
                $save = 0;
                $number = $conf_labs->totalslots - $userBooks;
                $message = get_string('totalslots', 'ejsappbooking'). ': ' . $conf_labs->totalslots;
                if($number > 0)
                    $message .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            } else if ($total2 > $conf_labs->weeklyslots) {
                $save = 0;
                $message = get_string('weeklyslots', 'ejsappbooking') . '. ';
                $number = $conf_labs->weeklyslots - $weekBooks;
                if($number > 0)
                    $message .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
            } else {
                $i = $pre_booking;

                if ($userBooks == 0) {
                    if ($i > $conf_labs->dailyslots) {
                        $save = 0;
                        $message = get_string('dailyslots', 'ejsappbooking')  . ': ' . $conf_labs->dailyslots ;
                    }
                } else {

                    foreach ($user_access as $access) {

                        $convert = date("Y-m-d", strtotime($access->starttime));

                        if ($convert == $sDate->format("Y-m-d"))
                            $i++;


                        if ($i > $conf_labs->dailyslots) {
                            $message = get_string('dailyslots', 'ejsappbooking') . ': ' . $conf_labs->dailyslots;
                            $number = $conf_labs->dailyslots - $i;

                            if($number < 0)
                                $number = 0;

                            $save = 0;
                            break;
                        }
                    }
                }
            }

            // If there are any restrictions, displays a message
            if ($message) {
                $out .= '<p align="center"><strong>' . $message . '</strong></p><br>';
            } else {
                // save the booking
                if ($save) {
                    $i = 0;
                    // booking info
                    $messagebody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

                    // bookings
                    foreach ($booking as $book) {

                        $event = new stdClass();
                        $event->name = get_string('book_message', 'ejsappbooking') . ' '. $currentLab . '. ' . $practActual;
                        $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
                        $event->groupid = 0;
                        $event->courseid = 0;
                        $event->userid = $USER->id;
                        $event->timeduration = 3540;
                        $event->eventtype = 'user';

                        $slotDuration = $DB->get_field('remlab_manager_conf', 'slotsduration', array('practiceintro'=>$practiceintro) );
                        $slotMultiplo = 1;
                        $min = 0;
                        switch ($slotDuration){
                            case 0: //60 min
                                $slotMultiplo = 1;
                                $min = 0;
                                break;
                            case 1: //30 min
                                $slotMultiplo = 2;
                                $min = 30;
                                break;
                            case 2: //15 min
                                $slotMultiplo = 4;
                                $min = 15;
                                break;
                            case 3: //5 min
                                $slotMultiplo = 12;
                                $min = 5;
                                break;
                            case 4: //2 min
                                $slotMultiplo = 30;
                                $min = 2;
                                break;
                            
                        }
                        $hourStart = ($book - $book % $slotMultiplo)/$slotMultiplo;
                        $hourEnd = ($book +1 - ($book+1) % $slotMultiplo)/$slotMultiplo;
                        $minStart = $book % $slotMultiplo * $min;
                        $minEnd = ($book+1) % $slotMultiplo * $min - 1;
                        if ($minEnd < 0){
                            $hourEnd--;
                            $minEnd += 60;
                        }
                        $dateStart = $sDate->format("Y-m-d") . ' ' . $hourStart . ':' . $minStart . ':00';
                        $dateEnd = $sDate->format("Y-m-d") . ' ' . $hourEnd . ':' . $minEnd . ':59';

                        $bk = new stdClass();
                        $bk->username = $USER->username;
                        $bk->ejsappid = $labid;
                        $bk->practiceid = $practid;
                        $bk->starttime = date("Y-m-d H:i:00", strtotime($dateStart));
                        $bk->endtime = date("Y-m-d H:i:59", strtotime($dateEnd));
                        $bk->valid = 1;                    
                        $initTime = new DateTime($bk->starttime);
                        $finishTime = new DateTime($bk->endtime);


                        // Will the book exists?
                        if ($DB->record_exists('ejsappbooking_remlab_access', array('starttime' => $bk->starttime, 'ejsappid' => $labid, 'practiceid' => $practid))) {

                            // report that the slot is occupied
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

                            $timeActual = $initTime->format('H:i:00') . '-' . $finishTime->format('H:i:59');
                            $bookingcell->text = $timeActual;
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                        } else {

                            // save in database
                            $identificador = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);

                            $bookingtable->data[] = new html_table_row();

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = $currentLab . '. ' . $practActual;
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = $initTime->format("Y-m-d");
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';

                            $timeActual = $initTime->format('H:i:00') . '-' . $finishTime->format('H:i:59');
                            $bookingcell->text = $timeActual;
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $event->timestart = make_timestamp($initTime->format('Y'), $initTime->format('m'), $initTime->format('d'), $initTime->format('H'));

                            // Booking information - Message
                            $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' . $currentLab . '. ' . $practActual . '<br>';
                            $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("Y-m-d") . '<br>';
                            $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:i:s') . '-' . $finishTime->format('H:i:s') . '<br><br>';

                            // Booking information - Event
                            $event->description = $event->description . get_string('plant', 'ejsappbooking') . ': ' . $currentLab . '. ' . $practActual . '<br>';
                            $event->description = $event->description . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("Y-m-d") . '<br>';
                            $event->description = $event->description . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:i:s') . '-' . $finishTime->format('H:i:s');

                            // create the event on the calendar
                            calendar_event::create($event);
                        }
                        $i++;
                    }

                    // check message delivery
                    if (empty($CFG->messaging)) {
                        $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
                    }

                    // format and send the message by the Admin user
                    $format = FORMAT_HTML;
                    $cuser = $DB->get_record('user', array('id' => 2));
                    @message_post_message($cuser, $USER, $messagebody, $format);

                    // Booking information
                    $out .= '<p align="center"> <strong>' . get_string('bookinginfo', 'ejsappbooking') . '</strong></p>';
                    $out .= html_writer::table($bookingtable);
                    $out .= '<p align="center"> <strong>' . get_string('sending_message', 'ejsappbooking') . '</strong></p>';
               }
            }

        } else {
            // no date, show an error
            $out .= '<p align="center"><strong>' . get_string('selectdate', 'ejsappbooking') . '</strong></p>';
        }

    // Manage user´s bookings
    } else if ($Mybookingsbutton || $Mybookingsdelete) {

        $deletebutton = true;
        $bookingtable->head = array(' ', ' ', get_string('plant', 'ejsappbooking'), get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'));
        $username = $USER->username;

        // delete booking request
        if ($Mybookingsdelete) {

            // info - message
            $messagebody = get_string('deleteBooking', 'ejsappbooking') . '<br><br>';

            // check and delete bookings
            foreach ($booking as $book) {

                $record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $book));
                $labs = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
                $prac = $DB->get_record('remlab_manager_expsyst2pract', array('practiceid' => $record->practiceid, 'ejsappid'=>$record->ejsappid));
                $initTime = new DateTime($record->starttime);
                $error = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $book));
                $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' . $labs->name . '<br>';
                $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' . $initTime->format("d-m-y") . '<br>';
                $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' . $initTime->format('H:00:00') . '-' . $initTime->format('H:59:59') . '<br><br>';
                $time = make_timestamp($initTime->format('Y'), $initTime->format('m'), $initTime->format('d'), $initTime->format('H'));
                $event = $DB->get_record_sql('SELECT * FROM {event} WHERE userid = ? AND name = ? AND timestart = ?', array($USER->id, get_string('book_message', 'ejsappbooking') . ' '. $labs->name . '. ' . $prac->practiceintro, $time));

                // delete calendar´s event
                if ($event) {
                    $event = calendar_event::load($event->id);
                    $event->delete($deleterepeated = false);
                }
            }

            // check message delivery
            if (empty($CFG->messaging)) {
                $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
            }

            // format and send the message by the Admin user
            $format = FORMAT_HTML;
            $cuser = $DB->get_record('user', array('id' => 2));
            @message_post_message($cuser, $USER, $messagebody, $format);

            // Booking information
            $message = '<p align="center"> <strong>' . get_string('send_message', 'ejsappbooking') . '</strong></p>';
        }

        // show user´s bookings
        $events = $DB->get_records_sql("SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id WHERE a.username = ? AND DATE_FORMAT(a.starttime, '%Y-%m-%d') >= ?  ORDER BY a.starttime ASC", array($username, $today->format('Y-m-d')));
        $result = count($events);

        //  page´s configuration
        $bookingPage = 12;
        $pages = ceil($result / $bookingPage);
        $currentPage = 0;

        if (isset($page) || empty($page)) {
            $currentPage = $page;
        }

        if ($currentPage < 1) {
            $currentPage = 1;
        } else if ($currentPage > $pages) {
            $currentPage = $pages;
        }

        $initBook = ($currentPage - 1) * $bookingPage;

        // check bookings
        $sql = 'SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, a.valid, b.name, c.practiceintro FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id  INNER JOIN {remlab_manager_expsyst2pract} c ON a.practiceid = c.practiceid  WHERE a.ejsappid = c.ejsappid AND a.username = "' . $username . '" AND DATE_FORMAT(starttime, "%Y-%m-%d") >= "'. $today->format('Y-m-d') .'"ORDER BY a.starttime ASC LIMIT ' . $initBook . ', ' . $bookingPage . '';
        $events2 = $DB->get_records_sql($sql);
        $result2 = count($events2);


        // Exists
        if ($result != 0) {

            $i = 0;
            foreach ($events2 as $event) {

                $name = 'booking[' . $i . ']';
                $value = $event->id;

                $time = new DateTime($event->starttime);
                $timeEnd = new DateTime($event->endtime);
                $visible = array(null);
                
                if ($today->format("Y-m-d H:i") < $time->format("Y-m-d H:i") || $today->format("Y-m-d H:i") < $timeEnd->format("Y-m-d H:i")) {
                //if ($today->format("Y-m-d H:i:s") < $time->format("Y-m-d H:i:s") || $today->format("Y-m-d H:i:s") < $timeEnd->format("Y-m-d H:i:s")) {
                    $url = 'available.png';
                } else {
                    $url = 'busy.png';
                    if ($today->format("Y-m-d") == $time->format("Y-m-d"))
                        $visible = array('disabled' => 'disable');
                }

                $bookingtable->data[] = new html_table_row();

                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                if ($currentPage == 1)
                    $numpage = ($i + 1);
                else
                    $numpage = (($currentPage - 1) * $bookingPage) + ($i + 1);

                $bookingcell->text = ' ' . $numpage . ' ';
                $bookingtable->data[$i]->cells[] = $bookingcell;

                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                $bookingcell->text = html_writer::checkbox($name, $value, false, null, $visible);
                $bookingtable->data[$i]->cells[] = $bookingcell;

                //Add link to access the lab if the current time is within the booking slot
                $currentSlot = false;
                if ($today->format("Y-m-d H:i:s") > $time->format("Y-m-d H:i:s") && $today->format("Y-m-d H:i:s") < $timeEnd->format("Y-m-d H:i:s")) {
                    $currentSlot = true;
                }
                
                $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                if ($currentSlot){ //Add link to access the lab if the current time is within the booking slot
                    $bookingcell->text = "<a href='../ejsapp/view.php?n=" . $event->ejsappid . "'>" . $multilang->filter($event->name) . '. ' . $event->practiceintro . "</a>";
                } else {
                    $bookingcell->text = $multilang->filter($event->name) . '. ' . $event->practiceintro;
                }

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

            if ($result > $bookingPage) {

                $out .= '<div class="paginacion">';

                // show pagination
                for ($i = 1; $i <= $pages; $i++) {

                    if ($i == $currentPage) {
                        $out .= '&nbsp;&nbsp;&nbsp;<span class="pagina actual">' . $i . '</span>';
                    }

                    else if ($i == 1 || $i == $pages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)) {
                        $out .= '&nbsp;&nbsp;&nbsp;<a href="' . $baseurl->out() . '&amp;Mybookingsbutton=1&amp;page=' . $i . '" class="pagina">' . $i . '</a>';
                    }
                }
                $out .= '</div>';
            }
            $out .= '<input id="Mybookingsdelete" name="Mybookingsdelete" value="0" type="hidden">';

        } else {
            // No bookings
            $deletebutton = false;
            $out .= '<p><strong>' . get_string('nobooking', 'ejsappbooking') . '</strong></p>';
        }
        // End - Manage user´s bookings

    // Show slots in selected day
    } else {
        $practiceintro = $DB->get_field('remlab_manager_expsyst2pract', 'practiceintro', array('ejsappid'=>$labid, 'practiceid'=>$practid));
        $labids = $DB->get_fieldset_select('remlab_manager_expsyst2pract', 'ejsappid', 'practiceintro = :practiceintro', array('practiceintro'=>$practiceintro));
        $practiceids = $DB->get_fieldset_select('remlab_manager_expsyst2pract', 'practiceid', 'practiceintro = :practiceintro', array('practiceintro'=>$practiceintro));
        $events = array();
        $i = 0;
        foreach($labids as $labid) {
            $practid = $practiceids[$i];
            $temp_events = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE DATE_FORMAT(starttime, '%Y-%m-%d') = ? AND ejsappid = ? AND practiceid = ? ORDER BY starttime ASC", array($sDate->format('Y-m-d'), $labid, $practid));
            if (!empty($temp_events)) $events[] = $temp_events;
            $i++;
        }

        $slotDuration = $DB->get_field('remlab_manager_conf', 'slotsduration', array('practiceintro'=>$practiceintro) );
        $slotMultiplo = 1;
        $min = 60;
        switch ($slotDuration){
            case 0: //60 min
                $slotMultiplo = 1;
                $min = 0;
                break;
            case 1: //30 min
                $slotMultiplo = 2;
                $min = 30;
                break;
            case 2: //15 min
                $slotMultiplo = 4;
                $min = 15;
                break;
            case 3: //5 min
                $slotMultiplo = 12;
                $min = 5;
                break;
            case 4: //2 min
                $slotMultiplo = 30;
                $min = 2;
                break;
            
        }
        $height = 6*$slotMultiplo;
        
        for ($i = 0; $i < $height; $i++) {
            $bookingtable->data[] = new html_table_row();

            for ($j = 0; $j < 4; $j++) {
                $index = ($j * $height) + $i;
                
                $hourStart = ($index - $index % $slotMultiplo)/$slotMultiplo;
                $hourEnd = ($index +1 - ($index+1) % $slotMultiplo)/$slotMultiplo;
                $minStart = $index % $slotMultiplo * $min;
                $minEnd = ($index+1) % $slotMultiplo * $min;

                $initTime = new DateTime('2000-01-01');
                $finishTime = new DateTime('2000-01-01');
                $initTime->add(new DateInterval('PT' . $hourStart . 'H' . $minStart . 'M'));
                $finishTime->add(new DateInterval('PT' . $hourEnd . 'H' . $minEnd . 'M'));

                $name = 'booking[' . $index . ']';
                $value = $index;
                $tag = $initTime->format('H:i') . ' - ' . $finishTime->format('H:i');
                $visible = array(null);
                $checked = false;
                $url = 'available.png';

                // hours not valid
                //$actual = new DateTime("now");
                if ($previous_day) {
                    $visible = array('disabled' => 'disable');
                    $checked = false;
                    $url = 'no_available.png';
                } else if (($finishTime->format('H:i') <= $today->format('H:i')) && ($initTime->format('H:i') < $today->format('H:i')) && $now) {
                    $visible = array('disabled' => 'disable');
                    $checked = false;
                    $url = 'no_available.png';
                }

                // reserved slot
                foreach ($events as $event) {
                    foreach ($event as $bookinginfo) {
                        $date = $bookinginfo->starttime;
                        if ($initTime->format('H:i') == date("H:i", strtotime($date))) {
                            $checked = true;
                            $visible = array('disabled' => 'disable');
                            $url = 'busy.png';
                        }
                    }
                }
                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                $bookingcell->text = '<img id=bookimg' . $value . ' src="' . $CFG->wwwroot . '/mod/ejsappbooking/pix/' . $url . '" width="12px" height="12px">&nbsp;' . html_writer::checkbox($name, $value, $checked, $tag, $visible);
                $bookingtable->data[$i]->cells[] = $bookingcell;
            }
        }
        $out .=  '<p align="center"><strong>' . get_string('availability', 'ejsappbooking') . ' ' . $sDate->format('d-m-Y') . '</strong></p>';
        $out .= html_writer::table($bookingtable);
        $out .= '<br><p align="center"><button name="bookingbutton" value="1" type="submit">' . get_string('book', 'ejsappbooking') . '</button></p>';
    }

    // </Program logic>

    $out .= html_writer::end_tag('div');

    // hidden parameters
    $out .= '<input id="id" name="id" value=' . $id . ' type="hidden">';
    $out .= '<input id="selectdate" name="selectDay" value="' . $selectDay . '" type="hidden">';

    // show my bookings
     if (!$Mybookingsbutton) {
        $out .= '<p align="center"><button name="Mybookingsbutton" value="1" type="submit">' . get_string('mybookings', 'ejsappbooking') . '</button></p>';
     }

    $out .= html_writer::end_tag('form');

    // delete button
    if ($deletebutton) {
        $out .= '<p align="center"><strong>' . get_string('selectbooking', 'ejsappbooking') . '</strong>&nbsp;';
        $out .= '<button class="yui3-button btn-show">' . get_string('delete', 'ejsappbooking') . '</button></p>';
        if($message)
            $out .= $message;
    }

    // show the slots
    $table = new html_table();
    $table->attributes['class'] = 'userinfobox';
    $row = new html_table_row();
    $row->cells[0] = new html_table_cell();
    $row->cells[0]->attributes['class'] = 'center';
    $row->cells[0]->text = $out;
    $table->data[0] = $row;
    echo html_writer::table($table);

    echo html_writer::end_tag('div');

    echo $OUTPUT->box_end();

}

// Finish the page
echo $OUTPUT->footer();