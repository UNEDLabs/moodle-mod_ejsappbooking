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


global $DB, $CFG, $USER, $PAGE, $OUTPUT;


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
//require_once(dirname(__FILE__) . '/locallib.php');

require_once(dirname(__FILE__) . '/ejsappbooking_model.class.php');
require_once(dirname(__FILE__) . '/ejsappbooking_view.class.php');

//require_once($CFG->libdir . '/tablelib.php');
//require_once($CFG->libdir . '/filelib.php');
//require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->libdir . '/moodlelib.php');
// require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$n = optional_param('n', 0, PARAM_INT); // ...ejsappbooking instance ID - it should be named as the first character of the module.
//$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
//$practid = optional_param('practid', 0, PARAM_INT); // Selected practice.

//$booking = optional_param_array('booking', array(), PARAM_INT); // Bookings selected to save or delete.
//$bookingbutton = optional_param('bookingbutton', 0, PARAM_RAW); // Controls the functionality for saving bookings.
//$mybookingsbutton = optional_param('Mybookingsbutton', 0, PARAM_RAW); // Controls the functionality of showing the bookings.
//$mybookingsdelete = optional_param('Mybookingsdelete', 0, PARAM_RAW); // Controls functionality to delete the selected bookings.
//$selectday = optional_param('selectDay', 0, PARAM_RAW); // Selected day.
//$page = optional_param('page', 0, PARAM_INT); // Which page to show.

$model = new ejsappbooking_model($id, $n);


$view = new ejsappbooking_view(
   $id, $model->get_mod_url(), $model->get_mod_name(), $model->get_course_name(),
   $model->get_mod_intro(), $model->get_remlabs(), $model->get_practices($id), 
   $model->get_user_timezone_str(), $model->get_timezone_edit_url()
);

$view->render();
