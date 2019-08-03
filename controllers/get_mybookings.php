<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...

$model = new ejsappbooking_model($id, null);

$bookings = $model->get_mybookings();

echo json_encode($bookings);