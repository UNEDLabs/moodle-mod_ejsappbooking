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
//require_once(dirname(__FILE__) . '/locallib.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

//require_once($CFG->libdir . '/tablelib.php');
//require_once($CFG->libdir . '/filelib.php');
//require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->libdir . '/moodlelib.php');
// require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$n = optional_param('n', 0, PARAM_INT); // ...ejsappbooking instance ID - it should be named as the first character of the module.
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$practid = optional_param('practid', 0, PARAM_INT); // Selected practice.
//$booking = optional_param_array('booking', array(), PARAM_INT); // Bookings selected to save or delete.
//$bookingbutton = optional_param('bookingbutton', 0, PARAM_RAW); // Controls the functionality for saving bookings.
//$mybookingsbutton = optional_param('Mybookingsbutton', 0, PARAM_RAW); // Controls the functionality of showing the bookings.
//$mybookingsdelete = optional_param('Mybookingsdelete', 0, PARAM_RAW); // Controls functionality to delete the selected bookings.
//$selectday = optional_param('selectDay', 0, PARAM_RAW); // Selected day.
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
profile_load_data($USER); // user profile load

$context = context_module::instance($cm->id);
$multilang = new filter_multilang($context, array('filter_multilang_force_old' => 0));

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

$PAGE->requires->js_call_amd('mod_ejsappbooking/ui','init', array($CFG->wwwroot . '/mod/ejsappbooking/controllers'));
$PAGE->requires->css('/mod/ejsappbooking/styles/ui.css');

$CFG->cachejs = false;

// Output starts here.
echo $OUTPUT->header();

$intro =""; 

// If some text was written, show the intro.
if ( isset($ejsappbooking->intro) && ($ejsappbooking->intro != null)) { 
    $intro = $OUTPUT->box(format_module_intro('ejsappbooking', $ejsappbooking, $cm->id), 
        'generalbox mod_introbox', 'ejsappbookingintro');
}

// Get the remote laboratories in which the user is authorized to make bookings.
$remlabs = $DB->get_records_sql("
    SELECT DISTINCT (a.id), a.name 
    FROM {ejsapp} a INNER JOIN {ejsappbooking_usersaccess} b ON a.id = b.ejsappid 
    WHERE b.userid = ? AND a.course = ? AND a.is_rem_lab = 1 AND b.allowremaccess = 1",
    array($USER->id, $course->id)
);

if (!$remlabs) {// No labs.
    echo $OUTPUT->box(get_string('no_labs_rem', 'ejsappbooking'));
    echo $OUTPUT->footer();
    return;
} 

// At least one remote lab.

// $baseurl = new moodle_url('/mod/ejsappbooking/view.php', array('id' => $id, 'labid' => $labid));

// Section One: Booking form

echo html_writer::start_tag('div', array('class' => 'row '));
    echo html_writer::start_tag('div', array('class' => 'col-md-8 offset-md-1'));
        echo $intro;
        echo $OUTPUT->heading(get_string('newreservation', 'ejsappbooking'));
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('form', array('id' => 'bookingform', 'method' => 'get', 
        'action' => "/mod/ejsappbooking/controllers/add_booking.php?id=".$id));

echo html_writer::start_tag('div', array('class' => 'row selectores'));    
    echo html_writer::start_tag('div', array('class' => 'col-md-3 offset-md-1'));
        echo get_string('rem_lab_selection', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>';
        include('views/select_labs.php');
        echo '<input type="hidden" name="slot-size" />';
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', array('class' => 'col-md-4 offset-md-1'));
        echo get_string('rem_prac_selection', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>';
        include('views/select_practices.php');
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'row'));

// Left column
echo html_writer::start_tag('div', array('class' => 'col-md-3 offset-md-1'));
   echo '<span class="ui-icon ui-icon-calendar"></span> &nbsp;'.
       get_string('date-select', 'ejsappbooking') . ':&nbsp;&nbsp;'.'<br>';

    echo $OUTPUT->container('<div id="datepicker"></div>');   

    // time zone info
    $tz_edit_url = $CFG->wwwroot . "/user/editadvanced.php?id=".$USER->id; // ."#id_email"

    if ( $USER->timezone == '99'){
        $tz_str = get_string('time_zone_default', 'ejsappbooking');
    } else {
        $tz_str = get_string('time_zone', 'ejsappbooking') . ' ' . $USER->timezone ;
    } 

    echo '<p>' . $tz_str . '&nbsp; '. "<a href='$tz_edit_url' target='_blank' 
        title='".get_string('time_zone_help', 'ejsappbooking')."'>"."<span class='ui-icon ui-icon-gear'></span>
    </a></p></br>";

echo html_writer::end_tag('div'); // end column


// Right column
echo html_writer::start_tag('div', array('class' => 'col-md-3 offset-md-1'));

// Current date

//echo '<img src="pix/clock_16px.png" title="Made by Freepic under license CC 3.0 BY from www.flaticon.com" />&nbsp;' .
echo '<span class="ui-icon ui-icon-clock"></span>&nbsp;' . 
    get_string('time-select', 'ejsappbooking').':'; 

//<em><label id="current-date"></label></em>'.'<br>';

// timepicker
include('views/timepicker.php');  

// hiddens warnings
echo '<div id="notif-area">';
echo '<div class="alert alert-primary slot-free" role="alert">'.
    get_string('slot-free', 'ejsappbooking').'</div>';
echo '<div class="alert alert-dark slot-past error" role="alert">'. 
    get_string('slot-past', 'ejsappbooking').'</div>';
echo '<div class="alert alert-warning slot-busy error" role="alert">'. 
    get_string('slot-busy', 'ejsappbooking').'</div>';
echo '<div class="alert alert-danger plant-inactive error" role="alert">'. 
    get_string('plant-inactive', 'ejsappbooking').'</div>';  
echo '<div class="alert alert-success plant-active" role="alert">'. 
    get_string('plant-active', 'ejsappbooking').'</div>';    
echo '<div id="notif" class="alert" role="alert">&nbsp;</div>';
echo '<div id="submit-error" class="alert" role="alert">'.
    get_string('submit-error', 'ejsappbooking').'</div>';
echo '<div class="alert submit-missing-field" role="alert">'.
    get_string('submit-missing-field', 'ejsappbooking').'</div>';
echo '</div>'; // end notif-area

// submit button
echo '<div id="submitwrap">'.
        '<button id="booking_btn" name="bookingbutton" class="btn btn-secondary" value="1" type="submit">' .
        get_string('book', 'ejsappbooking') . '</button></div>';

echo html_writer::end_tag('div'); // end column
echo html_writer::end_tag('div'); // end row

 echo html_writer::end_tag('form');

// Section Two: My bookings table

echo html_writer::start_tag('div', array('class' => 'row'));
    echo html_writer::start_tag('div', array('class' => 'col-md-3 offset-md-1'));
        echo $OUTPUT->heading(get_string('mybookings', 'ejsappbooking'));
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', array('class' => 'col-md-3'));
        echo '<nav><ul class="pagination" style="display: none">
            <li class="page-item"><a class="page-link" href="#">
                &laquo; </a></li>
            <li class="page-item"><a class="page-link" href="#">
                &raquo;</a></li>
        </ul></nav>';
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'row'));
    echo html_writer::start_tag('div', array('class' => 'col-md-7 offset-md-1 '));

    echo '<p e id="mybookings_notif" >' 
        . get_string('mybookings_empty','ejsappbooking') . '</p>';

    echo '<table id="mybookings" class="table table-hover table-responsive-sm" style="display: none">
            <thead><tr>
               <th></th>
               <th>'.get_string('date', 'ejsappbooking').'</th>              
               <th>'.get_string('plant', 'ejsappbooking').'</th>
               <th>'.get_string('hour', 'ejsappbooking').'</th>                        
               <th>'.get_string('action', 'ejsappbooking').'</th>
               </tr></thead>
            <tbody></tbody>
          </table>';

    echo '<div id="del-confirm" class="alert role="alert">' . get_string('delete-confirmation', 'ejsappbooking').'</div>';

    echo html_writer::end_tag('div'); // end col
echo html_writer::end_tag('div'); // end row

// Finish the page.
echo $OUTPUT->footer();