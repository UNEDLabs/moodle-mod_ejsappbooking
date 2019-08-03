<?php 

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');
require_once($CFG->dirroot . '/filter/multilang/filter.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');  // userprofile
require_once($CFG->dirroot . '/filter/multilang/filter.php');

require_once(dirname(dirname(__FILE__)) . '/ejsappbooking_model.class.php');

global $DB, $CFG, $USER, $PAGE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // We need course_module ID, or...
$labid = optional_param('labid', 0, PARAM_INT); // Selected laboratory.
$practid = optional_param('practid',0, PARAM_INT); // 
$date = optional_param('date', 0, PARAM_RAW);
$time = optional_param('time',0, PARAM_RAW);

$model = new ejsappbooking_model($id, null);

$exit = $model->add_booking($labid, $practid, $date, $time);

echo json_encode($exit);

