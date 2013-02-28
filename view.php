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
//  - Javier Pavon: javi.pavon@gmail.com
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
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ejsappbooking instance ID - it should be named as the first character of the module

if ($id) {
    $cm  = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $ejsappbooking = $DB->get_record('ejsappbooking', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $ejsappbooking->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ejsappbooking', $ejsappbooking->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'ejsappbooking', 'view', "view.php?id={$cm->id}", $ejsappbooking->name, $cm->id);

/// Print the page header
$PAGE->set_title(format_string($ejsappbooking->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url('/mod/ejsappbooking/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'ejsappbooking')));

//$PAGE->set_pagelayout('incourse');

// Output starts here
echo $OUTPUT->header();

// Embed the BookingClient applet into Moodle
$dir = get_plugin_directory('mod','ejsappbooking');      
if ( is_file($dir . '/applets/BookingClient/BookingClient.jar') && is_file($dir . '/applets/BookingServer/configuracion/valores.dat') ) {
  global $USER, $CFG;
  //Applet params:
  $host = $CFG->wwwroot;
  $fs = fopen($dir . '/applets/BookingServer/configuracion/valores.dat','r');
  $port = fgets($fs);
  fclose($fs);
  $port = intval(substr($port,5));
  $language = current_language();   
  $dbhost = $CFG->dbhost;
  if (strcmp($dbhost,'localhost') == 0) $dbhost = substr($CFG->wwwroot,7); 
  $dbhost_exp = explode("/",$dbhost);
  $dbhost = $dbhost_exp[0];  
  $server_time = number_format(microtime(true)*1000,0,'.','');
  $code = '';
  $code .= '<script "text/javascript">';
  $code .= "var w = 740, h = 545;
  document.write('<applet code=\"com.booking_client.ClienteReservas.class\"');
  document.write('codebase=\"$host/mod/ejsappbooking/applets/BookingClient/\"');
  document.write('archive=\"BookingClient.jar\"');
  document.write('name=\"BookingClient\"');
  document.write('id=\"BookingClient\"');
  document.write('width=\"'+w+'\"');
  document.write('height=\"'+h+'\"');
  document.write('<param name=\"nullParam\" value=\"null\"/>');
  document.write('<param name=\"host\" value=\"{$dbhost}\"/>');
  document.write('<param name=\"port\" value=\"{$port}\"/>');
  document.write('<param name=\"lang\" value=\"{$language}\"/>');
  document.write('<param name=\"user\" value=\"{$USER->username}\"/>');
  document.write('<param name=\"password\" value=\"{$USER->password}\"/>');
  document.write('<param name=\"serverTime\" value=\"{$server_time}\"/>');
  document.write('</applet>');";
  $code .= '</script>';
} else {
  $code = get_string('view_error', 'ejsappbooking');
}

echo $OUTPUT->heading($code);

if ($ejsappbooking->intro) { // If some text was written, show the intro
  echo $OUTPUT->box(format_module_intro('ejsappbooking', $ejsappbooking, $cm->id), 'generalbox mod_introbox', 'ejsappbookingintro');
}

// Check wether the user has teacher or admin privileges. If so, let him grant his students access to make bookings for the remote labs in the course
if (has_capability('moodle/course:viewhiddensections', $context, $USER->id, true)) {
  $set_permissions = $CFG->wwwroot . '/mod/ejsappbooking/set_permissions.php';
  echo $OUTPUT->heading('<form action="' . $set_permissions . '" method="get"><input type="hidden" name="id" value="' . $cm->id . '"><input type="hidden" name="courseid" value="' . $course->id . '"><input type="hidden" name="contextid" value="' . $context->id . '"><input type=submit id="manage_access" value="' . get_string('manage_access_but', 'ejsappbooking') . '"></form>');  
} 

// Finish the page
echo $OUTPUT->footer();