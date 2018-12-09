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
 * Prints a particular instance of ejsappbooking
 *
 * @package    mod_ejsappbooking
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

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$n = optional_param('n', 0, PARAM_INT); // ...ejsappbooking instance ID - it should be named as the first character of the module.
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$practid = optional_param('practid', 0, PARAM_INT); // Selected practice.
$booking = optional_param_array('booking', array(), PARAM_INT); // Bookings selected to save or delete.
$bookingbutton = optional_param('bookingbutton', 0, PARAM_RAW); // Controls the functionality for saving bookings.
$mybookingsbutton = optional_param('Mybookingsbutton', 0, PARAM_RAW); // Controls the functionality of showing the bookings.
$mybookingsdelete = optional_param('Mybookingsdelete', 0, PARAM_RAW); // Controls functionality to delete the selected bookings.
$selectday = optional_param('selectDay', 0, PARAM_RAW); // Selected day.
$page = optional_param('page', 0, PARAM_INT); // Which page to show.

if ($id) {
    $cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $ejsappbooking->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ejsappbooking', $ejsappbooking->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

if ($CFG->version < 2013111899) { // Moodle 2.6 or inferior.
    add_to_log($course->id, 'ejsappbooking', 'view', "view.php?id={$cm->id}", $ejsappbooking->name, $cm->id);
} else {
    $event = \mod_ejsappbooking\event\ejsappbooking_viewed::create(array(
        'objectid' => $ejsappbooking->id,
        'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('ejsappbooking', $ejsappbooking);
    $event->trigger();
}

if ($CFG->dbtype == "mysql")
    $date_convert_func="DATE_FORMAT";
else if ($CFG->dbtype == "pgsql")
    $date_convert_func="TO_CHAR";

// Print the page header.
$PAGE->set_title(format_string($ejsappbooking->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url('/mod/ejsappbooking/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
if ($CFG->version < 2016090100) {
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'ejsappbooking'));
}

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui', 'core');
$PAGE->requires->jquery_plugin('ui-css', 'core');

$PAGE->requires->string_for_js('messageDelete', 'ejsappbooking');
$PAGE->requires->string_for_js('book_message', 'ejsappbooking');
$PAGE->requires->string_for_js('cancel', 'ejsappbooking');
// $PAGE->requires->js('/mod/ejsappbooking/module.js');
$PAGE->requires->js_call_amd('mod_ejsappbooking/ui','init');

$CFG->cachejs = false;

// Output starts here.
echo $OUTPUT->header();

if ($ejsappbooking->intro) { // If some text was written, show the intro.
    echo $OUTPUT->box(format_module_intro('ejsappbooking', $ejsappbooking, $cm->id), 'generalbox mod_introbox',
        'ejsappbookingintro');
}

// Get the remote laboratories in which the user is authorized to make bookings.
$remlabs = $DB->get_records_sql("SELECT DISTINCT (a.id), a.name FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} 
b ON a.id = b.ejsappid WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1",
    array($USER->id, $course->id));

if (!$remlabs) {
    // No labs.
    echo $OUTPUT->box(get_string('no_labs_rem', 'ejsappbooking'));
} else { // At least one remote lab.

    // Obtain the name of the remote lab considering the language filter.
    $i = 1;
    $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
    foreach ($remlabs as $remlab) {
        $labname[$remlab->id] = $multilang->filter($remlab->name);
        if ($i == 1 && $labid == 0) {
            $labid = $remlab->id;
        }
        $i++;
    }

    $today = new DateTime("now");

    // Check the selected day.
    if (!$selectday) {
        $sdate = $today;
        $previousday = false;
        $now = true;
    } else {
        $sdate = DateTime::createFromFormat("Y-m-d", $selectday);
        // Used to clear days in the calendar.
        if ($today->format("Y-m-d") == $sdate->format("Y-m-d")) {
            $previousday = false;
            $now = true;
        } else if ($today->format("Y-m-d") < $sdate->format("Y-m-d")) {
            $previousday = false;
            $now = false;
        } else {
            $previousday = true;
            $now = false;
        }
    }

    // Start building the website.
    $baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('makereservation', 'ejsappbooking'));
    $iconurl = $CFG->wwwroot . '/mod/ejsappbooking/pix/selected.png';

    // Check the configuration of the lab (whether it is active or not).
    $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro', array('ejsappid' => $labid));
    $conflabs = $DB->get_record('block_remlab_manager_conf', array('practiceintro' => $practiceintro));
    if ($conflabs->active) {
        $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_success_8bit.png';
        $plantstateinfo = get_string('active_plant', 'ejsappbooking');
    } else {
        $plantico = $CFG->wwwroot . '/mod/ejsappbooking/pix/icon_error_8bit.png';
        $plantstateinfo = get_string('inactive_plant', 'ejsappbooking');
    }

    // User data and control the calendar.
    echo html_writer::start_tag('div', array('id' => 'container', 'align' => 'center'));
    $userpicture = $OUTPUT->user_picture($USER, array('size' => 100, 'courseid' => $course->id));
    $userfullname = $OUTPUT->container('<p align="center">' . fullname($USER,
            has_capability('moodle/site:viewfullnames', $context)). '</strong></p>', 'username');
    $activedate = $OUTPUT->container('<p align="center"> <strong> <span id="ActiveDate">' . $sdate->format("Y-m-d") .
        '</span> </strong></p>');
    $plantname = $OUTPUT->container('<p align="center">'. $plantstateinfo .'</p>');
    $plantstate = $OUTPUT->container('<p align="center"><img src="' . $plantico. '" width="44px" height="44px"></p>');
    $calendar = $OUTPUT->container('<div id="datepicker"></div>');    
/*    
    $selectdate = html_writer::start_tag('div', array('id' => 'control', 'align' => 'center'));
    $selectdate .= html_writer::tag('button', '&lt;' . get_string('iyear', 'ejsappbooking'),
        array('class' => 'booking_button', 'id' => 'subyear'));
    $selectdate .= html_writer::tag('button', '&lt;' . get_string('imonth','ejsappbooking'),
        array('class' => 'booking_button', 'id' => 'submonth'));
    $selectdate .= html_writer::tag('button', get_string('imonth','ejsappbooking') . '&gt;',
        array('class' => 'booking_button', 'id' => 'addmonth'));
    $selectdate .= html_writer::tag('button', get_string('iyear', 'ejsappbooking') . '&gt;',
        array('class' => 'booking_button', 'id' => 'addyear'));
    $selectdate .= html_writer::end_tag('div') . '<br>';
*/    
    $table = new html_table();
    $table->attributes['class'] = 'userinfobox';
    $row = new html_table_row();
    $row->cells[0] = new html_table_cell();
    $row->cells[0]->attributes['class'] = 'left';
    $row->cells[0]->text = $userpicture .''. $userfullname .''. $activedate .''. $plantname .''. $plantstate . '';
    $row->cells[1] = new html_table_cell();
    $row->cells[1]->attributes['class'] = 'center';
    $row->cells[1]->text = $calendar ;    
//  $row->cells[1]->text = $calendar .''. $selectdate;
    $row->cells[2] = new html_table_cell();
    $row->cells[2]->attributes['class'] = 'right';
    $row->cells[2]->text = '';
    $table->data[0] = $row;
    echo html_writer::table($table);

    // Select labs.
    $out = html_writer::start_tag('form', array('id' => 'bookingform', 'method' => 'get', 'action' => $baseurl));
    $out .= html_writer::start_tag('div', array('id' => 'controls', 'align' => 'center'));
    $out .= get_string('rem_lab_selection', 'ejsappbooking');
    $out .= '&nbsp;&nbsp;<select name="labid" class="booking_select" data-previousindex="0" onchange="this.form.submit()"> ';
    $currentlab = '';
    $i = 1;
    foreach ($remlabs as $remlab) {
        $labname[$remlab->id] = $remlab->name;
        if ($i == 1 && $labid == 0) {
            $labid = $remlab->id;
        }
        $out .= '<option value="' . $remlab->id . '"';

        if ($labid == $remlab->id) {
            $out .= 'selected="selected"';
            $currentlab = $labname[$remlab->id];
        }
        $out .= '>' . $multilang->filter($labname[$remlab->id]) . '</option>';
        $i++;
    }
    $out .= '</select>';

    // Select practices.
    $practices = $DB->get_records_sql("SELECT id, ejsappid, practiceid, practiceintro FROM {block_remlab_manager_exp2prc} 
WHERE ejsappid = ? ", array($labid));
    $i = 1;
    foreach ($practices as $practice) {
        $labname[$practice->id] = $multilang->filter($practice->practiceintro);
        if ($i == 1 && $practid == 0) {
            $practid = $practice->practiceid;
        }
        $i++;
    }
    $selectedpractice = '';
    $out .= '<br>';
    $out .= get_string('rem_prac_selection', 'ejsappbooking');
    $out .= '&nbsp;&nbsp;<select name="practid" class="booking_select" data-previousindex="0" onchange="this.form.submit()"> ';
    $i = 1;
    foreach ($practices as $practice) {
        $labname[$practice->practiceid] = $practice->practiceintro;
        if ($i == 1 && $practid == 0) {
            $practid = $practice->practiceid;
        }
        $out .= '<option value="' . $practice->practiceid . '"';

        if ($practid == $practice->practiceid) {
            $out .= 'selected="selected"';
            $selectedpractice = $labname[$practice->practiceid];
        }
        $out .= '>' . $multilang->filter($labname[$practice->practiceid]) . '</option>';
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

    // Init of program logic.

    // Reservation functionality.
    if ($bookingbutton) {

        // Checks if there are reservations on request.
        if ($booking) {

            $bookingtable->head = array(get_string('plant', 'ejsappbooking'), get_string('date',
                'ejsappbooking'), get_string('hour', 'ejsappbooking'));

            $i = 0;

            // Retrieving user´s bookings at the DB.
            $useraccess = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE username = ? 
AND ejsappid = ? ORDER BY starttime ASC", array($USER->username, $labid));
            $userbooks = count($useraccess);

            // Bookings at the request.
            $prebooking = count($booking);

            $message = false;
            // Set save.
            $save = 1;
            $total = $prebooking + $userbooks;
            $day = $sdate->format('w');

            if ($day == 0) {
                $monday = 6;
                $sunday = 0;
            } else {
                $monday = $day - 1;
                $sunday = 7 - $day;
            }

            $dmonday = strtotime('-' . $monday . 'day', strtotime($sdate->format('Y-m-d')));
            $dsunday = strtotime('+' . $sunday . 'day', strtotime($sdate->format('Y-m-d')));
            $dmonday = date('Y-m-d', $dmonday);
            $dsunday = date('Y-m-d', $dsunday);

            if ($dmonday < $today->format('Y-m-d')) {
                $dmonday = $today->format('Y-m-d');
            }

            // Determine user´s bookings of the week.
            $weekaccesses = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} 
WHERE ".$date_convert_func."(starttime, '%Y-%m-%d') >= ? AND ".$date_convert_func."(starttime, '%Y-%m-%d') <= ? AND username = ? AND 
ejsappid = ? ORDER BY starttime ASC", array($dmonday, $dsunday, $USER->username, $labid));
            $weekbooks = count($weekaccesses);

            $total2 = $prebooking + $weekbooks;

            // Check restrictions.
            if ($total > $conflabs->totalslots) {
                $save = 0;
                $number = $conflabs->totalslots - $userbooks;
                $message = get_string('totalslots', 'ejsappbooking'). ': ' . $conflabs->totalslots;
                if ($number > 0) {
                    $message .= '. ' . get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
                }
            } else if ($total2 > $conflabs->weeklyslots) {
                $save = 0;
                $message = get_string('weeklyslots', 'ejsappbooking') . '. ';
                $number = $conflabs->weeklyslots - $weekbooks;
                if ($number > 0) {
                    $message .= get_string('availability_booking', 'ejsappbooking') . ': ' . $number;
                }
            } else {
                $i = $prebooking;

                if ($userbooks == 0) {
                    if ($i > $conflabs->dailyslots) {
                        $save = 0;
                        $message = get_string('dailyslots', 'ejsappbooking')  . ': ' . $conflabs->dailyslots;
                    }
                } else {
                    foreach ($useraccess as $access) {
                        $convert = date("Y-m-d", strtotime($access->starttime));

                        if ($convert == $sdate->format("Y-m-d")) {
                            $i++;
                        }

                        if ($i > $conflabs->dailyslots) {
                            $message = get_string('dailyslots', 'ejsappbooking') . ': ' . $conflabs->dailyslots;
                            $number = $conflabs->dailyslots - $i;
                            if ($number < 0) {
                                $number = 0;
                            }
                            $save = 0;
                            break;
                        }
                    }
                }
            }

            // If there are any restrictions, displays a message.
            if ($message) {
                $out .= '<p align="center"><strong>' . $message . '</strong></p><br>';
            } else {
                // Save the booking.
                if ($save) {
                    $i = 0;
                    // Booking info.
                    $messagebody = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';

                    // Bookings.
                    foreach ($booking as $book) {

                        $event = new stdClass();
                        $event->name = get_string('book_message', 'ejsappbooking') . ' '. $currentlab . '. ' . $selectedpractice;
                        $event->description = get_string('bookinginfo', 'ejsappbooking') . '<br><br>';
                        $event->groupid = 0;
                        $event->courseid = 0;
                        $event->userid = $USER->id;
                        $event->eventtype = 'user';

                        $slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration',
                            array('practiceintro' => $practiceintro) );
                        $slotsperhour = 1;
                        $min = 0;
                        switch ($slotduration){
                            case 0: // 60 min.
                                $slotsperhour = 1;
                                $min = 0;
                                break;
                            case 1: // 30 min.
                                $slotsperhour = 2;
                                $min = 30;
                                break;
                            case 2: // 15 min.
                                $slotsperhour = 4;
                                $min = 15;
                                break;
                            case 3: // 5 min.
                                $slotsperhour = 12;
                                $min = 5;
                                break;
                            case 4: // 2 min.
                                $slotsperhour = 30;
                                $min = 2;
                                break;
                        }
                        $starthour = ($book - $book % $slotsperhour) / $slotsperhour;
                        $endhour = ($book + 1 - ($book + 1) % $slotsperhour) / $slotsperhour;
                        $startmin = $book % $slotsperhour * $min;
                        $endmin = ($book + 1) % $slotsperhour * $min - 1;
                        if ($endmin < 0) {
                            $endhour--;
                            $endmin += 60;
                        }
                        $event->timeduration = 60 * ($endmin - $startmin);
                        $startdate = $sdate->format("Y-m-d") . ' ' . $starthour . ':' . $startmin . ':00';
                        $enddate = $sdate->format("Y-m-d") . ' ' . $endhour . ':' . $endmin . ':59';

                        $bk = new stdClass();
                        $bk->username = $USER->username;
                        $bk->ejsappid = $labid;
                        $bk->practiceid = $practid;
                        $bk->starttime = date("Y-m-d H:i:00", strtotime($startdate));
                        $bk->endtime = date("Y-m-d H:i:59", strtotime($enddate));
                        $bk->valid = 1;
                        $inittime = new DateTime($bk->starttime);
                        $finishtime = new DateTime($bk->endtime);

                        // Check if the book exists.
                        if ($DB->record_exists('ejsappbooking_remlab_access', array('starttime' => $bk->starttime,
                            'ejsappid' => $labid, 'practiceid' => $practid))) {

                            // Report that the slot is occupied.
                            $bookingtable->data[] = new html_table_row();

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = get_string('messageOccupied', 'ejsappbooking');
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = $inittime->format("Y-m-d");
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';

                            $bookingcell->text = $inittime->format('H:i:00') . '-' . $finishtime->format('H:i:59');
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                        } else {

                            // Save in database.
                            $identificador = $DB->insert_record('ejsappbooking_remlab_access', $bk, true);

                            $bookingtable->data[] = new html_table_row();

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = $multilang->filter($currentlab) . '. ' . $selectedpractice;
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';
                            $bookingcell->text = $inittime->format("Y-m-d");
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $bookingcell = new html_table_cell();
                            $bookingcell->attributes['class'] = 'center';

                            $bookingcell->text = $inittime->format('H:i:00') . '-' . $finishtime->format('H:i:59');
                            $bookingtable->data[$i]->cells[] = $bookingcell;

                            $event->timestart = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                                $inittime->format('d'), $inittime->format('H'));

                            // Booking information - Message.
                            $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' .
                                $multilang->filter($currentlab) . '. ' . $selectedpractice . '<br>';
                            $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' .
                                $inittime->format("Y-m-d") . '<br>';
                            $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' .
                                $inittime->format('H:i:s') . '-' . $finishtime->format('H:i:s') . '<br><br>';

                            // Booking information - Event.
                            $event->description = $event->description . get_string('plant', 'ejsappbooking') .
                                ': ' . $multilang->filter($currentlab) . '. ' . $selectedpractice . '<br>';
                            $event->description = $event->description . get_string('date', 'ejsappbooking') .
                                ': ' . $inittime->format("Y-m-d") . '<br>';
                            $event->description = $event->description . get_string('hour', 'ejsappbooking') .
                                ': ' . $inittime->format('H:i:s') . '-' . $finishtime->format('H:i:s');

                            // Create the event on the calendar.
                            calendar_event::create($event);

                        }
                        $i++;
                    }

                    // Check message delivery.
                    if (empty($CFG->messaging)) {
                        $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
                    }

                    // Format and send the message by the Admin user.
                    $format = FORMAT_HTML;
                    $cuser = $DB->get_record('user', array('id' => 2));
                    @message_post_message($cuser, $USER, $messagebody, $format);

                    // Booking information.
                    $out .= '<p align="center"> <strong>' . get_string('bookinginfo', 'ejsappbooking') . '</strong></p>';
                    $out .= html_writer::table($bookingtable);
                    $out .= '<p align="center"> <strong>' . get_string('sending_message', 'ejsappbooking') . '</strong></p>';
                }
            }

        } else {
            // If no date was selected, show an error.
            $out .= '<p align="center"><strong>' . get_string('selectdate', 'ejsappbooking') . '</strong></p>';
        }

    } else if ($mybookingsbutton || $mybookingsdelete) {
        // Manage user´s bookings.

        $deletebutton = true;
        $bookingtable->head = array(' ', ' ', get_string('plant', 'ejsappbooking'),
            get_string('date', 'ejsappbooking'), get_string('hour', 'ejsappbooking'));
        $username = $USER->username;

        // Delete booking request.
        if ($mybookingsdelete) {

            // Info - message.
            $messagebody = get_string('deleteBooking', 'ejsappbooking') . '<br><br>';

            // Check and delete bookings.
            foreach ($booking as $book) {
                $record = $DB->get_record('ejsappbooking_remlab_access', array('id' => $book));
                $lab = $DB->get_record('ejsapp', array('id' => $record->ejsappid));
                $prac = $DB->get_record('block_remlab_manager_exp2prc', array('practiceid' => $record->practiceid,
                    'ejsappid' => $record->ejsappid));
                $inittime = new DateTime($record->starttime);
                $error = $DB->delete_records('ejsappbooking_remlab_access', array('id' => $book));
                $messagebody = $messagebody . get_string('plant', 'ejsappbooking') . ': ' .
                    $lab->name . '<br>';
                $messagebody = $messagebody . get_string('date', 'ejsappbooking') . ': ' .
                    $inittime->format("d-m-y") . '<br>';
                $messagebody = $messagebody . get_string('hour', 'ejsappbooking') . ': ' .
                    $inittime->format('H:00:00') . '-' . $inittime->format('H:59:59') . '<br><br>';
                $time = make_timestamp($inittime->format('Y'), $inittime->format('m'),
                    $inittime->format('d'), $inittime->format('H'));
                $event = $DB->get_record_sql('SELECT * FROM {event} WHERE userid = ? AND name = ? AND timestart = ?',
                    array($USER->id, get_string('book_message', 'ejsappbooking') . ' ' . $lab->name .
                        '. ' . $prac->practiceintro, $time));

                // Delete calendar´s event.
                if ($event) {
                    $event = calendar_event::load($event->id);
                    $event->delete($deleterepeated = false);
                }
            }

            // Check message delivery.
            if (empty($CFG->messaging)) {
                $out .= '<p align="center"> <strong>' . get_string('messagingdisabled', 'message') . '</strong></p>';
            }

            // Format and send the message by the Admin user.
            $format = FORMAT_HTML;
            $cuser = $DB->get_record('user', array('id' => 2));
            @message_post_message($cuser, $USER, $messagebody, $format);

            // Booking information.
            $message = '<p align="center"> <strong>' . get_string('send_message', 'ejsappbooking') . '</strong></p>';
        }

        // Show user´s bookings.
        $events = $DB->get_records_sql("SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, 
a.valid, b.name FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id WHERE a.username = ? AND 
".$date_convert_func."(a.starttime, '%Y-%m-%d') >= ?  ORDER BY a.starttime ASC", array($username, $today->format('Y-m-d')));
        $result = count($events);

        // Page´s configuration.
        $bookingpage = 12;
        $pages = ceil($result / $bookingpage);
        $currentpage = 0;

        if (isset($page) || empty($page)) {
            $currentpage = $page;
        }

        if ($currentpage < 1) {
            $currentpage = 1;
        } else if ($currentpage > $pages) {
            $currentpage = $pages;
        }

        $initbook = ($currentpage - 1) * $bookingpage;

        // Check bookings.

        $events2 = $DB->get_records_sql("SELECT a.id, a.username, a.ejsappid, a.practiceid, a.starttime, a.endtime, 
        a.valid, b.name, c.practiceintro FROM {ejsappbooking_remlab_access} a INNER JOIN {ejsapp} b ON a.ejsappid = b.id  
        INNER JOIN {block_remlab_manager_exp2prc} c ON a.practiceid = c.practiceid  WHERE a.ejsappid = c.ejsappid AND 
        a.username = ? AND ". $date_convert_func."(starttime, '%Y-%m-%d') >= ? ORDER BY a.starttime  ASC LIMIT ? OFFSET ? ",
            array($username, $today->format('Y-m-d'), $bookingpage , $initbook)
        );
        
        $result2 = count($events2);

        // Exists.
        if ($result != 0) {

            $i = 0;
            foreach ($events2 as $event) {

                $name = 'booking[' . $i . ']';
                $value = $event->id;

                $time = new DateTime($event->starttime);
                $timeend = new DateTime($event->endtime);
                $visible = array(null);

                if ($today->format("Y-m-d H:i") < $time->format("Y-m-d H:i") ||
                    $today->format("Y-m-d H:i") < $timeend->format("Y-m-d H:i")) {
                    $url = 'available.png';
                } else {
                    $url = 'busy.png';
                    if ($today->format("Y-m-d") == $time->format("Y-m-d")) {
                        $visible = array('disabled' => 'disable');
                    }
                }

                $bookingtable->data[] = new html_table_row();

                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                if ($currentpage == 1) {
                    $numpage = ($i + 1);
                } else {
                    $numpage = (($currentpage - 1) * $bookingpage) + ($i + 1);
                }
                $bookingcell->text = ' ' . $numpage . ' ';
                $bookingtable->data[$i]->cells[] = $bookingcell;

                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                $bookingcell->text = html_writer::checkbox($name, $value, false, null, $visible);
                $bookingtable->data[$i]->cells[] = $bookingcell;

                // Add link to access the lab if the current time is within the booking slot.
                $currentslot = false;
                if ($today->format("Y-m-d H:i:s") > $time->format("Y-m-d H:i:s") &&
                    $today->format("Y-m-d H:i:s") < $timeend->format("Y-m-d H:i:s")) {
                    $currentslot = true;
                }

                $multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));
                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                if ($currentslot) {
                    // Add link to access the lab if the current time is within the booking slot.
                    $bookingcell->text = "<a href='../ejsapp/view.php?n=" . $event->ejsappid . "'>" .
                        $multilang->filter($event->name) . '. ' . $event->practiceintro . "</a>";
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
                $bookingcell->text = $time->format("H:i") . '-' . $time2->format("H:i");;
                $bookingtable->data[$i]->cells[] = $bookingcell;

                $i++;
            }

            $out .= '<p align="center"> <strong>' . get_string('mybookings', 'ejsappbooking') . '</strong></p>';
            $out .= html_writer::table($bookingtable);

            if ($result > $bookingpage) {

                $out .= '<div class="paginacion">';

                // Show pagination.
                for ($i = 1; $i <= $pages; $i++) {

                    if ($i == $currentpage) {
                        $out .= '&nbsp;&nbsp;&nbsp;<span class="pagina actual">' . $i . '</span>';
                    } else if ($i == 1 || $i == $pages || ($i >= $currentpage - 2 && $i <= $currentpage + 2)) {
                        $out .= '&nbsp;&nbsp;&nbsp;<a href="' . $baseurl->out() . '&amp;Mybookingsbutton=1&amp;page=' .
                            $i . '" class="pagina">' . $i . '</a>';
                    }
                }
                $out .= '</div>';
            }
            $out .= '<input id="Mybookingsdelete" name="Mybookingsdelete" value="0" type="hidden">';

        } else {
            // No bookings.
            $deletebutton = false;
            $out .= '<p><strong>' . get_string('nobooking', 'ejsappbooking') . '</strong></p>';
        }
        // End - Manage user´s bookings.

    } else {
        // Show slots in selected day.

        $practiceintro = $DB->get_field('block_remlab_manager_exp2prc', 'practiceintro',
            array('ejsappid' => $labid, 'practiceid' => $practid));
        $labids = $DB->get_fieldset_select('block_remlab_manager_exp2prc', 'ejsappid',
            'practiceintro = :practiceintro', array('practiceintro' => $practiceintro));
        $practiceids = $DB->get_fieldset_select('block_remlab_manager_exp2prc', 'practiceid',
            'practiceintro = :practiceintro', array('practiceintro' => $practiceintro));
        $events = array();
        $i = 0;
        foreach ($labids as $labid) {
            $practid = $practiceids[$i];
            $tempevents = $DB->get_records_sql("SELECT starttime FROM {ejsappbooking_remlab_access} WHERE 
".$date_convert_func."(starttime, '%Y-%m-%d') = ? AND ejsappid = ? AND practiceid = ? ORDER BY starttime ASC",
                array($sdate->format('Y-m-d'), $labid, $practid));
            if (!empty($tempevents)) {
                $events[] = $tempevents;
            }
            $i++;
        }

        $slotduration = $DB->get_field('block_remlab_manager_conf', 'slotsduration', array('practiceintro' => $practiceintro));
        $slotsperhour = 1;
        $min = 60;
        switch ($slotduration) {
            case 0: // 60 min.
                $slotsperhour = 1;
                $min = 0;
                break;
            case 1: // 30 min.
                $slotsperhour = 2;
                $min = 30;
                break;
            case 2: // 15 min.
                $slotsperhour = 4;
                $min = 15;
                break;
            case 3: // 5 min.
                $slotsperhour = 12;
                $min = 5;
                break;
            case 4: // 2 min.
                $slotsperhour = 30;
                $min = 2;
                break;
        }
        $height = 6 * $slotsperhour;

        for ($i = 0; $i < $height; $i++) {
            $bookingtable->data[] = new html_table_row();

            for ($j = 0; $j < 4; $j++) {
                $index = ($j * $height) + $i;

                $starthour = ($index - $index % $slotsperhour) / $slotsperhour;
                $endhour = ($index + 1 - ($index + 1) % $slotsperhour) / $slotsperhour;
                $startmin = $index % $slotsperhour * $min;
                $endmin = ($index + 1) % $slotsperhour * $min;

                $inittime = new DateTime('2000-01-01');
                $finishtime = new DateTime('2000-01-01');
                $inittime->add(new DateInterval('PT' . $starthour . 'H' . $startmin . 'M'));
                $finishtime->add(new DateInterval('PT' . $endhour . 'H' . $endmin . 'M'));

                $name = 'booking[' . $index . ']';
                $value = $index;
                $tag = $inittime->format('H:i') . ' - ' . $finishtime->format('H:i');
                $visible = array(null);
                $checked = false;
                $url = 'available.png';

                // Hours not valid.
                if ($previousday) {
                    $visible = array('disabled' => 'disable');
                    $checked = false;
                    $url = 'no_available.png';
                } else if (($finishtime->format('H:i') <= $today->format('H:i')) &&
                    ($inittime->format('H:i') < $today->format('H:i')) && $now) {
                    $visible = array('disabled' => 'disable');
                    $checked = false;
                    $url = 'no_available.png';
                }

                // Reserved slot.
                foreach ($events as $event) {
                    foreach ($event as $bookinginfo) {
                        $date = $bookinginfo->starttime;
                        if ($inittime->format('H:i') == date("H:i", strtotime($date))) {
                            $checked = true;
                            $visible = array('disabled' => 'disable');
                            $url = 'busy.png';
                        }
                    }
                }
                $bookingcell = new html_table_cell();
                $bookingcell->attributes['class'] = 'center';
                $bookingcell->text = '<img id=bookimg' . $value . ' src="' . $CFG->wwwroot . '/mod/ejsappbooking/pix/' .
                    $url . '" width="12px" height="12px">&nbsp;' . html_writer::checkbox($name, $value, $checked, $tag, $visible);
                $bookingtable->data[$i]->cells[] = $bookingcell;
            }
        }
        $out .= '<p align="center"><strong>' . get_string('availability', 'ejsappbooking') . ' ' .
            $sdate->format('d-m-Y') . '</strong></p>';
        $out .= html_writer::table($bookingtable);
        $out .= '<br><p align="center"><button name="bookingbutton" class="booking_button" value="1" type="submit">' .
            get_string('book', 'ejsappbooking') . '</button></p>';
    }

    // End of program logic.
    $out .= html_writer::end_tag('div');

    // Hidden parameters.
    $out .= '<input id="id" name="id" value=' . $id . ' type="hidden">';
    $out .= '<input id="selectdate" name="selectDay" value="' . $selectday . '" type="hidden">';

    // Show my bookings.
    if (!$mybookingsbutton) {
        $out .= '<p align="center"><button name="Mybookingsbutton" class="booking_button" value="1" type="submit">' .
            get_string('mybookings', 'ejsappbooking') . '</button></p>';
    }

    $out .= html_writer::end_tag('form');

    // Delete button.
    if ($deletebutton) {
        $out .= '<p align="center"><strong>' . get_string('selectbooking', 'ejsappbooking') .
            '</strong>&nbsp;';
        $out .= '<button class="yui3-button btn-show">' . get_string('delete', 'ejsappbooking') .
            '</button></p>';
        if ($message) {
            $out .= $message;
        }
    }

    // Show the slots.
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

// Finish the page.
echo $OUTPUT->footer();