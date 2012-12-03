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
 *
 * Prints a particular instance of ejsappbooking
 *
 * @package    mod
 * @subpackage ejsappbooking
 * @copyright  2012 Javier Pavon, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$cm = get_coursemodule_from_id('ejsappbooking', $id, 0, false, MUST_EXIST);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = get_context_instance_by_id($contextid, MUST_EXIST);

$title = get_string('selectRemLab_pageTitle', 'ejsappbooking');
$PAGE->set_context($context);
$PAGE->set_url('/mod/ejsappbooking/select_rem_lab.php', array('id' => $id, 'courseid' => $courseid, 'contextid' => $contextid));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('rem_lab_selection', 'ejsappbooking'));

$select_users = $CFG->wwwroot . '/mod/ejsappbooking/select_users.php';
echo '<form action="' . $select_users . '" method="get" >';

$rem_labs = $DB->get_records('ejsapp', array('course' => $courseid, 'is_rem_lab' => '1'));
$i = 1;

require_once('../../filter/multilang/filter.php');
$multilang = new filter_multilang($context, array('filter_multilang_force_old'=>0));
foreach ($rem_labs as $rem_lab) {
  $lab_name = $multilang->filter($rem_lab->name);
  //$lab_name = $rem_lab->name; 
  if ($i == 1) {
    echo '<input type=radio name="labid" value="' . $rem_lab->id . '" checked> ' . $lab_name . '<br>';
  } else {
    echo '<input type=radio name="labid" value="' . $rem_lab->id . '"> ' . $lab_name . '<br>';
  }
  $i++;
}

$ejsappbooking = $DB->get_record('ejsappbooking', array('course' => $courseid));

if ($i>1) {
  echo '<input type="hidden" name="bookingid" value="' . $ejsappbooking->id . '"><input type="hidden" name="id" value="' . $cm->id . '"><input type="hidden" name="courseid" value="' . $courseid . '"><input type="hidden" name="contextid" value="' . $contextid . '"><br><p align="center"><input type=submit value="' . get_string('select_users_but', 'ejsappbooking') . '"></p></form>';
} else {
  echo  get_string('no_rem_labs', 'ejsappbooking');
}

echo $OUTPUT->footer();

?>