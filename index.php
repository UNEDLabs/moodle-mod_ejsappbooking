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
 * Prints the overview of all ejsappbookings included into the current course
 *
 * This page is used by Moodle when listing all the instances of your module that are in a
 * particular course with the course id being passed to this script
 *
 * @package    mod_ejsappbooking
 * @copyright  2012 Francisco José Calvillo Muñoz, Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

if ($CFG->version < 2013111899) { // Moodle 2.6 or inferior.
    add_to_log($course->id, 'ejsappbooking', 'view all', "index.php?id=$course->id", '');
} else {
    $params = array(
        'context' => context_course::instance($course->id)
    );
    $event = \mod_ejsappbooking\event\course_module_instance_list_viewed::create($params);
    $event->add_record_snapshot('course', $course);
    $event->trigger();
}

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/ejsapp/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

if (! $ejsappbookings = get_all_instances_in_course('ejsappbooking', $course)) {
    notice(get_string('noejsappbookings', 'ejsappbooking'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_ejsappbooking';

if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($ejsappbookings as $ejsappbooking) {
    if (!$ejsappbooking->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/ejsappbooking.php', array('id' => $ejsappbooking->coursemodule)),
            format_string($ejsappbooking->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/ejsappbooking.php', array('id' => $ejsappbooking->coursemodule)),
            format_string($ejsappbooking->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($ejsappbooking->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'ejsappbooking'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();